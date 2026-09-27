<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;

trait ExcluiRegistro
{
    /**
     * Tentar excluir um registro e caso o mesmo possua algum vínculo retorna uma mensagem explicando.
     */
    protected function excluirSeNaoReferenciado(Model $registro, string $rotaListagem): RedirectResponse
    {
        try {
            $registro->delete();
        } catch (QueryException $e) {
            if ($e->getCode() !== '23503') {
                throw $e;
            }

            return back()->with('erro', 'Este registro não pode ser excluído porque está em uso em outros cadastros.');
        }

        return redirect()->route($rotaListagem)->with('sucesso', 'Registro excluído com sucesso.');
    }
}