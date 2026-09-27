<?php

namespace App\Enums\Concerns;

trait TemRotulo
{
    /**
     * Retorna as opções em formato de lista, seguindo a forma [valor => rótulo].
     */
    public static function opcoes(): array
    {
        $opcoes = [];

        foreach (self::cases() as $caso) {
            $opcoes[$caso->value] = $caso->rotulo();
        }

        return $opcoes;
    }
}