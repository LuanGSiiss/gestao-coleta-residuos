<?php

namespace App\Models;

use App\Casts\Hora;
use App\Enums\DiaSemana;
use Carbon\CarbonImmutable;
use App\Enums\SituacaoExecucao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Execucao extends Model
{
    protected $table = 'execucao';

    public $timestamps = false;

    protected $fillable = [
        'hora_inicio_prevista',
        'hora_fim_prevista',
        'motorista_id',
        'veiculo_id',
        'rota_versao_id',
        'orientacoes',
    ];

    protected function casts(): array
    {
        return [
            'data'                 => 'immutable_date',
            'hora_inicio_prevista' => Hora::class,
            'hora_fim_prevista'    => Hora::class,
            'situacao'             => SituacaoExecucao::class,
            'iniciada_em'          => 'immutable_datetime',
            'concluida_em'         => 'immutable_datetime',
            'quilometragem'        => 'decimal:2',
            'quantidade_kg'        => 'decimal:2',
        ];
    }

    public function programacao(): BelongsTo
    {
        return $this->belongsTo(ProgramacaoColeta::class, 'programacao_coleta_id');
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'motorista_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function rotaVersao(): BelongsTo
    {
        return $this->belongsTo(RotaVersao::class);
    }

    public function coletores(): BelongsToMany
    {
        return $this->belongsToMany(Funcionario::class, 'execucao_coletor')->orderBy('funcionario.nome');
    }

    public function scopeNaoIniciadas(Builder $query): Builder
    {
        return $query->where('execucao.situacao', SituacaoExecucao::NAO_INICIADA->value);
    }

    /**
     * Retorna as execuções cujo o início previsto ainda não chegou.
     */
    public function scopeFuturas(Builder $query): Builder
    {
        $agora = now();

        return $query
            ->where('execucao.data', '>=', $agora->toDateString())
            ->whereRaw('(execucao.data + execucao.hora_inicio_prevista) > ?', [$agora->toDateTimeString()]);
    }

    // Retorna as execuções não iniciadas e que o início previsto já passou.
    public function scopeAtrasadas(Builder $query): Builder
    {
        return $query
            ->where('execucao.situacao', SituacaoExecucao::NAO_INICIADA->value)
            ->whereRaw('(execucao.data + execucao.hora_inicio_prevista) <= ?', [now()->toDateTimeString()]);
    }

    public function inicioPrevisto(): CarbonImmutable
    {
        return $this->data->setTimeFromTimeString($this->hora_inicio_prevista);
    }

    public function atrasada(): bool
    {
        return $this->situacao === SituacaoExecucao::NAO_INICIADA && $this->inicioPrevisto()->lte(now());
    }

    public function diaSemana(): DiaSemana
    {
        return DiaSemana::daData($this->data);
    }

    /**
     * Retorna os dados que a programação define para cada execução, no mesmo formato de ProgramacaoColeta::dadosPrevistos.
     * Utilizado para comparar se uma execução teve os dados alterados em comparação com a programação.
     */
    public function dadosPrevistos(): array
    {
        $coletores = $this->coletores->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($coletores);

        return [
            'motorista_id'         => (int) $this->motorista_id,
            'veiculo_id'           => (int) $this->veiculo_id,
            'hora_inicio_prevista' => $this->hora_inicio_prevista,
            'hora_fim_prevista'    => $this->hora_fim_prevista,
            'orientacoes'          => $this->orientacoes,
            'coletores'            => $coletores,
        ];
    }
}