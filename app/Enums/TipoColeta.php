<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoColeta: int
{
    use TemRotulo;

    case CONVENCIONAL = 1;
    case SELETIVA     = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::CONVENCIONAL => 'Convencional',
            self::SELETIVA     => 'Seletiva',
        };
    }
}