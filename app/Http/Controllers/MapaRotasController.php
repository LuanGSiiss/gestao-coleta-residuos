<?php

namespace App\Http\Controllers;

use App\Models\Rota;
use App\Models\Bairro;
use Illuminate\View\View;
use App\Support\MapaRotas;
use Illuminate\Http\Request;

class MapaRotasController extends Controller
{
    /**
     * Exibe o trajeto vigente de todas as rotas.
     */
    public function index(Request $request): View
    {
        $situacao = in_array($request->query('situacao'), ['ativas', 'inativas', 'todas'], true)
            ? $request->query('situacao')
            : 'ativas';

        $rotas = Rota::query()
            ->with('versaoAtual.trechos')
            ->when($request->filled('bairro'), fn ($query) => $query->whereHas(
                'bairros',
                fn ($bairros) => $bairros->whereKey($request->integer('bairro'))
            ))
            ->when($situacao !== 'todas', fn ($query) => $query->where('ativo', $situacao === 'ativas'))
            ->orderBy('identificacao')
            ->get();

        return view('rotas.mapa', [
            'rotas'    => $rotas,
            'bairros'  => Bairro::orderBy('nome')->pluck('nome', 'id'),
            'situacao' => $situacao,
            'mapa'     => [
                'camadas' => [['colecao' => MapaRotas::colecao($rotas), 'estilo' => (object) []]],
                'centro'  => config('coleta.mapa.centro'),
                'zoom'    => config('coleta.mapa.zoom'),
            ],
        ]);
    }
}