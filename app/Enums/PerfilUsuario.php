<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum PerfilUsuario: int
{
    use TemRotulo;

    case TECNICO   = 1;
    case GESTOR    = 2;
    case MOTORISTA = 3;

    public function rotulo(): string
    {
        return match ($this) {
            self::TECNICO   => 'Técnico',
            self::GESTOR    => 'Gestor',
            self::MOTORISTA => 'Motorista',
        };
    }

    /**
     * Retorna os perfis de administração.
     */
    public function administra(): bool
    {
        return $this === self::TECNICO;
    }

    /**
     * Retorna os perfis que acessam o planejamento e a programação.
     */
    public function gerencia(): bool
    {
        return in_array($this, [self::TECNICO, self::GESTOR], true);
    }
}