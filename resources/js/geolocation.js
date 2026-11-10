const storageKey = 'campo.geolocation';
let pending;
let lastState = { status: 'unknown', position: null };

function readState() {
    try {
        return JSON.parse(sessionStorage.getItem(storageKey) || 'null');
    } catch {
        return null;
    }
}

function publish(state) {
    lastState = state;
    try {
        sessionStorage.setItem(storageKey, JSON.stringify(state));
    } catch {
        // O mapa continua funcionando quando o armazenamento do navegador não está disponível.
    }
    window.dispatchEvent(new CustomEvent('campo:location', { detail: state }));
    const notice = document.querySelector('[data-location-notice]');
    if (notice) {
        notice.hidden = state.status === 'granted';
        notice.querySelector('[data-location-message]').textContent = state.message || '';
        notice.querySelector('[data-location-retry]').hidden = state.status === 'pending';
    }
    return state.position || null;
}

export function requestLocation({ retry = false } = {}) {
    if (pending && !retry) {
        return pending;
    }
    if (!window.isSecureContext || !navigator.geolocation) {
        return Promise.resolve(publish({ status: 'unavailable', message: 'A localização precisa de HTTPS ou localhost e de um navegador compatível. O mapa pode ser usado manualmente.' }));
    }
    const previous = readState();
    if (!retry && previous?.status === 'granted' && previous.position
        && Date.now() - previous.timestamp < 300000) {
        return Promise.resolve(publish(previous));
    }
    if (!retry && previous && ['denied', 'unavailable', 'pending'].includes(previous.status)) {
        return Promise.resolve(publish({ ...previous, status: previous.status === 'pending' ? 'unavailable' : previous.status,
            message: previous.status === 'pending' ? 'A localização ainda não foi obtida. Tente novamente ou use o mapa manualmente.' : previous.message }));
    }
    publish({ status: 'pending', message: 'Permita o acesso à localização para iniciar os mapas na sua região.' });
    pending = new Promise((resolve) => {
        try {
            navigator.geolocation.getCurrentPosition(
            ({ coords }) => resolve(publish({ status: 'granted', position: { latitude: coords.latitude, longitude: coords.longitude }, timestamp: Date.now() })),
            (error) => resolve(publish({ status: error.code === 1 ? 'denied' : 'unavailable',
                message: error.code === 1 ? 'Localização não autorizada. Você pode usar o mapa manualmente ou habilitar a localização nas permissões do navegador.' : 'Não foi possível obter sua localização. Você pode tentar novamente ou usar o mapa manualmente.' })),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 300000 },
            );
        } catch {
            resolve(publish({ status: 'unavailable', message: 'O navegador bloqueou o acesso à localização. O mapa continua disponível para uso manual.' }));
        }
    }).finally(() => { pending = null; });
    return pending;
}

export function locationState() {
    return lastState;
}

export function initializeGeolocation() {
    document.querySelector('[data-location-retry]')?.addEventListener('click', () => requestLocation({ retry: true }));
    return requestLocation();
}
