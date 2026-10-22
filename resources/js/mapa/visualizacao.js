import L from 'leaflet';

import { criarMapa, enquadrar, rotuloRota } from './base';

/**
 * Mapa somente de leitura, utilizado para a versões do trajeto e mapa geral das rotas.
 */
export function iniciarVisualizacao(elemento, dados) {
    const mapa = criarMapa(elemento, dados.centro, dados.zoom);
    const todas = L.featureGroup().addTo(mapa);

    // Cada conjunto pode trazer estilo próprio nas versões, a vigente vem em cinza
    // tracejado, por baixo da versão consultada.
    for (const conjunto of dados.camadas) {
        L.geoJSON(conjunto.colecao, {
            style: (feature) => ({
                color: feature.properties.cor,
                weight: 5,
                opacity: 0.9,
                ...conjunto.estilo,
            }),
            onEachFeature: (feature, camada) => {
                camada.bindTooltip(rotuloRota(feature.properties), { sticky: true });
            },
        }).addTo(todas);
    }

    enquadrar(mapa, todas);
}