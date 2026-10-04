<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Models\Veiculo;
use Illuminate\View\View;
use App\Enums\TipoVeiculo;
use Illuminate\Http\Request;
use App\Http\Requests\VeiculoRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Concerns\ExcluiRegistro;

class VeiculoController extends Controller
{
    use ExcluiRegistro;

    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca'));
        $placa = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $busca));

        $veiculos = Veiculo::query()
            ->with('marca')
            ->when($busca !== '', fn ($query) => $query->where(function ($q) use ($busca, $placa) {
                $q->where('modelo', 'ilike', "%{$busca}%");

                if ($placa !== '') {
                    $q->orWhere('placa', 'like', "%{$placa}%");
                }
            }))
            ->when($request->filled('marca'), fn ($query) => $query->where('marca_id', $request->integer('marca')))
            ->orderBy('placa')
            ->paginate(15)
            ->withQueryString();

        return view('veiculos.index', [
            'veiculos' => $veiculos,
            'marcas'   => Marca::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('veiculos.form', $this->dadosFormulario(new Veiculo()));
    }

    public function store(VeiculoRequest $request): RedirectResponse
    {
        Veiculo::create($request->validated());

        return redirect()->route('veiculos.index')->with('sucesso', 'Veículo cadastrado com sucesso.');
    }

    public function edit(Veiculo $veiculo): View
    {
        return view('veiculos.form', $this->dadosFormulario($veiculo));
    }

    public function update(VeiculoRequest $request, Veiculo $veiculo): RedirectResponse
    {
        $veiculo->update($request->validated());

        return redirect()->route('veiculos.index')->with('sucesso', 'Veículo alterado com sucesso.');
    }

    public function destroy(Veiculo $veiculo): RedirectResponse
    {
        return $this->excluirSeNaoReferenciado($veiculo, 'veiculos.index');
    }

    private function dadosFormulario(Veiculo $veiculo): array
    {
        return [
            'veiculo' => $veiculo,
            'marcas'  => Marca::paraSelecao($veiculo->marca_id)->pluck('nome', 'id'),
            'tipos'   => TipoVeiculo::opcoes(),
        ];
    }
}