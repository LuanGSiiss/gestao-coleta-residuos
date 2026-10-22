/*
 * Realiza a configuração de cada tipo de gráfico dos indicadores. Possui funções que só montam o objeto
 * que o Chart.js recebe.
 *
 * Dados:
 *   tipo      'barras' ou 'evolucao'
 *   unidade   't', 'min' ou 'km'; na evolução, vai nos números do eixo
 *   rotulos   o nome de cada categoria.
 *   curtos    nas barras, o nome curto da rota, para telas estreitas
 *   detalhes  nas barras, a segunda linha do rótulo: o número de coletas
 *   titulos   na evolução, o título da dica de cada semana
 *   series    [{ nome, cor, valores, textos }]: os números desenham, os textos aparecem
 */

const numero = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });

/** Define o valor de pixels para largura estreita. */
const LARGURA_ESTREITA = 576;

/**
 * Retorna o valor formatado com base na unidade passada, como "20 t" ou "1.234,5 t".
 */
export function formatarEixo(valor, unidade) {
    return unidade === 'min' ? `${numero.format(valor / 60)} h` : `${numero.format(valor)} ${unidade}`;
}

/** A legenda só aparece com duas séries; com uma, o título do gráfico já diz o que é. */
function legenda(dados, deLinha) {
    return {
        display: dados.series.length > 1,
        position: 'top',
        align: 'start',
        labels: { boxWidth: deLinha ? 16 : 12, boxHeight: deLinha ? 2 : 12 },
    };
}

/** Retorna os dados para o graficos de barras deitadas, uma por rota (ou por versão do trajeto), com o valor escrito na ponta. */
function barras(dados, aparencia) {
    return {
        type: 'bar',
        data: {
            labels: dados.rotulos,
            datasets: dados.series.map((serie) => ({
                label: serie.nome,
                data: serie.valores,
                textos: serie.textos,
                backgroundColor: aparencia.cores[serie.cor],
                borderRadius: 4,
                maxBarThickness: 18,
                categoryPercentage: 0.8,
                barPercentage: 0.9,
            })),
        },
        options: {
            indexAxis: 'y',
            // A altura vem da caixa em volta, calculada pela tela conforme o número de rotas.
            maintainAspectRatio: false,
            animation: { duration: 400 },
            // Com o mouse sobre uma barra, a dica traz as duas séries daquela rota, lado a lado.
            interaction: { mode: 'index', axis: 'y' },
            scales: {
                x: eixoDosValores(dados, aparencia),
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: aparencia.texto,
                        autoSkip: false,
                        callback(valor, indice) {
                            const nome = this.chart.width < LARGURA_ESTREITA ? dados.curtos[indice] : dados.rotulos[indice];

                            return [nome, dados.detalhes[indice]];
                        },
                    },
                },
            },
            plugins: {
                legend: legenda(dados, false),
                tooltip: {
                    callbacks: {
                        title: (itens) => dados.rotulos[itens[0].dataIndex],
                        label: (item) => `${item.dataset.label}: ${item.dataset.textos[item.dataIndex]}`,
                        footer: (itens) => dados.detalhes[itens[0].dataIndex],
                    },
                },
            },
        },
    };
}

/** Retorna os dados para o grafico de linhas ao longo das semanas do período, uma por tipo de coleta. */
function evolucao(dados, aparencia) {
    return {
        type: 'line',
        data: {
            labels: dados.rotulos,
            datasets: dados.series.map((serie) => ({
                label: serie.nome,
                data: serie.valores,
                textos: serie.textos,
                borderColor: aparencia.cores[serie.cor],
                backgroundColor: aparencia.cores[serie.cor],
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBorderColor: aparencia.fundo,
                pointBorderWidth: 2,
                tension: 0,
            })),
        },
        options: {
            maintainAspectRatio: false,
            animation: { duration: 400 },
            // A dica mostra as duas séries da semana sob o mouse, sem precisar acertar o ponto.
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: aparencia.texto, maxRotation: 0, autoSkipPadding: 12 },
                },
                y: eixoDosValores(dados, aparencia),
            },
            plugins: {
                legend: legenda(dados, true),
                tooltip: {
                    callbacks: {
                        title: (itens) => dados.titulos[itens[0].dataIndex],
                        label: (item) => `${item.dataset.label}: ${item.dataset.textos[item.dataIndex]}`,
                    },
                },
            },
        },
    };
}

const MONTADORES = { barras, evolucao };

/**
 * Realiza a configuração completa de um gráfico.
 */
export function configurar(tipo, dados, aparencia) {
    const montar = MONTADORES[tipo];

    if (!montar) {
        throw new Error(`Tipo de gráfico desconhecido: ${tipo}`);
    }

    return montar(dados, aparencia);
}