<?php

namespace App\Exceptions;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Debug\ShouldntReport;

/**
 * Controla a exceções de operações recudadas, como tentar cancelar uma execução que está está cancelada.
 * Quando disparada volta para a tela anterior com a explicação.
 */
final class OperacaoRecusada extends RuntimeException implements ShouldntReport
{
    public function render(Request $request): RedirectResponse
    {
        return back()->with('erro', $this->getMessage());
    }
}