<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoVeiculo: int
{
    use TemRotulo;

    case COMPACTADOR       = 1;
    case CARROCERIA_ABERTA = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::COMPACTADOR       => 'Compactador',
            self::CARROCERIA_ABERTA => 'Carroceria aberta',
        };
    }
}