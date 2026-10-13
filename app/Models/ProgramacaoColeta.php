<?php

namespace App\Models;

use App\Casts\Hora;
use App\Enums\DiaSemana;
use App\Enums\TipoColeta;
use Carbon\CarbonImmutable;
use App\Enums\SituacaoProgramacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgramacaoColeta extends Model
{
    protected $table = 'programacao_coleta';

    protected $fillable = [
        'descricao',
        'rota_id',
        'motorista_id',
        'veiculo_id',
        'tipo_coleta',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'hora_fim',
        'orientacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo_coleta'  => TipoColeta::class,
            'situacao'     => SituacaoProgramacao::class,
            'data_inicio'  => 'immutable_date',
            'data_fim'     => 'immutable_date',
            'hora_inicio'  => Hora::class,
            'hora_fim'     => Hora::class,
            'encerrada_em' => 'immutable_datetime',
        ];
    }

    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'motorista_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function coletores(): BelongsToMany
    {
        return $this->belongsToMany(Funcionario::class, 'programacao_coleta_coletor')->orderBy('funcionario.nome');
    }

    public function diasSemana(): HasMany
    {
        return $this->hasMany(ProgramacaoColetaDiaSemana::class)->orderBy('dia_semana');
    }

    public function execucoes(): HasMany
    {
        return $this->hasMany(Execucao::class);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('programacao_coleta.situacao', SituacaoProgramacao::ATIVA->value);
    }

    public function ativa(): bool
    {
        return $this->situacao === SituacaoProgramacao::ATIVA;
    }

    public function dias(): array
    {
        return $this->diasSemana->pluck('dia_semana')->all();
    }

    /**
     * Retorna o que a programação define para cada execução, com os nomes das colunas da execução.
     * Utilizado para comparar se uma execução teve os dados alterados em comparação com a programação.
     *
     * @param array $coletores Os coletores recebidos do formulário.
     */
    public function dadosPrevistos(?array $coletores = null): array
    {
        $coletores = array_map('intval', $coletores ?? $this->coletores()->pluck('funcionario.id')->all());
        sort($coletores);

        return [
            'motorista_id'         => (int) $this->motorista_id,
            'veiculo_id'           => (int) $this->veiculo_id,
            'hora_inicio_prevista' => $this->hora_inicio,
            'hora_fim_prevista'    => $this->hora_fim,
            'orientacoes'          => $this->orientacoes,
            'coletores'            => $coletores,
        ];
    }

    /**
     * Retorna o dia de inicio da prorrogação, sendo o proximo dia após a data fim da programação ou a data atual.
     */
    public function inicioDaProrrogacao(): CarbonImmutable
    {
        $diaSeguinte = $this->data_fim->addDay();
        $hoje = CarbonImmutable::today();

        return $diaSeguinte->gt($hoje) ? $diaSeguinte : $hoje;
    }

    /** Retorna os dias da programação abreviados. */
    protected function diasAbreviados(): Attribute
    {
        return Attribute::get(fn () => implode(', ', array_map(
            fn (DiaSemana $dia) => $dia->abreviado(),
            $this->dias(),
        )));
    }

    /** Retorna o horário no formato "07:00 às 12:00" */
    protected function horario(): Attribute
    {
        return Attribute::get(fn () => "{$this->hora_inicio} às {$this->hora_fim}");
    }

    /** Retorna o horário no formato "12/10/2026 a 11/10/2027" */
    protected function periodo(): Attribute
    {
        return Attribute::get(
            fn () => $this->data_inicio->format('d/m/Y') . ' a ' . $this->data_fim->format('d/m/Y')
        );
    }
}