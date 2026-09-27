<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class BairroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bairro = $this->route('bairro');

        return [
            'nome' => [
                'required',
                'string',
                'max:120',
                Rule::unique('bairro', 'nome')->ignore($bairro?->id),
            ],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'  => 'nome do bairro',
            'ativo' => 'situação',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe um bairro cadastrado com este nome.',
        ];
    }
}