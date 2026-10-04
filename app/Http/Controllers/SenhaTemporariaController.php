<?php

namespace App\Http\Controllers;

use App\Http\Requests\SenhaTemporariaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SenhaTemporariaController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        // Usuários sem senha temporária não precisam acessar a pagina.
        if (!$request->user()->deve_alterar_senha) {
            return redirect()->route('painel');
        }

        return view('auth.senha-temporaria');
    }

    public function update(SenhaTemporariaRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        // Usuários sem senha temporária não precisam acessar a pagina.
        if (!$usuario->deve_alterar_senha) {
            return redirect()->route('painel');
        }

        $usuario->definirSenhaDefinitiva($request->validated('password'));

        // Regenera a sessão após a troca de senha.
        $request->session()->regenerate(true);

        return redirect()->route('painel')->with('sucesso', 'Senha definida com sucesso.');
    }
}