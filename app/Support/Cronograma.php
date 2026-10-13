<?php

namespace App\Support;

use App\Enums\DiaSemana;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Regras de calendário da programação de coleta.
 */
final class Cronograma
{
    /** Limite máxima da duração da programação e de cada prorrogação. */
    public const MESES = 12;

    /**
     * Retorna a data limite admitida para a programação, contado a partir de uma data.
     */
    public static function limite(CarbonInterface $aPartirDe): CarbonImmutable
    {
        return $aPartirDe->toImmutable()->startOfDay()->addMonthsNoOverflow(self::MESES);
    }

    /**
     * Retorna as datas do período que caem num dos dias da semana programados.
     *
     * @param  DiaSemana[]  $dias
     */
    public static function datas(CarbonInterface $inicio, CarbonInterface $fim, array $dias): array
    {
        $ultima = $fim->toImmutable()->startOfDay();
        $datas = [];

        for ($data = $inicio->toImmutable()->startOfDay(); $data->lte($ultima); $data = $data->addDay()) {
            if (in_array(DiaSemana::daData($data), $dias, true)) {
                $datas[] = $data;
            }
        }

        return $datas;
    }
}