<?php

namespace App\Support;

use App\Enums\TipoColeta;
use App\Models\RotaVersao;
use App\Enums\SituacaoExecucao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Classe para os comportamentos dos indicadores.
 * Realiza as consultas dos valores e no banco e devolve os valores para serem utilizados nos indicadores.
 */
final class Indicadores
{
    private const MINUTOS_REALIZADOS = 'extract(epoch from e.concluida_em - e.iniciada_em) / 60';
    private const MINUTOS_PREVISTOS = 'extract(epoch from e.hora_fim_prevista - e.hora_inicio_prevista) / 60';

    public function __construct(
        private readonly Periodo $periodo,
        private readonly ?int $rotaId = null,
        private readonly ?TipoColeta $tipo = null,
    ) {
    }

    /** Retorna os os números do período inteiro, para o resumo no topo da tela. 
     * Trazendo:
     * - total de coletas,
     * - quantidade total em kg.
     * - quilometragem total.
     * - tempo médio de realização das coletas.
    */
    public function resumo(): object
    {
        return $this->concluidas()
            ->selectRaw('count(*) as coletas')
            ->selectRaw('coalesce(sum(e.quantidade_kg), 0) as quantidade_kg')
            ->selectRaw('coalesce(sum(e.quilometragem), 0) as quilometragem')
            ->selectRaw('avg(' . self::MINUTOS_REALIZADOS . ') as minutos')
            ->first();
    }

    /**
     * Retorna os valores por rota, uma linha por rota.
     * Trazendo: 
     * - total de coletas, 
     * - quantidade total em kg, 
     * - tempo médio de realização das coletas.
     * - tempo médio previsto.
     */
    public function porRota(): Collection
    {
        return $this->concluidas()
            ->select('r.id as rota_id', 'r.identificacao', 'r.nome', 'r.cor')
            ->selectRaw('count(*) as coletas')
            ->selectRaw('sum(e.quantidade_kg) as quantidade_kg')
            ->selectRaw('avg(' . self::MINUTOS_PREVISTOS . ') as minutos_previstos')
            ->selectRaw('avg(' . self::MINUTOS_REALIZADOS . ') as minutos')
            ->groupBy('r.id', 'r.identificacao', 'r.nome', 'r.cor')
            ->orderBy('r.identificacao')
            ->get();
    }

    /**
     * Retorna os valores por versão do trajeto. 
     * Trazendo:
     * - total de coletas, 
     * - média de quilometragem, 
     */
    public function porVersao(): Collection
    {
        return $this->concluidas()
            ->select('r.id as rota_id', 'r.identificacao', 'r.nome', 'r.cor', 'v.numero', 'v.extensao_metros')
            ->selectRaw('count(*) as coletas')
            ->selectRaw('avg(e.quilometragem) as quilometragem_media')
            ->groupBy('r.id', 'r.identificacao', 'r.nome', 'r.cor', 'v.id', 'v.numero', 'v.extensao_metros')
            ->orderBy('r.identificacao')
            ->orderBy('v.numero')
            ->get();
    }

    /**
     * Retorna os valores do quanto foi coletado em cada semana do período, por tipo de coleta. A semana começa na segunda-feira.
     */
    public function porSemana(): Collection
    {
        return $this->concluidas()
            ->selectRaw("date_trunc('week', e.data)::date as semana")
            ->addSelect('p.tipo_coleta')
            ->selectRaw('count(*) as coletas')
            ->selectRaw('sum(e.quantidade_kg) as quantidade_kg')
            ->groupBy('semana', 'p.tipo_coleta')
            ->orderBy('semana')
            ->orderBy('p.tipo_coleta')
            ->get();
    }

    /**
     * Retorna as alterações de trajeto feitas dentro do período, para a tela sinalizar a data.
     */
    public function alteracoesDeTrajeto(): EloquentCollection
    {
        return RotaVersao::query()
            ->with('rota')
            ->where('numero', '>', 1)
            ->where('criada_em', '>=', $this->periodo->de)
            ->where('criada_em', '<', $this->periodo->ate->addDay())
            ->whereIn('rota_id', $this->concluidas()->select('r.id'))
            ->orderBy('criada_em')
            ->get();
    }

    /**
     * Estrutura base de todas as consultas, retornado os dados só das execuções concluídas, com data no período, a
     * rota a que pertence a versão do trajeto de cada uma e a programação, que tem o tipo de coleta.
     */
    private function concluidas(): Builder
    {
        return DB::table('execucao as e')
            ->join('rota_versao as v', 'v.id', '=', 'e.rota_versao_id')
            ->join('rota as r', 'r.id', '=', 'v.rota_id')
            ->join('programacao_coleta as p', 'p.id', '=', 'e.programacao_coleta_id')
            ->where('e.situacao', SituacaoExecucao::CONCLUIDA->value)
            ->whereBetween('e.data', [$this->periodo->de->toDateString(), $this->periodo->ate->toDateString()])
            ->when($this->rotaId, fn (Builder $query, int $rotaId) => $query->where('r.id', $rotaId))
            ->when($this->tipo, fn (Builder $query, TipoColeta $tipo) => $query->where('p.tipo_coleta', $tipo->value));
    }
}