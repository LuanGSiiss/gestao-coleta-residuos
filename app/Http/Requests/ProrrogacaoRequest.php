<?php

namespace App\Http\Requests;

use App\Support\Cronograma;
use App\Support\Habilitacao;
use App\Models\ProgramacaoColeta;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProrrogacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programacao = $this->programacao();

        return [
            'data_fim' => [
                'required',
                'date_format:Y-m-d',
                'after:' . $programacao->data_fim->toDateString(),
                'before_or_equal:' . Cronograma::limite($programacao->data_fim)->toDateString(),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('data_fim')) {
                    return;
                }

                foreach ($this->impedimentos() as $impedimento) {
                    $validator->errors()->add('data_fim', $impedimento);
                }

                $programacao = $this->programacao();
                $datas = Cronograma::datas($programacao->inicioDaProrrogacao(), $this->date('data_fim'), $programacao->dias());

                if ($datas === []) {
                    $validator->errors()->add('data_fim', 'Nenhum dia de coleta da programação cai no período acrescentado.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['data_fim' => 'nova data de término'];
    }

    public function messages(): array
    {
        $programacao = $this->programacao();

        return [
            'data_fim.after'           => "A nova data de término deve ser posterior ao término vigente, {$programacao->data_fim->format('d/m/Y')}.",
            'data_fim.before_or_equal' => 'A prorrogação é limitada a 12 meses: até ' . Cronograma::limite($programacao->data_fim)->format('d/m/Y') . '.',
        ];
    }

    private function programacao(): ProgramacaoColeta
    {
        return $this->route('programacao');
    }

    /** Valida e retorna os impedimentos causados com a prorrogação. */
    private function impedimentos(): array
    {
        $programacao = $this->programacao()->load(['rota', 'motorista', 'veiculo', 'coletores']);

        $inativos = [];
        if (!$programacao->rota->ativo) {
            $inativos[] = "a rota {$programacao->rota->identificacao}";
        }
        if (!$programacao->motorista->ativo) {
            $inativos[] = "o motorista {$programacao->motorista->nome}";
        }
        if (!$programacao->veiculo->ativo) {
            $inativos[] = "o veículo {$programacao->veiculo->placa_formatada}";
        }

        foreach ($programacao->coletores as $coletor) {
            if (!$coletor->ativo) {
                $inativos[] = "o coletor {$coletor->nome}";
            }
        }

        $impedimentos = Habilitacao::impedimentos($programacao->motorista, $programacao->veiculo);

        if ($inativos !== []) {
            array_unshift(
                $impedimentos,
                'Antes de prorrogar, altere a programação para substituir o que está inativo: ' . implode(', ', $inativos) . '.'
            );
        }

        return $impedimentos;
    }
}