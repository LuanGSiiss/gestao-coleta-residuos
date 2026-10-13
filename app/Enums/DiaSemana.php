<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use App\Enums\Concerns\TemRotulo;

enum DiaSemana: int
{
    use TemRotulo;

    case DOMINGO = 1;
    case SEGUNDA = 2;
    case TERCA   = 3;
    case QUARTA  = 4;
    case QUINTA  = 5;
    case SEXTA   = 6;
    case SABADO  = 7;

    public function rotulo(): string
    {
        return match ($this) {
            self::DOMINGO => 'Domingo',
            self::SEGUNDA => 'Segunda-feira',
            self::TERCA   => 'Terça-feira',
            self::QUARTA  => 'Quarta-feira',
            self::QUINTA  => 'Quinta-feira',
            self::SEXTA   => 'Sexta-feira',
            self::SABADO  => 'Sábado',
        };
    }

    /** Retorna o nome abreviado, como "Dom", "Seg", "Ter". */
    public function abreviado(): string
    {
        return mb_substr($this->rotulo(), 0, 3);
    }

    /**
     * Retorna o dia da semana da data informada.
     * O Carbon numera os dias de 0 (domingo) a 6 (sábado). Porém o sistema utiliza de 1 a 7.
     */
    public static function daData(CarbonInterface $data): self
    {
        return self::from($data->dayOfWeek + 1);
    }
}