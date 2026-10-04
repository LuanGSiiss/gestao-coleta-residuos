<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Http\FormRequest;

class SenhaTemporariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'confirmed', 
                'min:8',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Hash::check($value, $this->user()->password)) {
                        $fail('A nova senha deve ser diferente da senha temporária.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'nova senha'];
    }
}