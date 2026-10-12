import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

import { iniciarEditorRota } from './editor-rota';
import { iniciarVisualizacao } from './visualizacao';

const INICIALIZADORES = {
    'editor-rota': iniciarEditorRota,
    visualizacao: iniciarVisualizacao,
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-mapa]').forEach((elemento) => {
        const iniciar = INICIALIZADORES[elemento.dataset.mapa];
        const fonte = document.getElementById(elemento.dataset.dados);

        if (iniciar && fonte) {
            iniciar(elemento, JSON.parse(fonte.textContent));
        }
    });
});