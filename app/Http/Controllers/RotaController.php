<?php

namespace App\Http\Controllers;

use App\Models\Rota;
use App\Models\Bairro;
use App\Support\Trajeto;
use App\Support\Retorno;
use App\Support\MapaRotas;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\RotaService;
use App\Http\Requests\RotaRequest;
use Illuminate\Http\RedirectResponse;

class RotaController extends Controller
{
    public function __construct(private readonly RotaService $servico)
    {
    }

    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca'));

        $rotas = Rota::query()
            ->when($request->filled('id'), fn ($query) => $query->whereKey($request->integer('id')))
            ->with('versaoAtual')
            ->withCount('bairros')
            ->when($busca !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('identificacao', 'ilike', "%{$busca}%")
                ->orWhere('nome', 'ilike', "%{$busca}%")))
            ->when($request->filled('bairro'), fn ($query) => $query->whereHas(
                'bairros',
                fn ($bairros) => $bairros->whereKey($request->integer('bairro'))
            ))
            ->orderBy('identificacao')
            ->paginate(15)
            ->withQueryString();

        return view('rotas.index', [
            'rotas'   => $rotas,
            'bairros' => Bairro::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('rotas.form', $this->dadosFormulario(new Rota()));
    }

    public function store(RotaRequest $request): RedirectResponse
    {
        return $this->salvar(new Rota(), $request);
    }

    public function edit(Rota $rota): View
    {
        return view('rotas.form', $this->dadosFormulario($rota));
    }

    public function update(RotaRequest $request, Rota $rota): RedirectResponse
    {
        return $this->salvar($rota, $request);
    }

    private function salvar(Rota $rota, RotaRequest $request): RedirectResponse
    {
        $versaoAntes = $rota->versao_atual_id;

        $this->servico->salvar(
            $rota,
            $request->dadosDaRota(),
            $request->bairros(),
            $request->trajeto(),
            $request->user(),
        );

        // Mostra uma mensagem indicando se o trajeto gerou versão nova ou não.
        $mensagem = $rota->versao_atual_id !== $versaoAntes
            ? "Rota {$rota->identificacao} salva. Versão {$rota->versaoAtual->numero} do trajeto registrada."
            : "Rota {$rota->identificacao} salva. O trajeto não mudou, então nenhuma versão nova foi criada.";

        return redirect()->to(Retorno::destino(route('rotas.show', $rota)))->with('sucesso', $mensagem);
    }

    public function show(Rota $rota): View
    {
        $rota->load([
            'bairros' => fn ($query) => $query->orderBy('nome'),
            'versaoAtual.usuario',
            'versaoAtual.trechos',
        ]);

        $versao = $rota->versaoAtual;

        return view('rotas.show', [
            'rota'         => $rota,
            'versao'       => $versao,
            'totalVersoes' => $rota->versoes()->count(),
            'mapa'         => [
                'camadas' => $versao === null ? [] : [[
                    'colecao' => Trajeto::daVersao($versao)->paraFeatureCollection([
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

    private function dadosFormulario(Rota $rota): array
    {
        $vinculados = $rota->exists ? $rota->bairros()->pluck('bairro.id')->all() : [];

        // Outras rotas ativas, para referência visual e alinhamento no mapa.
        $outras = Rota::query()
            ->ativos()
            ->when($rota->exists, fn ($query) => $query->whereKeyNot($rota->id))
            ->with('versaoAtual.trechos')
            ->get();

        return [
            'rota'         => $rota,
            'cor'          => $rota->cor ?? Rota::sugerirCor(),
            'bairros'      => Bairro::query()
                ->where(fn ($q) => $q
                    ->where('ativo', true)
                    ->when($vinculados, fn ($q, $ids) => $q->orWhereIn('id', $ids)))
                ->orderBy('nome')
                ->pluck('nome', 'id'),
            'selecionados' => array_map('intval', (array) old('bairros', $vinculados)),
            'mapa'         => [
                'trajeto'    => $this->trajetoInicial($rota),
                'referencia' => MapaRotas::colecao($outras, false),
                'centro'     => config('coleta.mapa.centro'),
                'zoom'       => config('coleta.mapa.zoom'),
            ],
        ];
    }

    /**
     * Depois de um erro de validação, o mapa reabre com o que o usuário tinha desenhado.
     */
    private function trajetoInicial(Rota $rota): ?array
    {
        $enviado = old('trajeto');

        if (is_string($enviado) && $enviado !== '') {
            $decodificado = json_decode($enviado, true);

            if (is_array($decodificado)) {
                return $decodificado;
            }
        }

        if ($rota->versaoAtual === null) {
            return null;
        }

        return Trajeto::daVersao($rota->versaoAtual->load('trechos'))->paraFeatureCollection();
    }
}