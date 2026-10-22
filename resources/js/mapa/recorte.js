/*
 * O recorte de trechos por um polígono, ao desenhar o que fica dentro do polígono sai do trajeto.
 */

/** Cortes mais próximos que isto, num segmento, são o mesmo corte: um vértice do polígono. */
const MESMO_CORTE = 1e-9;

/**
 * O ponto está dentro do polígono? Uma semirreta que sai do ponto cruza a borda um número
 * ímpar de vezes se o ponto está dentro, e par se está fora.
 */
export function dentro(ponto, poligono) {
    let resultado = false;

    for (let i = 0, j = poligono.length - 1; i < poligono.length; j = i++) {
        const a = poligono[i];
        const b = poligono[j];

        if ((a.lat > ponto.lat) !== (b.lat > ponto.lat)
            && ponto.lng < ((b.lng - a.lng) * (ponto.lat - a.lat)) / (b.lat - a.lat) + a.lng) {
            resultado = !resultado;
        }
    }

    return resultado;
}

/**
 * Em que fração do segmento ab, de 0 a 1, ele cruza o segmento cd. Nulo se não cruzam, ou se
 * são paralelos.
 */
function cruzamento(a, b, c, d) {
    // Produto vetorial, com x = longitude e y = latitude
    const vetorial = (u, v) => u.lng * v.lat - u.lat * v.lng;

    const r = { lat: b.lat - a.lat, lng: b.lng - a.lng };
    const s = { lat: d.lat - c.lat, lng: d.lng - c.lng };
    const ac = { lat: c.lat - a.lat, lng: c.lng - a.lng };
    const denominador = vetorial(r, s);

    if (denominador === 0) {
        return null;
    }

    const t = vetorial(ac, s) / denominador;   // posição no segmento ab
    const u = vetorial(ac, r) / denominador;   // posição no segmento cd

    return t > 0 && t < 1 && u >= 0 && u <= 1 ? t : null;
}

/** O ponto na fração t do segmento ab. Nas pontas, o próprio vértice, sem conta. */
function pontoEm(a, b, t) {
    if (t === 0) {
        return a;
    }

    if (t === 1) {
        return b;
    }

    return { lat: a.lat + (b.lat - a.lat) * t, lng: a.lng + (b.lng - a.lng) * t };
}

/**
 * As partes de uma linha que ficam fora do polígono. A linha toda fora volta como está (o
 * mesmo array, para o editor saber que nada mudou); toda dentro, some; a que cruza a borda
 * volta em pedaços, cada um com pelo menos dois pontos.
 *
 * @param {Array<{lat: number, lng: number}>} linha
 * @param {Array<{lat: number, lng: number}>} poligono  sem repetir o primeiro ponto no fim
 * @returns {Array<Array<{lat: number, lng: number}>>}
 */
export function partesFora(linha, poligono) {
    if (poligono.length < 3) {
        return [linha];
    }

    const partes = [];
    let atual = [];
    let cortou = false;

    const fechar = () => {
        if (atual.length >= 2) {
            partes.push(atual);
        }

        atual = [];
    };

    for (let i = 1; i < linha.length; i++) {
        const a = linha[i - 1];
        const b = linha[i];

        // Os pontos em que o segmento cruza a borda o dividem em pedaços. Cada pedaço fica
        // todo dentro ou todo fora do polígono, e o ponto do meio diz qual.
        const cortes = [0, 1];

        for (let j = 0; j < poligono.length; j++) {
            const t = cruzamento(a, b, poligono[j], poligono[(j + 1) % poligono.length]);

            if (t !== null) {
                cortes.push(t);
            }
        }

        cortes.sort((x, y) => x - y);

        for (let k = 1; k < cortes.length; k++) {
            const inicio = cortes[k - 1];
            const fim = cortes[k];

            if (fim - inicio < MESMO_CORTE) {
                continue;
            }

            if (dentro(pontoEm(a, b, (inicio + fim) / 2), poligono)) {
                cortou = true;
                fechar();
            } else {
                // O pedaço de fora continua a parte em andamento, ou começa uma nova
                if (atual.length === 0) {
                    atual.push(pontoEm(a, b, inicio));
                }

                atual.push(pontoEm(a, b, fim));
            }
        }
    }

    fechar();

    return cortou ? partes : [linha];
}