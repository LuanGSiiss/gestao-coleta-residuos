import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';

import {
    comprimentoMetros,
    contarPartes,
    contarPontos,
    criarMapa,
    enquadrar,
    formatarKm,
    rotuloRota,
} from './base';

/*
 * Textos próprios por cima da tradução pt_br do Geoman: "trecho" é a linguagem do
 * gestor, "polilinha" não. O terceiro argumento do setLang faz o resto cair no pt_br.
 * Se algum título não mudar, o nome da chave pode ter mudado entre versões: confira
 * no arquivo de tradução en.json do repositório do Geoman.
 */
const TEXTOS = {
    buttonTitles: {
        drawLineButton: 'Desenhar trecho',
        editButton: 'Editar trechos',
        cutButton: 'Recortar trecho',
        deleteButton: 'Remover trecho',
    },
};

const DICAS = {
    padrao: 'Use as ferramentas à esquerda do mapa para desenhar, editar, recortar ou remover trechos.',
    desenho: 'Clique para marcar cada vértice e clique no último para concluir. O próximo trecho começa em seguida. Esc encerra o desenho.',
    edicao: 'Arraste os vértices para ajustar e os pontos intermediários para criar vértices. Botão direito remove um vértice. Segure Alt para desativar o alinhamento.',
    recorte: 'Desenhe um polígono sobre a parte do trecho que deve ser retirada.',
    remocao: 'Clique sobre um trecho para removê-lo.',
};

const ESTILO_REFERENCIA = { color: '#6c757d', weight: 3, opacity: 0.6, dashArray: '6 6' };

const estiloTrecho = (cor) => ({ color: cor, weight: 5, opacity: 0.9 });

export function iniciarEditorRota(elemento, dados) {
    const buscar = (chave) => document.getElementById(elemento.dataset[chave]);

    const entrada = buscar('entrada');
    const campoCor = buscar('campoCor');
    const dica = buscar('dica');
    const extensao = buscar('extensao');
    const contagem = buscar('contagem');
    const formulario = entrada.form;

    const mapa = criarMapa(elemento, dados.centro, dados.zoom);

    const grupo = L.featureGroup().addTo(mapa);

    let cor = campoCor.value;
    let alterado = false;
    let enviando = false;

    // Carrega as outras rotas como referência.
    const referencia = L.geoJSON(dados.referencia, {
        pmIgnore: true,
        snapIgnore: false,
        style: ESTILO_REFERENCIA,
        onEachFeature: (feature, camada) => {
            camada.bindTooltip(rotuloRota(feature.properties), { sticky: true });
        },
    }).addTo(mapa);

    // Carrega os trechos atuais da rota ou o que foi desenhado antes de um erro de validação.
    try {
        if (dados.trajeto) {
            L.geoJSON(dados.trajeto).eachLayer(registrar);
        }
    } catch {
        // Abre o mapa vazio
    }

    enquadrar(mapa, grupo, referencia);
    configurarGeoman();
    atualizarResumo();
    atualizarDica();

    function registrar(camada) {
        if (contarPontos(camada.getLatLngs()) < 2) {
            grupo.removeLayer(camada);
            camada.remove();
            return;
        }

        camada.setStyle(estiloTrecho(cor));

        camada.on('pm:edit', () => {
            alterado = true;
            atualizarResumo();
        });

        grupo.addLayer(camada);
    }

    function configurarGeoman() {
        mapa.pm.setLang('trechos', TEXTOS, 'pt_br');

        mapa.pm.addControls({
            position: 'topleft',
            drawPolyline: true,
            editMode: true,
            cutPolygon: true,
            removalMode: true,
            drawMarker: false,
            drawCircleMarker: false,
            drawCircle: false,
            drawRectangle: false,
            drawPolygon: false,
            drawText: false,
            rotateMode: false,
            dragMode: false,
        });

        mapa.pm.setGlobalOptions({
            snappable: true,
            snapDistance: 10,
            continueDrawing: true,
            layerGroup: grupo,
            removeLayerBelowMinVertexCount: false,
            pathOptions: estiloTrecho(cor),
            templineStyle: { color: cor },
            hintlineStyle: { color: cor, dashArray: [5, 5] },
        });

        mapa.on('pm:create', ({ layer }) => {
            registrar(layer);
            alterado = true;
            atualizarResumo();
        });

        mapa.on('pm:remove', ({ layer }) => {
            grupo.removeLayer(layer);
            alterado = true;
            atualizarResumo();
        });

        // O recorte substitui o trecho original por um novo, que pode ter várias partes.
        mapa.on('pm:cut', ({ originalLayer, layer }) => {
            grupo.removeLayer(originalLayer);
            registrar(layer);
            alterado = true;
            atualizarResumo();
        });

        mapa.on(
            'pm:globaldrawmodetoggled pm:globaleditmodetoggled pm:globalcutmodetoggled pm:globalremovalmodetoggled',
            atualizarDica,
        );
    }

    function modoAtual() {
        if (mapa.pm.globalDrawModeEnabled()) {
            return mapa.pm.Draw.getActiveShape() === 'Cut' ? 'recorte' : 'desenho';
        }

        if (mapa.pm.globalEditModeEnabled()) {
            return 'edicao';
        }

        if (mapa.pm.globalCutModeEnabled()) {
            return 'recorte';
        }

        if (mapa.pm.globalRemovalModeEnabled()) {
            return 'remocao';
        }

        return 'padrao';
    }

    function atualizarDica() {
        setTimeout(() => {
            dica.textContent = DICAS[modoAtual()];
        });
    }

    function atualizarResumo() {
        let metros = 0;
        let partes = 0;

        grupo.eachLayer((camada) => {
            metros += comprimentoMetros(camada.getLatLngs());
            partes += contarPartes(camada.getLatLngs());
        });

        extensao.textContent = formatarKm(metros);
        contagem.textContent = partes;
    }

    function encerrarFerramentas() {
        mapa.pm.disableDraw();
        mapa.pm.disableGlobalEditMode();
        mapa.pm.disableGlobalCutMode();
        mapa.pm.disableGlobalRemovalMode();
    }

    // Adiciona a opção de sair da ferramenta ativa pressionando "Esc".
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !document.querySelector('.modal.show')) {
            encerrarFerramentas();
        }
    });

    // Atualiza a cor dos trechos do desenho sempre que for mudado no campo.
    campoCor.addEventListener('input', () => {
        cor = campoCor.value;
        grupo.setStyle(estiloTrecho(cor));
        mapa.pm.setGlobalOptions({
            pathOptions: estiloTrecho(cor),
            templineStyle: { color: cor },
            hintlineStyle: { color: cor, dashArray: [5, 5] },
        });
    });

    formulario.addEventListener('input', () => {
        alterado = true;
    });

    formulario.addEventListener('submit', () => {
        if (mapa.pm.globalDrawModeEnabled() && mapa.pm.Draw.getActiveShape() === 'Line') {
            mapa.pm.finishDraw();
        }

        encerrarFerramentas();
        
        entrada.value = JSON.stringify(grupo.toGeoJSON(6));
        enviando = true;
    });

    // Pede confirmação ao sair da página com alterações não salvas.
    window.addEventListener('beforeunload', (evento) => {
        if (alterado && !enviando) {
            evento.preventDefault();
            evento.returnValue = '';
        }
    });
}