<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Execucao;
use App\Support\Alocacao;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use App\Support\TravaExecucoes;
use App\Enums\SituacaoExecucao;
use Illuminate\Support\Facades\DB;
use App\Exceptions\OperacaoRecusada;

/**
 * Responsável pelos comportamentos que manipulam execuções.
 */
final class ExecucaoService
{
    public function __construct(private readonly ConflitoAlocacao $conflitos)
    {
    }

    /**
     * Altera a execução
     */
    public function alterar(Execucao $execucao, array $dados, array $coletores): void
    {
        DB::transaction(function () use ($execucao, $dados, $coletores) {
            TravaExecucoes::adquirir();
            $execucao->refresh()->load('coletores');
            $this->confirmar($execucao->impedimentoParaAlterar());

            $antes = $execucao->dadosPrevistos();
            $execucao->fill($dados);
            $depois = $execucao->dadosPrevistos($coletores);

            if (self::mudouAlocacao($antes, $depois)) {
                $this->conflitos->verificar(
                    [Alocacao::deDadosPrevistos($execucao->data->toDateString(), $depois)],
                    $execucao->programacao_coleta_id,
                );
            }

            $execucao->save();
            $execucao->coletores()->sync($depois['coletores']);
        });
    }

    /** Inicia a execução, alterando a situação para "Em Andamento" e informando datahora de inicio. */
    public function iniciar(Execucao $execucao, CarbonInterface $inicio): void
    {
        DB::transaction(function () use ($execucao, $inicio) {
            TravaExecucoes::adquirir();
            $execucao->refresh();
            $this->confirmar($execucao->impedimentoParaIniciar());

            $execucao->situacao = SituacaoExecucao::EM_ANDAMENTO;
            $execucao->iniciada_em = $inicio;
            $execucao->save();
        });
    }

    /** Conclui a execução, informando a data de termino, quilometragem, quantidade coletada e observações. */
    public function concluir(Execucao $execucao, CarbonInterface $termino, string $quilometragem, string $quantidadeKg, ?string $observacoes): void 
    {
        DB::transaction(function () use ($execucao, $termino, $quilometragem, $quantidadeKg, $observacoes) {
            TravaExecucoes::adquirir();
            $execucao->refresh();
            $this->confirmar($execucao->impedimentoParaConcluir());

            $execucao->situacao = SituacaoExecucao::CONCLUIDA;
            $execucao->concluida_em = $termino;
            $execucao->quilometragem = $quilometragem;
            $execucao->quantidade_kg = $quantidadeKg;
            $execucao->observacoes = $observacoes;
            $execucao->save();
        });
    }

    /** Cancela a execução, informando o motivo, usuário e datahora. */
    public function cancelar(Execucao $execucao, string $motivo, Usuario $usuario): void
    {
        DB::transaction(function () use ($execucao, $motivo, $usuario) {
            TravaExecucoes::adquirir();
            $execucao->refresh();
            $this->confirmar($execucao->impedimentoParaCancelar());

            $execucao->situacao = SituacaoExecucao::CANCELADA;
            $execucao->motivo_cancelamento = self::motivoComAutor($motivo, $usuario->nome, CarbonImmutable::now());
            $execucao->save();
        });
    }

    /** Retorna o texto utilizado ao cancelar uma execução. */
    public static function motivoComAutor(string $motivo, string $autor, CarbonInterface $momento): string
    {
        return "Cancelada em {$momento->format('d/m/Y')} às {$momento->format('H:i')}, por {$autor}: {$motivo}";
    }

    /**
     * Valida se mudou os dados que podem gerar conflito.
     */
    public static function mudouAlocacao(array $antes, array $depois): bool
    {
        unset($antes['orientacoes'], $depois['orientacoes']);

        return $antes !== $depois;
    }

    /** Valida se é permitido utilizar a ação. */
    private function confirmar(?string $impedimento): void
    {
        if ($impedimento !== null) {
            throw new OperacaoRecusada($impedimento);
        }
    }
}