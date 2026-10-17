<?php

namespace App\Http\Requests\Concerns;

use App\Models\Veiculo;
use App\Models\Funcionario;
use App\Support\Habilitacao;
use App\Enums\TipoFuncionario;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Exists;

/**
 * Regras de alocação de equipe e veículo, comuns a programação e a execução.
 */
trait ValidaAlocacao
{
    /** Valida para que o funcionário seja do tipo certo e ativo. */
    protected function regraFuncionario(TipoFuncionario $tipo, array $vinculados = []): Exists
    {
        return Rule::exists('funcionario', 'id')
            ->where('tipo', $tipo->value)
            ->where(function ($query) use ($vinculados) {
                $query->where('ativo', 1);

                if ($vinculados !== []) {
                    $query->orWhereIn('id', $vinculados);
                }
            });
    }

    /** Valida para que o veículo esteja ativo. */
    protected function regraVeiculo(?int $vinculado = null): Exists
    {
        return Rule::exists('veiculo', 'id')->where(function ($query) use ($vinculado) {
            $query->where('ativo', 1);

            if ($vinculado !== null) {
                $query->orWhere('id', $vinculado);
            }
        });
    }

    /**
     * Valida o periodo informando. Garantindo que o motorista não possua CNH vencida e tenha categoria C ou superior quando for um veiculo compactador. 
     * Se não for alterado os dados de motorista ou veiculo, apenas exibe um aviso.
     */
    protected function validarHabilitacao(Validator $validator, ?int $motoristaAtual = null, ?int $veiculoAtual = null): void
    {
        if ($validator->errors()->hasAny(['motorista_id', 'veiculo_id'])) {
            return;
        }

        $motoristaId = $this->integer('motorista_id');
        $veiculoId = $this->integer('veiculo_id');

        if ($motoristaId === $motoristaAtual && $veiculoId === $veiculoAtual) {
            return;
        }

        $impedimentos = Habilitacao::impedimentos(
            Funcionario::findOrFail($motoristaId),
            Veiculo::findOrFail($veiculoId),
        );

        foreach ($impedimentos as $impedimento) {
            $validator->errors()->add('motorista_id', $impedimento);
        }
    }
}