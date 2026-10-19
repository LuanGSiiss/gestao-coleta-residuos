<?php

namespace App\Http\Controllers;

use App\Models\Rota;
use DateTimeImmutable;
use App\Models\Veiculo;
use App\Models\Execucao;
use App\Support\Retorno;
use App\Enums\TipoColeta;
use Illuminate\View\View;
use App\Models\RotaVersao;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use App\Enums\SituacaoExecucao;
use App\Support\OpcoesAlocacao;
use App\Services\ExecucaoService;
use App\Models\ProgramacaoColeta;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ExecucaoRequest;
use App\Http\Requests\CancelamentoRequest;

class ExecucaoController extends Controller
{
    private const FILTROS = ['id', 'de', 'ate', 'programacao', 'rota', 'motorista', 'veiculo', 'situacao'];

    public function __construct(private readonly ExecucaoService $servico)
    {
    }

    public function index(Request $request): View
    {
        $filtrando = $request->hasAny(self::FILTROS);
        $de = $filtrando ? self::data($request->query('de')) : today()->toDateString();
        $ate = $filtrando ? self::data($request->query('ate')) : today()->toDateString();
        $situacao = (string) $request->query('situacao');

        $execucoes = Execucao::query()
            ->with(['programacao', 'motorista', 'veiculo', 'rotaVersao.rota'])
            ->withCount('ocorrencias')
            ->when($request->filled('id'), fn ($query) => $query->whereKey($request->integer('id')))
            ->when($de, fn ($query, $data) => $query->where('data', '>=', $data))
            ->when($ate, fn ($query, $data) => $query->where('data', '<=', $data))
            ->when($request->filled('programacao'), fn ($query) => $query->where('programacao_coleta_id', $request->integer('programacao')))
            ->when($request->filled('rota'), fn ($query) => $query->whereIn(
                'rota_versao_id',
                RotaVersao::select('id')->where('rota_id', $request->integer('rota')),
            ))
            ->when($request->filled('motorista'), fn ($query) => $query->where('motorista_id', $request->integer('motorista')))
            ->when($request->filled('veiculo'), fn ($query) => $query->where('veiculo_id', $request->integer('veiculo')))
            ->when($situacao === 'atrasadas', fn ($query) => $query->atrasadas())
            ->when(SituacaoExecucao::tryFrom((int) $situacao), fn ($query, $valor) => $query->where('situacao', $valor->value))
            ->when(TipoColeta::tryFrom($request->integer('tipo')), fn ($query, $tipo) => $query->whereIn(
                'programacao_coleta_id',
                ProgramacaoColeta::select('id')->where('tipo_coleta', $tipo->value),
            ))
            ->when($request->boolean('ocorrencias'), fn ($query) => $query->has('ocorrencias'))
            ->orderBy('data')
            ->orderBy('hora_inicio_prevista')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('execucoes.index', [
            'execucoes'    => $execucoes,
            'de'           => $de,
            'ate'          => $ate,
            'situacao'     => $situacao,
            'programacoes' => ProgramacaoColeta::orderBy('situacao')->orderBy('descricao')->get(),
            'rotas'        => Rota::orderBy('identificacao')->get(),
            'motoristas'   => Funcionario::motoristas()->orderBy('nome')->get(),
            'veiculos'     => Veiculo::orderBy('placa')->get(),
            'tipos'        => TipoColeta::opcoes(),
        ]);
    }

    public function show(Execucao $execucao): View
    {
        $execucao->load(['programacao', 'motorista', 'veiculo', 'coletores', 'rotaVersao.rota', 'ocorrencias.usuario']);

        return view('execucoes.show', ['execucao' => $execucao]);
    }

    public function edit(Execucao $execucao): View|RedirectResponse
    {
        return $this->recusar($execucao, $execucao->impedimentoParaAlterar()) ?? view('execucoes.form', $this->dadosFormulario($execucao));
    }

    public function update(ExecucaoRequest $request, Execucao $execucao): RedirectResponse
    {
        if ($recusa = $this->recusar($execucao, $execucao->impedimentoParaAlterar())) {
            return $recusa;
        }

        $this->servico->alterar($execucao, $request->dadosDaExecucao(), $request->coletores());

        return redirect()->to(Retorno::destino(route('execucoes.show', $execucao)))->with('sucesso', "Execução {$execucao->id} alterada.");
    }

    public function cancelamento(Execucao $execucao): View|RedirectResponse
    {
        return $this->recusar($execucao, $execucao->impedimentoParaCancelar())
            ?? view('execucoes.cancelar', ['execucao' => $execucao->load(['programacao', 'motorista', 'rotaVersao.rota'])]);
    }

    public function cancelar(CancelamentoRequest $request, Execucao $execucao): RedirectResponse
    {
        if ($recusa = $this->recusar($execucao, $execucao->impedimentoParaCancelar())) {
            return $recusa;
        }

        $this->servico->cancelar($execucao, $request->validated('motivo'), $request->user());

        return redirect()->to(Retorno::destino(route('execucoes.show', $execucao)))->with('sucesso', "Execução {$execucao->id} cancelada.");
    }

    /** Retorna a tela de anterior a ação caso possua algum impedimento. */
    private function recusar(Execucao $execucao, ?string $impedimento): ?RedirectResponse
    {
        if ($impedimento === null) {
            return null;
        }

        return redirect()->to(Retorno::destino(route('execucoes.show', $execucao)))->with('erro', $impedimento);
    }

    private function dadosFormulario(Execucao $execucao): array
    {
        $execucao->load(['programacao', 'rotaVersao.rota', 'coletores']);
        $coletoresAtuais = $execucao->coletores->pluck('id')->all();

        return [
            'execucao'              => $execucao,
            'motoristas'            => OpcoesAlocacao::motoristas($execucao->motorista_id),
            'veiculos'              => OpcoesAlocacao::veiculos($execucao->veiculo_id),
            'coletores'             => OpcoesAlocacao::coletores($coletoresAtuais),
            'coletoresSelecionados' => array_map('intval', (array) old('coletores', $coletoresAtuais)),
        ];
    }

    /** Retorna a data informada no filtro, tratando para ignorar valores invalidos. */
    private static function data(mixed $valor): ?string
    {
        if (!is_string($valor) || $valor === '') {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return $data !== false && $data->format('Y-m-d') === $valor ? $valor : null;
    }
}