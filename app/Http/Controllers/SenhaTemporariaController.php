<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\SenhaTemporariaRequest;

class SenhaTemporariaController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (!$request->user()->deve_alterar_senha) {
            return redirect()->route('tela-inicial');
        }

        return view('auth.senha-temporaria');
    }

    public function update(SenhaTemporariaRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        if (!$usuario->deve_alterar_senha) {
            return redirect()->route('tela-inicial');
        }

        $usuario->definirSenhaDefinitiva($request->validated('password'));
        $request->session()->regenerate(true);

        return redirect()->route('tela-inicial')->with('sucesso', 'Senha definida com sucesso.');
    }
}