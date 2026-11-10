import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw/dist/leaflet.draw.css';
import { locationState, requestLocation } from '../geolocation';

window.L = L;

function featuresFrom(document) {
    if (!document) return [];
    if (document.type === 'FeatureCollection') return document.features.flatMap(featuresFrom);
    const feature = document.type === 'Feature' ? document : { type: 'Feature', properties: {}, geometry: document };
    if (feature.geometry?.type === 'MultiPolygon') {
        return feature.geometry.coordinates.map((coordinates) => ({ type: 'Feature', properties: feature.properties || {}, geometry: { type: 'Polygon', coordinates } }));
    }
    if (feature.geometry?.type !== 'Polygon') throw new Error('Geometria não suportada.');
    return [feature];
}

function localizeDrawing() {
    Object.assign(L.drawLocal.draw.toolbar.buttons, { polygon: 'Desenhar polígono', rectangle: 'Desenhar retângulo' });
    Object.assign(L.drawLocal.draw.toolbar.actions, { title: 'Cancelar desenho', text: 'Cancelar' });
    Object.assign(L.drawLocal.draw.toolbar.finish, { title: 'Finalizar contorno', text: 'Finalizar' });
    Object.assign(L.drawLocal.draw.toolbar.undo, { title: 'Remover último ponto', text: 'Desfazer ponto' });
    Object.assign(L.drawLocal.draw.handlers.polygon.tooltip, { start: 'Toque para começar o contorno.', cont: 'Toque para adicionar um ponto.', end: 'Toque no primeiro ponto para finalizar.' });
    L.drawLocal.draw.handlers.rectangle.tooltip.start = 'Arraste para marcar um retângulo.';
    L.drawLocal.draw.handlers.simpleshape.tooltip.end = 'Solte para finalizar.';
    L.drawLocal.draw.handlers.polyline.error = 'O contorno não pode cruzar suas próprias linhas.';
    Object.assign(L.drawLocal.edit.toolbar.buttons, { edit: 'Editar vértices', editDisabled: 'Desenhe um contorno para editar', remove: 'Excluir contornos', removeDisabled: 'Não há contornos para excluir' });
    Object.assign(L.drawLocal.edit.toolbar.actions.save, { title: 'Aplicar alterações no contorno', text: 'Aplicar' });
    Object.assign(L.drawLocal.edit.toolbar.actions.cancel, { title: 'Cancelar alterações', text: 'Cancelar' });
    Object.assign(L.drawLocal.edit.toolbar.actions.clearAll, { title: 'Remover todos os contornos', text: 'Limpar todos' });
    Object.assign(L.drawLocal.edit.handlers.edit.tooltip, { text: 'Arraste os pontos para editar.', subtext: 'Toque em Aplicar para concluir.' });
    L.drawLocal.edit.handlers.remove.tooltip.text = 'Toque no contorno a excluir e depois em Aplicar.';
}

export async function initializeMaps() {
    await import('leaflet-draw');
    localizeDrawing();
    document.querySelectorAll('[data-geojson-map]').forEach(initializeMap);
}

function initializeMap(element) {
    const readonly = element.dataset.readonly === 'true';
    const input = element.querySelector('[data-geojson-value]');
    const status = element.querySelector('[data-map-status]');
    const map = L.map(element.querySelector('[data-map-canvas]'), { scrollWheelZoom: false }).setView([-15.78, -47.93], 4);
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 20, maxNativeZoom: 19,
        attribution: 'Imagens &copy; Esri, Vantor, Earthstar Geographics e GIS User Community',
    }).addTo(map);
    const streets = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' });
    const context = L.featureGroup().addTo(map);
    const shapes = L.featureGroup().addTo(map);
    L.control.layers({ 'Satélite': satellite, 'Mapa de ruas': streets }, {}, { position: 'topright' }).addTo(map);
    let touched = false;
    let drawing = false;
    let editing = false;
    let locationMarker;
    map.on('dragstart zoomstart', () => { touched = true; });
    satellite.on('tileerror', () => { status.textContent = 'Não foi possível carregar algumas imagens de satélite. Confira a conexão ou selecione Mapa de ruas.'; });

    const addGeometry = (value, group, style) => {
        const features = featuresFrom(JSON.parse(value));
        L.geoJSON({ type: 'FeatureCollection', features }, { style }).eachLayer((layer) => group.addLayer(layer));
    };
    try {
        if (element.dataset.context) addGeometry(element.dataset.context, context, { color: '#f59e0b', weight: 2, dashArray: '6 6', fillOpacity: 0.04, interactive: false });
        if (input.value) addGeometry(input.value, shapes, { color: '#10b981', weight: 3, fillOpacity: 0.18 });
        status.textContent = shapes.getLayers().length ? 'Contorno carregado. As alterações são gravadas ao salvar o cadastro.' : 'Use as ferramentas do mapa para marcar o contorno.';
    } catch {
        status.textContent = 'Não foi possível abrir a marcação. Limpe-a e desenhe novamente antes de salvar.';
    }

    const fit = () => {
        const group = shapes.getLayers().length ? shapes : context;
        if (group.getLayers().length) map.fitBounds(group.getBounds(), { padding: [25, 25], maxZoom: 18 });
    };
    const syncButtons = () => {
        element.querySelector('[data-map-fit]').disabled = !shapes.getLayers().length && !context.getLayers().length;
        const clear = element.querySelector('[data-map-clear]');
        if (clear) clear.disabled = drawing || editing || (!shapes.getLayers().length && !input.value);
    };
    const serialize = () => {
        input.value = shapes.getLayers().length ? JSON.stringify(shapes.toGeoJSON()) : '';
        status.textContent = shapes.getLayers().length ? 'Contorno atualizado. Salve o cadastro para gravar a marcação.' : 'Sem contorno. Salve o cadastro para confirmar a remoção.';
        syncButtons();
    };
    fit();
    syncButtons();
    element.querySelector('[data-map-fit]').addEventListener('click', fit);

    const locate = (position, force = false) => {
        if (!position) {
            if (force) status.textContent = locationState().message;
            return;
        }
        const latlng = [position.latitude, position.longitude];
        if (locationMarker) map.removeLayer(locationMarker);
        locationMarker = L.circleMarker(latlng, { radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1 }).addTo(map);
        if (force || (!touched && !shapes.getLayers().length && !context.getLayers().length)) map.setView(latlng, 17);
    };
    requestLocation().then((position) => locate(position));
    element.querySelector('[data-map-locate]').addEventListener('click', () => {
        status.textContent = 'Obtendo sua localização…';
        requestLocation({ retry: true }).then((position) => {
            locate(position, true);
            if (position) status.textContent = 'Mapa centralizado na sua localização. O ponto azul não faz parte do contorno.';
        });
    });

    if (!readonly) {
        map.addControl(new L.Control.Draw({
            position: 'topleft',
            draw: { polygon: { allowIntersection: false, showArea: false, shapeOptions: { color: '#10b981' } }, rectangle: { showArea: false, shapeOptions: { color: '#10b981' } }, polyline: false, circle: false, circlemarker: false, marker: false },
            edit: { featureGroup: shapes, poly: { allowIntersection: false } },
        }));
        map.on('draw:drawstart', () => { drawing = true; touched = true; syncButtons(); });
        map.on('draw:drawstop', () => { drawing = false; syncButtons(); });
        map.on('draw:editstart draw:deletestart', () => { editing = true; syncButtons(); });
        map.on('draw:editstop draw:deletestop', () => { editing = false; syncButtons(); });
        map.on('draw:created', ({ layer }) => { shapes.addLayer(layer); serialize(); });
        map.on('draw:edited draw:deleted', serialize);
        element.querySelector('[data-map-clear]').addEventListener('click', () => { shapes.clearLayers(); serialize(); });
        element.closest('form')?.addEventListener('submit', (event) => {
            if (drawing || editing) {
                event.preventDefault();
                status.textContent = 'Conclua ou cancele o desenho e aplique as edições no mapa antes de salvar o cadastro.';
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
    new ResizeObserver(() => map.invalidateSize()).observe(element.querySelector('[data-map-canvas]'));
}
