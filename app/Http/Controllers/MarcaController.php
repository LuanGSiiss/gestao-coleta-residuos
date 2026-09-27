<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Requests\MarcaRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Concerns\ExcluiRegistro;

class MarcaController extends Controller
{
    use ExcluiRegistro;
    
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca'));

        $marcas = Marca::query()
            ->when($busca !== '', fn ($query) => $query->where('nome', 'ilike', "%{$busca}%"))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('marcas.index', compact('marcas'));
    }

    public function create(): View
    {
        return view('marcas.form', ['marca' => new Marca()]);
    }

    public function store(MarcaRequest $request): RedirectResponse
    {
        Marca::create($request->validated());

        return redirect()->route('marcas.index')->with('sucesso', 'Marca cadastrada com sucesso.');
    }

    public function edit(Marca $marca): View
    {
        return view('marcas.form', compact('marca'));
    }

    public function update(MarcaRequest $request, Marca $marca): RedirectResponse
    {
        $marca->update($request->validated());

        return redirect()->route('marcas.index')->with('sucesso', 'Marca alterada com sucesso.');
    }

    public function destroy(Marca $marca): RedirectResponse
    {
        return $this->excluirSeNaoReferenciado($marca, 'marcas.index');
    }
}