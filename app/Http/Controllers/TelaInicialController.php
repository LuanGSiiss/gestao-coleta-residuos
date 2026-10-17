<?php

namespace App\Http\Controllers;

use App\Enums\PerfilUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Tela para onde o sistema leva depois do login, da troca de senha e do clique na marca do menu.
 * Se for usuário técnico ou gestor, encaminha para o painel.
 * Se for usuário motorista, encaminha para a tela de coletas do dia.
 */
class TelaInicialController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        return $request->user()->perfil === PerfilUsuario::MOTORISTA ? redirect()->route('coletas.index') : redirect()->route('painel');
    }
}