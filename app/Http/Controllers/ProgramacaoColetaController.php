<?php

namespace App\Http\Controllers;

use App\Models\Rota;
use App\Models\Veiculo;
use App\Support\Retorno;
use App\Enums\DiaSemana;
use Illuminate\View\View;
use App\Enums\TipoColeta;
use App\Models\Funcionario;
use App\Support\Cronograma;
use App\Support\Habilitacao;
use Illuminate\Http\Request;
use App\Models\ProgramacaoColeta;
use App\Enums\SituacaoProgramacao;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ProrrogacaoRequest;
use App\Services\ProgramacaoColetaService;
use App\Http\Requests\ProgramacaoColetaRequest;

class ProgramacaoColetaController extends Controller
{
    public function __construct(private readonly ProgramacaoColetaService $servico)
    {
    }

    public function index(Request $request): View
    {
        $situacao = in_array($request->query('situacao'), ['ativas', 'encerradas', 'todas'], true) ? $request->query('situacao') : 'ativas';

        $busca = trim((string) $request->query('busca'));

        $programacoes = ProgramacaoColeta::query()
            ->with(['rota', 'diasSemana'])
            ->when($request->filled('id'), fn ($query) => $query->whereKey($request->integer('id')))
            ->when($busca !== '', fn ($query) => $query->where('descricao', 'ilike', "%{$busca}%"))
            ->when($request->filled('rota'), fn ($query) => $query->where('rota_id', $request->integer('rota')))
            ->when($situacao !== 'todas', fn ($query) => $query->where('situacao', $situacao === 'ativas' ? SituacaoProgramacao::ATIVA->value : SituacaoProgramacao::ENCERRADA->value))
            ->orderBy('descricao')
            ->paginate(15)
            ->withQueryString();

        return view('programacoes.index', [
            'programacoes' => $programacoes,
            'rotas'        => Rota::orderBy('identificacao')->get(),
            'situacao'     => $situacao,
        ]);
    }

    public function create(): View
    {
        return view('programacoes.form', $this->dadosFormulario(new ProgramacaoColeta()));
    }

    public function store(ProgramacaoColetaRequest $request): RedirectResponse
    {
        $programacao = new ProgramacaoColeta();

        $geradas = $this->servico->cadastrar(
            $programacao,
            $request->dadosDaProgramacao(),
            $request->coletores(),
            $request->diasSemana(),
        );

        return redirect()
            ->to(Retorno::manter('programacoes.show', $programacao))
            ->with('sucesso', "Programação {$programacao->id} cadastrada: "
                . self::quantidade($geradas, 'execução gerada', 'execuções geradas')
                . " no período de {$programacao->periodo}.")
            ->with('aviso', Habilitacao::aviso($programacao->motorista, $programacao->data_fim));
    }

    public function show(Request $request, ProgramacaoColeta $programacao): View
    {
        $programacao->load(['rota', 'motorista', 'veiculo', 'coletores', 'diasSemana']);

        $periodo = in_array($request->query('periodo'), ['proximas', 'anteriores', 'todas'], true) ? $request->query('periodo') : 'proximas';

        $hoje = today()->toDateString();

        $execucoes = $programacao->execucoes()
            ->with(['motorista', 'veiculo', 'coletores', 'rotaVersao.rota'])
            ->when($periodo === 'proximas', fn ($query) => $query->where('data', '>=', $hoje)->orderBy('data'))
            ->when($periodo === 'anteriores', fn ($query) => $query->where('data', '<', $hoje)->orderByDesc('data'))
            ->when($periodo === 'todas', fn ($query) => $query->orderBy('data'))
            ->paginate(20)
            ->withQueryString();

        $futuras = $programacao->execucoes()->naoIniciadas()->futuras()->count();

        return view('programacoes.show', [
            'programacao' => $programacao,
            'execucoes'   => $execucoes,
            'periodo'     => $periodo,
            // [situação => quantidade], numa consulta só
            'resumo'      => $programacao->execucoes()
                ->selectRaw('situacao, count(*) as total')
                ->groupBy('situacao')
                ->pluck('total', 'situacao'),
            'atrasadas'   => $programacao->execucoes()->atrasadas()->count(),
            'confirmacao' => "Encerrar a programação {$programacao->id}? " . match ($futuras) {
                0       => 'Nenhuma execução futura precisa ser cancelada.',
                1       => 'A execução futura ainda não iniciada será cancelada.',
                default => "As {$futuras} execuções futuras ainda não iniciadas serão canceladas.",
            } . ' Esta ação não pode ser desfeita.',
        ]);
    }

    public function edit(ProgramacaoColeta $programacao): View|RedirectResponse
    {
        return $this->recusarSeEncerrada($programacao) ?? view('programacoes.form', $this->dadosFormulario($programacao));
    }

    public function update(ProgramacaoColetaRequest $request, ProgramacaoColeta $programacao): RedirectResponse
    {
        if ($recusa = $this->recusarSeEncerrada($programacao)) {
            return $recusa;
        }

        $atualizadas = $this->servico->alterar($programacao, $request->dadosDaProgramacao(), $request->coletores());

        return redirect()
            ->to(Retorno::destino(route('programacoes.show', $programacao)))
            ->with('sucesso', "Programação {$programacao->id} alterada. " . ($atualizadas === 0
                ? 'Nenhuma execução futura precisou mudar.'
                : self::quantidade($atualizadas, 'execução futura atualizada.', 'execuções futuras atualizadas.')))
            ->with('aviso', Habilitacao::aviso($programacao->load('motorista')->motorista, $programacao->data_fim));
    }

    public function prorrogacao(ProgramacaoColeta $programacao): View|RedirectResponse
    {
        return $this->recusarSeEncerrada($programacao)
            ?? view('programacoes.prorrogar', [
                'programacao' => $programacao,
                'limite'      => Cronograma::limite($programacao->data_fim),
            ]);
    }

    public function prorrogar(ProrrogacaoRequest $request, ProgramacaoColeta $programacao): RedirectResponse
    {
        if ($recusa = $this->recusarSeEncerrada($programacao)) {
            return $recusa;
        }

        $geradas = $this->servico->prorrogar($programacao, $request->date('data_fim')->toImmutable());

        return redirect()
            ->to(Retorno::destino(route('programacoes.show', $programacao)))
            ->with('sucesso', "Programação {$programacao->id} prorrogada até {$programacao->data_fim->format('d/m/Y')}: "
                . self::quantidade($geradas, 'execução gerada.', 'execuções geradas.'))
            ->with('aviso', Habilitacao::aviso($programacao->motorista, $programacao->data_fim));
    }

    public function encerrar(Request $request, ProgramacaoColeta $programacao): RedirectResponse
    {
        if ($recusa = $this->recusarSeEncerrada($programacao)) {
            return $recusa;
        }

        $canceladas = $this->servico->encerrar($programacao, $request->user());

        return redirect()
            ->to(Retorno::manter('programacoes.show', $programacao))
            ->with('sucesso', "Programação {$programacao->id} encerrada. " . ($canceladas === 0
                ? 'Nenhuma execução futura precisou ser cancelada.'
                : self::quantidade($canceladas, 'execução futura cancelada.', 'execuções futuras canceladas.')));
    }

    /** Impede o encerramento se a situação não estiver como ativa. Abrindo a visualizaçao e exibindo o erro. */
    private function recusarSeEncerrada(ProgramacaoColeta $programacao): ?RedirectResponse
    {
        if ($programacao->ativa()) {
            return null;
        }

        return redirect()
            ->route('programacoes.show', $programacao)
            ->with('erro', 'Esta programação está encerrada e não admite mais alterações.');
    }

    private function dadosFormulario(ProgramacaoColeta $programacao): array
    {
        $coletoresAtuais = $programacao->exists ? $programacao->coletores()->pluck('funcionario.id')->all() : [];

        return [
            'programacao'           => $programacao,
            'tipos'                 => TipoColeta::opcoes(),
            'dias'                  => DiaSemana::cases(),
            'diasSelecionados'      => array_map('intval', (array) old('dias_semana', [])),
            'rotas'                 => $programacao->exists ? [] : $this->opcoesRotas(),
            'motoristas'            => $this->opcoesMotoristas($programacao->motorista_id),
            'veiculos'              => $this->opcoesVeiculos($programacao->veiculo_id),
            'coletores'             => $this->coletoresParaSelecao($coletoresAtuais),
            'coletoresSelecionados' => array_map('intval', (array) old('coletores', $coletoresAtuais)),
            'futuras'               => $programacao->exists ? $programacao->execucoes()->naoIniciadas()->futuras()->count() : 0
        ];
    }

    /** Retorna a lista de rotas ativas e com trajeto. */
    private function opcoesRotas(): array
    {
        return Rota::query()
            ->ativos()
            ->whereNotNull('versao_atual_id')
            ->orderBy('identificacao')
            ->get()
            ->mapWithKeys(fn (Rota $rota) => [$rota->id => "{$rota->identificacao} — {$rota->nome}"])
            ->all();
    }

    /** Retorna a lista de motorista ativos e mais o já vinculado a esta programação. */
    private function opcoesMotoristas(?int $atual): array
    {
        return Funcionario::query()
            ->motoristas()
            ->where(fn ($query) => $query->where('ativo', true)->when($atual, fn ($query, $id) => $query->orWhere('id', $id)))
            ->orderBy('nome')
            ->get()
            ->mapWithKeys(fn (Funcionario $motorista) => [
                $motorista->id => $motorista->nome . ($motorista->ativo ? '' : ' (inativo)') . ($motorista->cnhVencida() ? ' — CNH vencida' : ''),
            ])
            ->all();
    }

    /** Retorna a lista de veiculos ativos e mais o já vinculado a esta programação. */
    private function opcoesVeiculos(?int $atual): array
    {
        return Veiculo::query()
            ->where(fn ($query) => $query->where('ativo', true)->when($atual, fn ($query, $id) => $query->orWhere('id', $id)))
            ->orderBy('placa')
            ->get()
            ->mapWithKeys(fn (Veiculo $veiculo) => [
                $veiculo->id => "{$veiculo->placa_formatada} · {$veiculo->modelo} · {$veiculo->tipo->rotulo()}" . ($veiculo->ativo ? '' : ' (inativo)'),
            ])
            ->all();
    }

    /** Retorna a lista de coletores ativos e mais os já vinculados a esta programação. */
    private function coletoresParaSelecao(array $atuais): Collection
    {
        return Funcionario::query()
            ->coletores()
            ->where(fn ($query) => $query->where('ativo', true)->when($atuais, fn ($query, $ids) => $query->orWhereIn('id', $ids)))
            ->orderBy('nome')
            ->get();
    }

    /** Retorna o texto tratado com base na quantidade, sendo no singular ou plural. */
    private static function quantidade(int $numero, string $singular, string $plural): string
    {
        return $numero === 1 ? "1 {$singular}" : "{$numero} {$plural}";
    }
}