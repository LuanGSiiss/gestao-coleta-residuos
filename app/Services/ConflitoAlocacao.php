<?php

namespace App\Services;

use App\Models\Veiculo;
use App\Models\Execucao;
use App\Support\Alocacao;
use App\Models\Funcionario;
use App\Enums\SituacaoExecucao;
use Illuminate\Validation\ValidationException;

/**
 * Responsável por comparar as alocações planejadas com as execuções existentes.
 */
final class ConflitoAlocacao
{
    /**
     * @param Alocacao[] $plano Como as execuções vão ficar.
     * @param int|null $programacaoIgnorada Id da própria programação, ao alterar ou prorrogar.
     * @param string|null $campo Nome do campo que recebe as mensagens.
     * @throws ValidationException
     */
    public function verificar(array $plano, ?int $programacaoIgnorada = null, ?string $campo = null): void
    {
        $conflitos = $this->encontrar($plano, $programacaoIgnorada);

        if ($conflitos !== []) {
            throw ValidationException::withMessages($this->mensagens($conflitos, $campo));
        }
    }

    /**
     * @param  Alocacao[] $plano
     */
    private function encontrar(array $plano, ?int $programacaoIgnorada): array
    {
        if ($plano === []) {
            return [];
        }

        $datas = array_map(fn (Alocacao $alocacao) => $alocacao->data, $plano);

        $funcionarios = array_values(array_unique(array_merge(
            ...array_map(fn (Alocacao $alocacao) => $alocacao->funcionarios(), $plano)
        )));

        $veiculos = array_values(array_unique(
            array_map(fn (Alocacao $alocacao) => $alocacao->veiculoId, $plano)
        ));

        // Busca os registros no o intervalo do plano, não canceladas, de programação ativa e com algum funcionário ou veículo do plano.
        $existentes = Execucao::query()
            ->with(['coletores', 'programacao'])
            ->whereBetween('data', [min($datas), max($datas)])
            ->where('situacao', '<>', SituacaoExecucao::CANCELADA->value)
            ->whereHas('programacao', fn ($programacao) => $programacao->ativas())
            ->when($programacaoIgnorada, fn ($query, $id) => $query->where('programacao_coleta_id', '<>', $id))
            ->where(fn ($query) => $query
                ->whereIn('motorista_id', $funcionarios)
                ->orWhereIn('veiculo_id', $veiculos)
                ->orWhereHas('coletores', fn ($coletores) => $coletores->whereIn('funcionario.id', $funcionarios)))
            ->get()
            ->groupBy(fn (Execucao $execucao) => $execucao->data->toDateString());

        $conflitos = [];
        foreach ($plano as $alocacao) {
            foreach ($existentes->get($alocacao->data, []) as $execucao) {
                $existente = Alocacao::deDadosPrevistos($alocacao->data, $execucao->dadosPrevistos());

                foreach ($alocacao->recursosEmConflito($existente) as $recurso) {
                    $conflitos[] = $recurso + ['alocacao' => $alocacao, 'execucao' => $execucao];
                }
            }
        }

        return $conflitos;
    }

    /**
     * Retorna as mensagens dos conflitos. Sendo uma mensagem por recurso, com o recurso, a programação conflitante, a primeira
     * data do conflito e quantas datas mais ele atinge.
     */
    private function mensagens(array $conflitos, ?string $campo): array
    {
        usort($conflitos, fn (array $a, array $b) =>
            [$a['alocacao']->data, $a['alocacao']->horaInicio] <=> [$b['alocacao']->data, $b['alocacao']->horaInicio]);

        $porRecurso = [];

        foreach ($conflitos as $conflito) {
            $porRecurso["{$conflito['tipo']}-{$conflito['id']}"][] = $conflito;
        }

        $ids = fn (string $tipo) => array_column(array_filter($conflitos, fn (array $c) => $c['tipo'] === $tipo), 'id');
        $funcionarios = Funcionario::whereKey($ids('funcionario'))->get()->keyBy('id');
        $veiculos = Veiculo::whereKey($ids('veiculo'))->get()->keyBy('id');

        $mensagens = [];
        foreach ($porRecurso as $lista) {
            ['tipo' => $tipo, 'id' => $id, 'alocacao' => $alocacao, 'execucao' => $execucao] = $lista[0];

            $recurso = $tipo === 'veiculo'
                ? "O veículo {$veiculos[$id]->placa_formatada}"
                : 'O ' . mb_strtolower($funcionarios[$id]->tipo->rotulo()) . " {$funcionarios[$id]->nome}";

            $outrasDatas = count(array_unique(array_map(fn (array $c) => $c['alocacao']->data, $lista))) - 1;

            $mensagens[$campo ?? $this->campo($tipo, $id, $alocacao)][] = sprintf(
                '%s já está alocado na programação %d — %s: em %s, das %s às %s%s.',
                $recurso,
                $execucao->programacao->id,
                $execucao->programacao->descricao,
                $execucao->data->format('d/m/Y'),
                $execucao->hora_inicio_prevista,
                $execucao->hora_fim_prevista,
                match ($outrasDatas) {
                    0       => '',
                    1       => ', e em mais 1 data',
                    default => ", e em mais {$outrasDatas} datas",
                },
            );
        }

        return $mensagens;
    }

    /** Em que campo do formulário a mensagem aparece. */
    private function campo(string $tipo, int $id, Alocacao $alocacao): string
    {
        return match (true) {
            $tipo === 'veiculo'            => 'veiculo_id',
            $id === $alocacao->motoristaId => 'motorista_id',
            default                        => 'coletores',
        };
    }
}