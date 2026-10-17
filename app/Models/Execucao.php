<?php

namespace App\Models;

use App\Casts\Hora;
use App\Enums\DiaSemana;
use App\Support\Duracao;
use Carbon\CarbonImmutable;
use App\Enums\SituacaoExecucao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(Ocorrencia::class)->orderBy('data_hora')->orderBy('id');
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

    /**
     * Retorna as execuções que o motorista pode ver, sendo as que ele é motorista e as de hoje ou em andamento.
     */
    public function scopeDoMotorista(Builder $query, int $funcionarioId): Builder
    {
        return $query
            ->where('execucao.motorista_id', $funcionarioId)
            ->where(fn (Builder $query) => $query
                ->where('execucao.data', today()->toDateString())
                ->orWhere('execucao.situacao', SituacaoExecucao::EM_ANDAMENTO->value));
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

    /** Retorna o impedimento para alterar a execução, caso exista. */
    public function impedimentoParaAlterar(): ?string
    {
        if ($this->situacao !== SituacaoExecucao::NAO_INICIADA) {
            return 'Esta execução está ' . mb_strtolower($this->situacao->rotulo()) . ' e não pode mais ser alterada.';
        }

        if ($this->data->lt(today())) {
            return 'Esta execução é de uma data passada e não pode mais ser alterada.';
        }

        return null;
    }

    /** Retorna o impedimento para iniciar a execução, caso exista. */
    public function impedimentoParaIniciar(): ?string
    {
        if (!$this->situacao->podeIrPara(SituacaoExecucao::EM_ANDAMENTO)) {
            return 'Esta coleta está ' . mb_strtolower($this->situacao->rotulo()) . ' e não pode ser iniciada.';
        }

        if (!$this->data->isToday()) {
            return 'A coleta só pode ser iniciada na data prevista, ' . $this->data->format('d/m/Y') . '.';
        }

        return null;
    }

    /** Retorna o impedimento para concluir a execução, caso exista. */
    public function impedimentoParaConcluir(): ?string
    {
        return $this->situacao->podeIrPara(SituacaoExecucao::CONCLUIDA) ? null : 'Só uma coleta em andamento pode ser concluída, e esta está ' . mb_strtolower($this->situacao->rotulo()) . '.';
    }

    /** Retorna o impedimento para cancelar a execução, caso exista. */
    public function impedimentoParaCancelar(): ?string
    {
        return $this->situacao->podeIrPara(SituacaoExecucao::CANCELADA) ? null : 'Esta execução já está ' . mb_strtolower($this->situacao->rotulo()) . ', e essa situação é final.';
    }

    /** Retorna o impedimento para registrar uma ocorrência para a execução, caso exista. */
    public function impedimentoParaOcorrencia(): ?string
    {
        return $this->data->gt(today()) ? 'Ocorrências registram o que já aconteceu, e esta coleta é de ' . $this->data->format('d/m/Y') . '.' : null;
    }

    /**
     * Registra uma ocorrência para execução. 
     * O autor é o próprio usuário logado.
     */
    public function registrarOcorrencia(array $dados, Usuario $autor): Ocorrencia
    {
        $ocorrencia = new Ocorrencia($dados);
        $ocorrencia->usuario()->associate($autor);

        return $this->ocorrencias()->save($ocorrencia);
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

    /** Retorna o tempo total da execução no formato "3 h 25 min", somente as informações de iniciada_em e concluida_em.*/
    protected function tempoTotal(): Attribute
    {
        return Attribute::get(fn () => $this->iniciada_em && $this->concluida_em
            ? Duracao::formatar(Duracao::minutosEntre($this->iniciada_em, $this->concluida_em))
            : null);
    }

    /** Retorna a quilometragem no formato "45,30 km". */
    protected function quilometragemFormatada(): Attribute
    {
        return Attribute::get(fn () => $this->quilometragem === null
            ? null
            : number_format((float) $this->quilometragem, 2, ',', '.') . ' km');
    }

    /** Retorna a quantidade no formato "8.450,00 kg". */
    protected function quantidadeFormatada(): Attribute
    {
        return Attribute::get(fn () => $this->quantidade_kg === null
            ? null
            : number_format((float) $this->quantidade_kg, 2, ',', '.') . ' kg');
    }
}