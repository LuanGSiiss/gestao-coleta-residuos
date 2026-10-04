<?php

namespace App\Http\Requests;

use App\Models\Veiculo;
use App\Enums\TipoVeiculo;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Foundation\Http\FormRequest;

class VeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trata o valor recebido da placa, aceitando vários formatos como "abc-1234" ou "ABC 1D23" e variações. Mantém só letras e números.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'placa' => mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('placa'))),
        ]);
    }

    public function rules(): array
    {
        $veiculo = $this->route('veiculo');

        return [
            'modelo'   => ['required', 'string', 'max:80'],
            'marca_id' => ['required', 'integer', $this->regraMarca($veiculo)],
            'placa'    => [
                'required',
                'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', // Valida o formato
                Rule::unique('veiculo', 'placa')->ignore($veiculo?->id),
            ],
            'ano'                  => ['required', 'integer', 'between:1950,' . (now()->year + 1)],
            'tipo'                 => ['required', Rule::enum(TipoVeiculo::class)],
            'capacidade_toneladas' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'ativo'                => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'modelo'               => 'modelo',
            'marca_id'             => 'marca',
            'placa'                => 'placa',
            'ano'                  => 'ano',
            'tipo'                 => 'tipo',
            'capacidade_toneladas' => 'capacidade',
            'ativo'                => 'situação',
        ];
    }

    public function messages(): array
    {
        return [
            'placa.regex'     => 'Informe a placa no formato antigo (ABC-1234) ou Mercosul (ABC1D23).',
            'placa.unique'    => 'Já existe um veículo cadastrado com esta placa.',
            'marca_id.exists' => 'Selecione uma marca ativa.',
        ];
    }

    /**
     * Valida para que a marca esteja ativa ou já seja a marca deste veículo.
     */
    private function regraMarca(?Veiculo $veiculo): Exists
    {
        return Rule::exists('marca', 'id')->where(function ($query) use ($veiculo) {
            $query->where('ativo', 1);

            if ($veiculo) {
                $query->orWhere('id', $veiculo->marca_id);
            }
        });
    }
}