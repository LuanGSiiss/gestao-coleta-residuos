<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPerfil
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            abort(403);
        }

        $permitidos = array_map('strtoupper', $perfis);

        if (!in_array($usuario->perfil->name, $permitidos, true)) {
            abort(403, 'Seu perfil não tem acesso a esta rotina.');
        }

        return $next($request);
    }
}
