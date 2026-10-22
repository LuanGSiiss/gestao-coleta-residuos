<?php

namespace App\Http\Controllers;

use App\Models\Execucao;
use Illuminate\View\View;
use App\Enums\SituacaoExecucao;

/**
 * Controller do painel operacional do dia, exibindo as execuções de hoje agrupadas por situação.
 */
class PainelController extends Controller
{
    public function __invoke(): View
    {
        $hoje = today()->toDateString();

        $execucoes = Execucao::query()
            ->where('data', $hoje)
            ->with(['motorista', 'veiculo', 'rotaVersao.rota'])
            ->withCount('ocorrencias')
            ->orderBy('hora_inicio_prevista')
            ->orderBy('id')
            ->get();

        return view('painel', [
            'total'         => $execucoes->count(),
            'grupos'        => $execucoes->groupBy(fn (Execucao $execucao) => $execucao->situacao->value),
            'atrasadas'     => $execucoes->filter(fn (Execucao $execucao) => $execucao->atrasada())->count(),
            'comOcorrencia' => $execucoes->where('ocorrencias_count', '>', 0)->count(),
            'emAndamentoAntes'  => Execucao::query()
                ->where('situacao', SituacaoExecucao::EM_ANDAMENTO->value)
                ->where('data', '<', $hoje)
                ->count(),
            'naoIniciadasAntes' => Execucao::query()
                ->naoIniciadas()
                ->where('data', '<', $hoje)
                ->count(),
        ]);
    }
}