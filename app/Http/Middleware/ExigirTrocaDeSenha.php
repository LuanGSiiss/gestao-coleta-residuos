<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigirTrocaDeSenha
{
    /**
     * Realiza a validação para que, enquanto o usuário estiver com uma senha temporária, ele só acesse a tela de troca de senha e a saída do sistema.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario?->deve_alterar_senha && !$request->routeIs('senha.temporaria.*', 'logout')) {
            return redirect()->route('senha.temporaria.edit');
        }

        return $next($request);
    }
}
