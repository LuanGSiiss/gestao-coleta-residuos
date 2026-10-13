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

    /**
     * Retorna as categorias de CNH que são permitidas para conduzir determinado veículo. Se informado nulo, permite qualquer uma.
     */
    public function categoriasCnhExigidas(): ?array
    {
        return match ($this) {
            self::COMPACTADOR       => ['C', 'D', 'E', 'AC', 'AD', 'AE'],
            self::CARROCERIA_ABERTA => null,
        };
    }
}