<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum SituacaoProgramacao: int
{
    use TemRotulo;

    case ATIVA     = 1;
    case ENCERRADA = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::ATIVA     => 'Ativa',
            self::ENCERRADA => 'Encerrada',
        };
    }
}