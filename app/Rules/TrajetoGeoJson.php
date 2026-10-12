<?php

namespace App\Rules;

use Closure;
use InvalidArgumentException;

use App\Support\Trajeto;
use Illuminate\Contracts\Validation\ValidationRule;

class TrajetoGeoJson implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O trajeto enviado é inválido.');
            return;
        }

        try {
            Trajeto::deGeoJson($value);
        } catch (InvalidArgumentException $erro) {
            $fail($erro->getMessage());
        }
    }
}