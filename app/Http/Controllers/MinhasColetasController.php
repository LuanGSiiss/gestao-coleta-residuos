<?php

namespace App\Http\Controllers;

use App\Models\Execucao;
use App\Support\Trajeto;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\ExecucaoService;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\OcorrenciaRequest;
use App\Http\Requests\InicioExecucaoRequest;
use App\Http\Requests\ConclusaoExecucaoRequest;

/**
 * Tela do motorista, exibindos as informações necessárias para a operação do motorista.
 */
class MinhasColetasController extends Controller
{
    public function __construct(private readonly ExecucaoService $servico)
    {
    }

    public function index(Request $request): View
    {
        $execucoes = Execucao::query()
            ->doMotorista($this->funcionarioId($request))
            ->with(['programacao', 'veiculo', 'coletores', 'rotaVersao.rota'])
            ->withCount('ocorrencias')
            ->orderBy('data')
            ->orderBy('hora_inicio_prevista')
            ->get();

        return view('minhas-coletas.index', ['execucoes' => $execucoes]);
    }

    public function show(Execucao $execucao): View
    {
        $execucao->load(['programacao', 'veiculo', 'coletores', 'rotaVersao.rota', 'rotaVersao.trechos', 'ocorrencias.usuario']);
        $rota = $execucao->rotaVersao->rota;

        return view('minhas-coletas.show', [
            'execucao' => $execucao,
            'mapa'     => [
                'camadas' => [[
                    'colecao' => Trajeto::daVersao($execucao->rotaVersao)->paraFeatureCollection([
                        'identificacao' => $rota->identificacao,
                        'nome'          => $rota->nome,
                    ]),
                    'estilo'  => ['color' => $rota->cor],
                ]],
                'centro'  => config('coleta.mapa.centro'),
                'zoom'    => config('coleta.mapa.zoom'),
            ],
        ]);
    }

    public function inicio(Execucao $execucao): View|RedirectResponse
    {
        return $this->recusar($execucao, $execucao->impedimentoParaIniciar()) ?? view('minhas-coletas.iniciar', ['execucao' => $execucao->load(['veiculo', 'rotaVersao.rota'])]);
    }

    public function iniciar(InicioExecucaoRequest $request, Execucao $execucao): RedirectResponse
    {
        if ($recusa = $this->recusar($execucao, $execucao->impedimentoParaIniciar())) {
            return $recusa;
        }

        $this->servico->iniciar($execucao, $request->inicio());

        return redirect()->route('coletas.show', $execucao)->with('sucesso', "Coleta iniciada às {$execucao->iniciada_em->format('H:i')}.");
    }

    public function conclusao(Execucao $execucao): View|RedirectResponse
    {
        return $this->recusar($execucao, $execucao->impedimentoParaConcluir()) ?? view('minhas-coletas.concluir', ['execucao' => $execucao->load('rotaVersao.rota')]);
    }

    public function concluir(ConclusaoExecucaoRequest $request, Execucao $execucao): RedirectResponse
    {
        if ($recusa = $this->recusar($execucao, $execucao->impedimentoParaConcluir())) {
            return $recusa;
        }

        $this->servico->concluir(
            $execucao,
            $request->termino(),
            $request->validated('quilometragem'),
            $request->validated('quantidade_kg'),
            $request->validated('observacoes'),
        );

        return redirect()
            ->route('coletas.index')
            ->with('sucesso', "Coleta concluída: {$execucao->tempo_total}, " . "{$execucao->quilometragem_formatada} e {$execucao->quantidade_formatada}.");
    }

    public function novaOcorrencia(Execucao $execucao): View|RedirectResponse
    {
        return $this->recusar($execucao, $execucao->impedimentoParaOcorrencia())
            ?? view('ocorrencias.form', [
                'execucao' => $execucao->load('rotaVersao.rota'),
                'acao'     => route('coletas.ocorrencias.store', $execucao),
                'voltar'   => route('coletas.show', $execucao),
            ]);
    }

    public function registrarOcorrencia(OcorrenciaRequest $request, Execucao $execucao): RedirectResponse
    {
        if ($recusa = $this->recusar($execucao, $execucao->impedimentoParaOcorrencia())) {
            return $recusa;
        }

        $execucao->registrarOcorrencia($request->dadosDaOcorrencia(), $request->user());

        return redirect()->route('coletas.show', $execucao)->with('sucesso', 'Ocorrência registrada.');
    }

    /** Retorna o id do funcionario vinculado ao usuário logado. */
    private function funcionarioId(Request $request): int
    {
        $funcionarioId = $request->user()->funcionario_id;

        abort_if($funcionarioId === null, 403, 'Seu usuário não está vinculado a um funcionário motorista.');

        return $funcionarioId;
    }

    /** Retorna a tela de anterior a ação caso possua algum impedimento. */
    private function recusar(Execucao $execucao, ?string $impedimento): ?RedirectResponse
    {
        return $impedimento === null ? null : redirect()->route('coletas.show', $execucao)->with('erro', $impedimento);
    }
}