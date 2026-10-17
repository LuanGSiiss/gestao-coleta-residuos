<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Execucao;
use Illuminate\Auth\Access\Response;

/**
 * Controla a autorização por registro das execuções. 
 */
class ExecucaoPolicy
{
    /**
     * Controla para que o motorista acesse apenas as execuções associadas a ele.
     */
    public function acessarComoMotorista(Usuario $usuario, Execucao $execucao): Response
    {
        $visivel = $usuario->funcionario_id !== null
            && Execucao::query()
                ->doMotorista($usuario->funcionario_id)
                ->whereKey($execucao->id)
                ->exists();

        return $visivel ? Response::allow() : Response::denyAsNotFound();
    }
}