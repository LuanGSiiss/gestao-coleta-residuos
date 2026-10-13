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

    /** Retorna a cor usada na classe do badge do Bootstrap nas listagens. */
    public function cor(): string
    {
        return match ($this) {
            self::ATIVA     => 'success',
            self::ENCERRADA => 'secondary',
        };
    }
}