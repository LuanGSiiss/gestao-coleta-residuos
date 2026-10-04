<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validam do CPF
 */
class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        // Valida se o Cpf tem onze números e se não são os mesmos números repetidos.
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O :attribute informado não é válido.');
            return;
        }

        /** 
         * Faz a validam do CPF com base nos digitos verificadores.
         * Os dois últimos dígitos são verificadores. Cada um é calculado a partir de todos os anteriores, com pesos decrescentes que terminam em 2.
         */
        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digito) {
                $fail('O :attribute informado não é válido.');
                return;
            }
        }
    }
}