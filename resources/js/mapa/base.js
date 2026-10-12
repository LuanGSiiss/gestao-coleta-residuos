import L from 'leaflet';

/*
 * Política de uso dos tiles do OpenStreetMap:
 * - um único endereço, sem os subdomínios a/b/c, que podem ser retirados sem aviso;
 * - sempre HTTPS;
 * - atribuição visível, com link para a página de direitos autorais;
 * - o navegador precisa enviar o cabeçalho Referer. Por isso não se configura
 *   referrerPolicy restritivo aqui — nem um Referrer-Policy: no-referrer no Laravel,
 *   se um dia forem acrescentados cabeçalhos de segurança.
 * Uso intenso exigiria servidor de tiles próprio ou um provedor contratado.
 */
const URL_TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

const ATRIBUICAO =
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    + ' · <a href="https://www.openstreetmap.org/fixthemap">Corrigir o mapa</a>';

export function criarMapa(elemento, centro, zoom) {
    const mapa = L.map(elemento).setView(centro, zoom);

    L.tileLayer(URL_TILES, { maxZoom: 19, attribution: ATRIBUICAO }).addTo(mapa);

    return mapa;
}

/** Enquadra o mapa no primeiro grupo que tiver camadas; se nenhum tiver, mantém a visão inicial. */
export function enquadrar(mapa, ...grupos) {
    for (const grupo of grupos) {
        if (grupo.getLayers().length > 0 && grupo.getBounds().isValid()) {
            mapa.fitBounds(grupo.getBounds(), { padding: [24, 24] });
            return;
        }
    }
}

/**
 * Comprimento de uma polilinha em metros. Aceita linha simples e linha com várias
 * partes, que é o que o recorte produz.
 */
export function comprimentoMetros(latlngs) {
    if (!L.LineUtil.isFlat(latlngs)) {
        return latlngs.reduce((total, parte) => total + comprimentoMetros(parte), 0);
    }

    let total = 0;

    for (let i = 1; i < latlngs.length; i++) {
        // distanceTo usa Haversine, com o mesmo raio que o servidor usa.
        total += latlngs[i - 1].distanceTo(latlngs[i]);
    }

    return total;
}

/** Quantos trechos uma camada vai virar no servidor: um, ou um por parte. */
export function contarPartes(latlngs) {
    return L.LineUtil.isFlat(latlngs) ? 1 : latlngs.length;
}

/** Quantidade de pontos, somando todas as partes. */
export function contarPontos(latlngs) {
    return L.LineUtil.isFlat(latlngs)
        ? latlngs.length
        : latlngs.reduce((total, parte) => total + contarPontos(parte), 0);
}

export function formatarKm(metros) {
    const km = (metros / 1000).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return `${km} km`;
}

/*
 * Tooltips e popups do Leaflet interpretam texto como HTML. Nomes de rota são digitados
 * por usuários, então o conteúdo é montado com elementos e textContent.
 */
export function rotuloRota(propriedades) {
    const elemento = document.createElement('span');
    elemento.textContent = [propriedades.identificacao, propriedades.nome].filter(Boolean).join(' · ');
    
    return elemento;
}

export function conteudoPopup(propriedades) {
    const caixa = document.createElement('div');

    const titulo = document.createElement('strong');
    titulo.textContent = `${propriedades.identificacao} · ${propriedades.nome}`;

    const extensao = document.createElement('div');
    extensao.textContent = `Extensão: ${propriedades.extensao}`;

    caixa.append(titulo, extensao);

    if (propriedades.url) {
        const link = document.createElement('a');
        link.href = propriedades.url;
        link.textContent = 'Abrir rota';
        caixa.append(link);
    }

    return caixa;
}