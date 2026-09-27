<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $marca = $this->route('marca');

        return [
            'nome' => [
                'required',
                'string',
                'max:60',
                Rule::unique('marca', 'nome')->ignore($marca?->id),
            ],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'  => 'nome da marca',
            'ativo' => 'situação',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe uma marca cadastrada com este nome.',
        ];
    }
}
