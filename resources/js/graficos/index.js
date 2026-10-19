import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

import { configurar } from './configuracao';

// Registra só as peças que os gráficos usam. Deixando as demais de fora.
Chart.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
);

document.addEventListener('DOMContentLoaded', () => {
    const css = getComputedStyle(document.documentElement);
    const variavel = (nome) => css.getPropertyValue(nome).trim();

    const aparencia = {
        fonte: getComputedStyle(document.body).fontFamily,
        texto: variavel('--bs-body-color'),
        grade: variavel('--bs-border-color'),
        fundo: variavel('--bs-body-bg'),
        cores: {
            registrada: variavel('--grafico-registrada'),
            prevista: variavel('--grafico-prevista'),
            convencional: variavel('--grafico-convencional'),
            seletiva: variavel('--grafico-seletiva'),
        },
    };

    Chart.defaults.font.family = aparencia.fonte;
    Chart.defaults.color = variavel('--bs-secondary-color');

    document.querySelectorAll('canvas[data-grafico]').forEach((canvas) => {
        const fonte = document.getElementById(canvas.dataset.dados);

        if (fonte) {
            new Chart(canvas, configurar(canvas.dataset.grafico, JSON.parse(fonte.textContent), aparencia));
        }
    });
});