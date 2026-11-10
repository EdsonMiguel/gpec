import { initializeGeolocation } from './geolocation';
import './navigation';
import './destino-lancamento';

initializeGeolocation();

if (document.querySelector('[data-weight-chart]')) {
    import('./weight-chart');
}

if (document.querySelector('[data-fin-form]')) {
    import('./financeiro-form');
}

if (document.querySelector('[data-refeicao-form]')) {
    import('./refeicao-form');
}

if (document.querySelector('[data-geojson-map]')) {
    import('./maps/geojson-map').then(({ initializeMaps }) => initializeMaps()).catch(() => {
        document.querySelectorAll('[data-map-status]').forEach((element) => {
            element.textContent = 'Não foi possível carregar o mapa. Confira sua conexão e recarregue a página.';
        });
    });
}
