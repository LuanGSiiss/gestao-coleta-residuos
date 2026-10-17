<?php

namespace App\Support;

use DateTimeInterface;

/**
 * O tempo total da execução, calculado do início e da conclusão reais.
 */
final class Duracao
{
    /**
     * Retorna os minutos inteiros entre dois momentos.
     */
    public static function minutosEntre(DateTimeInterface $inicio, DateTimeInterface $fim): int
    {
        return intdiv(abs($fim->getTimestamp() - $inicio->getTimestamp()), 60);
    }

    /** Retorna a duração no seguinte formato: 
     * 205 = "3 h 25 min"
     * 45 → "45 min"
     * 120 → "2 h"
     */
    public static function formatar(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return match (true) {
            $horas === 0 => "{$resto} min",
            $resto === 0 => "{$horas} h",
            default      => "{$horas} h {$resto} min",
        };
    }
}