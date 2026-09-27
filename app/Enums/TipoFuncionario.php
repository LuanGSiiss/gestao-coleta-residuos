<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum TipoFuncionario: int
{
    use TemRotulo;

    case MOTORISTA = 1;
    case COLETOR   = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::MOTORISTA => 'Motorista',
            self::COLETOR   => 'Coletor',
        };
    }
}