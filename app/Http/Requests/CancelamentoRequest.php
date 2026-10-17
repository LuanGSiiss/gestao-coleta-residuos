<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['motivo' => 'motivo do cancelamento'];
    }

    public function messages(): array
    {
        return ['motivo.required' => 'Informe o motivo do cancelamento.'];
    }
}