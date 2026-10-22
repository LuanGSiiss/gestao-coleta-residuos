<?php

namespace App\Http\Controllers;

use App\Support\Voltar;
use App\Models\Execucao;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\OcorrenciaRequest;

class OcorrenciaController extends Controller
{
    public function create(Execucao $execucao): View|RedirectResponse
    {
        return $this->recusar($execucao)
            ?? view('ocorrencias.form', [
                'execucao' => $execucao->load('rotaVersao.rota'),
                'acao'     => route('execucoes.ocorrencias.store', $execucao),
                'voltar'   => Voltar::url(route('execucoes.show', $execucao))
            ]);
    }

    public function store(OcorrenciaRequest $request, Execucao $execucao): RedirectResponse
    {
        if ($recusa = $this->recusar($execucao)) {
            return $recusa;
        }

        $execucao->registrarOcorrencia($request->dadosDaOcorrencia(), $request->user());

        return redirect()->to(Voltar::url(route('execucoes.show', $execucao)))->with('sucesso', 'Ocorrência registrada.');
    }

    /** Retorna a tela de anterior a ação caso possua algum impedimento. */
    private function recusar(Execucao $execucao): ?RedirectResponse
    {
        $impedimento = $execucao->impedimentoParaOcorrencia();

        return $impedimento === null ? null : redirect()->to(Voltar::url(route('execucoes.show', $execucao)))->with('erro', $impedimento);
    }
}