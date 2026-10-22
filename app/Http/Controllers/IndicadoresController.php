<?php

namespace App\Http\Controllers;

use Closure;

use App\Models\Rota;
use App\Support\Duracao;
use App\Support\Periodo;
use App\Enums\TipoColeta;
use Illuminate\View\View;
use App\Support\Indicadores;
use Illuminate\Http\Request;
use App\Enums\SituacaoExecucao;
use Illuminate\Support\Collection;

/**
 * Controller dos indicadores.
 */
class IndicadoresController extends Controller
{
    public function __invoke(Request $request): View
    {
        $periodo = Periodo::daRequisicao($request);
        $rotaId = $request->integer('rota') ?: null;
        $tipo = TipoColeta::tryFrom($request->integer('tipo'));

        $filtros = array_filter(['rota' => $rotaId, 'tipo' => $tipo?->value]);

        $indicadores = new Indicadores($periodo, $rotaId, $tipo);
        $resumo = $indicadores->resumo();
        $porRota = $indicadores->porRota();
        $porVersao = $indicadores->porVersao();

        return view('indicadores.index', [
            'periodo'    => $periodo,
            'atalhos'    => Periodo::atalhos(),
            'filtros'    => $filtros,
            'rotas'      => Rota::orderBy('identificacao')->get(),
            'tipos'      => TipoColeta::opcoes(),
            'alteracoes' => $indicadores->alteracoesDeTrajeto(),
            'coletas'    => (int) $resumo->coletas,
            'resumo'     => [
                'Coletas concluídas'       => number_format((int) $resumo->coletas, 0, ',', '.'),
                'Quantidade coletada'      => self::toneladas($resumo->quantidade_kg / 1000),
                'Tempo médio por coleta'   => self::duracao($resumo->minutos),
                'Quilometragem registrada' => self::quilometros($resumo->quilometragem),
            ],

            // Os quatro gráficos.
            'evolucao'   => self::evolucao($indicadores->porSemana(), $periodo),
            'quantidade' => [
                ...self::rotas($porRota),
                'unidade' => 't',
                'series'  => [
                    self::serie('Quantidade coletada', 'registrada', $porRota->map(fn ($linha) => $linha->quantidade_kg / 1000), fn ($t) => self::toneladas($t)),
                ],
            ],
            'tempo'      => [
                ...self::rotas($porRota),
                'unidade' => 'min',
                'series'  => [
                    self::serie('Previsto', 'prevista', $porRota->pluck('minutos_previstos'), fn ($m) => self::duracao($m)),
                    self::serie('Realizado', 'registrada', $porRota->pluck('minutos'), fn ($m) => self::duracao($m)),
                ],
            ],
            'distancia'  => [
                ...self::rotas($porVersao, comVersao: true),
                'unidade' => 'km',
                'series'  => [
                    self::serie('Extensão do trajeto', 'prevista', $porVersao->map(fn ($linha) => $linha->extensao_metros / 1000), fn ($km) => self::quilometros($km)),
                    self::serie('Quilometragem registrada', 'registrada', $porVersao->pluck('quilometragem_media'), fn ($km) => self::quilometros($km)),
                ],
            ],
        ]);
    }

    /**
     * Retorna os valores para o gráfico de barras utilizando as rotas, uma por linha do resultado.
     */
    private static function rotas(Collection $linhas, bool $comVersao = false): array
    {
        return [
            'tipo'     => 'barras',
            'coluna'   => 'Rota',
            'rotulos'  => $linhas->map(fn ($linha) => "{$linha->identificacao} — {$linha->nome}")->values()->all(),
            'curtos'   => $linhas->map(fn ($linha) => $comVersao ? "{$linha->identificacao} v{$linha->numero}" : $linha->identificacao)->values()->all(),
            'detalhes' => $linhas->map(fn ($linha) => ($comVersao ? "versão {$linha->numero} · " : '') . self::coletas((int) $linha->coletas))->values()->all(),
        ];
    }

    /**
     * Retorna os valores para o gráfico de evolução, apresentando a média por dia de cada semana do período, uma linha para cada tipo de coleta que aparece no resultado.
     */
    private static function evolucao(Collection $porSemana, Periodo $periodo): array
    {
        $semanas = $periodo->semanas();
        $series = [];

        foreach ($porSemana->groupBy('tipo_coleta')->sortKeys() as $valor => $linhas) {
            $tipo = TipoColeta::from($valor);
            $quilos = $linhas->pluck('quantidade_kg', 'semana');
            $porDia = [];
            $textos = [];

            foreach ($semanas as $semana) {
                $toneladas = $quilos->get($semana['segunda']->toDateString(), 0) / 1000;
                $porDia[] = $toneladas / $semana['dias'];
                $textos[] = $semana['dias'] === 1
                    ? self::toneladas($toneladas) . ' no dia'
                    : self::toneladas($toneladas / $semana['dias']) . ' por dia (' . self::toneladas($toneladas) . " em {$semana['dias']} dias)";
            }

            $series[] = [
                ...self::serie($tipo->rotulo(), mb_strtolower($tipo->name), collect($porDia), fn ($t) => self::toneladas($t)),
                'textos' => $textos
            ];
        }

        return [
            'tipo'    => 'evolucao',
            'coluna'  => 'Semana',
            'unidade' => 't',
            'rotulos' => array_map(fn (array $semana) => $semana['inicio']->format('d/m'), $semanas),
            'titulos' => array_map(fn (array $semana) => $semana['dias'] === 1
                ? 'Dia ' . $semana['inicio']->format('d/m/Y')
                : 'Semana de ' . $semana['inicio']->format('d/m') . ' a ' . $semana['fim']->format('d/m/Y'), $semanas),
            'series'  => $series,
        ];
    }

    /**
     * Retorna uma série com base nos paramêtros passados. Trazendo os números, que definem o tamanho de cada barra ou a altura de cada ponto, 
     * e os textos, que aparecem no gráfico e na tabela.
     */
    private static function serie(string $nome, string $cor, Collection $valores, Closure $formato): array
    {
        return [
            'nome'    => $nome,
            'cor'     => $cor,
            'valores' => $valores->map(fn ($valor) => round((float) $valor, 2))->values()->all(),
            'textos'  => $valores->map(fn ($valor) => $formato((float) $valor))->values()->all(),
        ];
    }

    /** Retorna o texto para a coleta, tratando quando for singular ou plural. */
    private static function coletas(int $quantidade): string
    {
        return $quantidade === 1 ? '1 coleta' : "{$quantidade} coletas";
    }

    /** Retorna o valor no formato "112,4 t" */
    private static function toneladas(float|string $toneladas): string
    {
        return number_format((float) $toneladas, 1, ',', '.') . ' t';
    }

    /** Retorna o valor no formato "23,5 km" */
    private static function quilometros(float|string $quilometros): string
    {
        return number_format((float) $quilometros, 1, ',', '.') . ' km';
    }

    /** Retorna o valor da duração formatado: 205.4 → "3 h 25 min". */
    private static function duracao(float|string|null $minutos): string
    {
        return $minutos === null ? '—' : Duracao::formatar((int) round((float) $minutos));
    }
}