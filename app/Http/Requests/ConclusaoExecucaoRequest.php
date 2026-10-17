<?php

namespace App\Http\Requests;

use App\Models\Execucao;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ConclusaoExecucaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data'          => ['required', 'date_format:Y-m-d'],
            'hora'          => ['required', 'date_format:H:i'],
            'quilometragem' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:1000'],
            'quantidade_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000'],
            'observacoes'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['data', 'hora'])) {
                    return;
                }

                $termino = $this->termino();
                $inicio = $this->execucao()->iniciada_em;

                if ($termino->gt(now())) {
                    $validator->errors()->add('hora', 'O término não pode ser posterior ao momento atual.');
                } elseif ($inicio !== null && $termino->lte($inicio)) {
                    $validator->errors()->add('hora', 'O término deve ser posterior ao início, registrado em ' . "{$inicio->format('d/m/Y')} às {$inicio->format('H:i')}.");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'data'          => 'data de término',
            'hora'          => 'hora de término',
            'quilometragem' => 'quilometragem',
            'quantidade_kg' => 'quantidade coletada',
            'observacoes'   => 'observações',
        ];
    }

    public function messages(): array
    {
        return [
            'quilometragem.gt'      => 'Informe a quilometragem percorrida, maior que zero.',
            'quilometragem.max'     => 'A quilometragem passa de 1.000 km. Confira o valor digitado.',
            'quantidade_kg.max'     => 'A quantidade passa de 100.000 kg. Confira o valor digitado.',
            'quilometragem.decimal' => 'Use no máximo duas casas decimais.',
            'quantidade_kg.decimal' => 'Use no máximo duas casas decimais.',
        ];
    }

    public function termino(): CarbonImmutable
    {
        return CarbonImmutable::parse("{$this->input('data')} {$this->input('hora')}");
    }

    private function execucao(): Execucao
    {
        return $this->route('execucao');
    }
}