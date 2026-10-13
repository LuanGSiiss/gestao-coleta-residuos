<?php

namespace App\Support;

use LogicException;
use Illuminate\Support\Facades\DB;

/**
 * Responsável por realizar a trava única das operações que gravam execuções.
 */
final class TravaExecucoes
{
    private const CHAVE = 1;

    public static function adquirir(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('A trava das execuções só vale dentro de uma transação.');
        }

        DB::select('SELECT pg_advisory_xact_lock(?)', [self::CHAVE]);
    }
}