<?php

namespace App\Http\Controllers;

use App\Models\Rota;
use App\Support\Trajeto;
use Illuminate\View\View;
use App\Models\RotaVersao;
use Illuminate\Support\Collection;

class RotaVersaoController extends Controller
{
    public function index(Rota $rota, ?int $numero = null): View
    {
        $versoes = $rota->versoes()
            ->with('usuario')
            ->withCount('trechos')
            ->orderByDesc('numero')
            ->get();

        $selecionada = $numero === null
            ? $versoes->firstWhere('id', $rota->versao_atual_id)
            : $versoes->firstWhere('numero', $numero);

        abort_if($selecionada === null, 404);

        return view('rotas.versoes', [
            'rota'        => $rota,
            'versoes'     => $versoes,
            'selecionada' => $selecionada,
            'mapa'        => [
                'camadas' => $this->camadas($rota, $versoes, $selecionada),
                'centro'  => config('coleta.mapa.centro'),
                'zoom'    => config('coleta.mapa.zoom'),
            ],
        ]);
    }

    /**
     * A versão consultada vai por cima, na cor da rota. Se ela não for a vigente, a vigente aparece por baixo, em cinza tracejado.
     */
    private function camadas(Rota $rota, Collection $versoes, RotaVersao $selecionada): array
    {
        $camadas = [];
        $vigente = $versoes->firstWhere('id', $rota->versao_atual_id);

        if ($vigente !== null && !$vigente->is($selecionada)) {
            $camadas[] = [
                'colecao' => Trajeto::daVersao($vigente->load('trechos'))->paraFeatureCollection([
                    'identificacao' => $rota->identificacao,
                    'nome'          => "versão {$vigente->numero}, vigente",
                ]),
                'estilo'  => ['color' => '#6c757d', 'weight' => 4, 'opacity' => 0.7, 'dashArray' => '6 6'],
            ];
        }

        $camadas[] = [
            'colecao' => Trajeto::daVersao($selecionada->load('trechos'))->paraFeatureCollection([
                'identificacao' => $rota->identificacao,
                'nome'          => "versão {$selecionada->numero}",
            ]),
            'estilo'  => ['color' => $rota->cor],
        ];

        return $camadas;
    }
}