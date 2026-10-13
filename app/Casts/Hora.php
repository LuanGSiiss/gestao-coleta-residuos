<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Coluna TIME do PostgreSQL vista como "HH:MM".
 * Cast para a coluna do tipo time do Postgres, usada para manter o padrão do formato dos horários, pois o navegador envia a hora como "07:00",
 * porém o banco utilizada "07:00:00".
 */
final class Hora implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::normalizar($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::normalizar($value);
    }

    /** "7:00", "07:00" e "07:00:00" viram "07:00". */
    public static function normalizar(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        [$horas, $minutos] = array_pad(explode(':', (string) $valor), 2, '0');

        return sprintf('%02d:%02d', (int) $horas, (int) $minutos);
    }
}