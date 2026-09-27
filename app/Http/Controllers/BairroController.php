<?php

namespace App\Http\Controllers;

use App\Models\Bairro;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Requests\BairroRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Concerns\ExcluiRegistro;

class BairroController extends Controller
{
    use ExcluiRegistro;
    
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca'));

        $bairros = Bairro::query()
            ->when($busca !== '', fn ($query) => $query->where('nome', 'ilike', "%{$busca}%"))
            //->withCount('rotas')
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('bairros.index', compact('bairros'));
    }

    public function create(): View
    {
        return view('bairros.form', ['bairro' => new Bairro()]);
    }

    public function store(BairroRequest $request): RedirectResponse
    {
        Bairro::create($request->validated());

        return redirect()->route('bairros.index')->with('sucesso', 'Bairro cadastrado com sucesso.');
    }

    public function edit(Bairro $bairro): View
    {
        return view('bairros.form', compact('bairro'));
    }

    public function update(BairroRequest $request, Bairro $bairro): RedirectResponse
    {
        $bairro->update($request->validated());

        return redirect()->route('bairros.index')->with('sucesso', 'Bairro alterado com sucesso.');
    }

    public function destroy(Bairro $bairro): RedirectResponse
    {
        return $this->excluirSeNaoReferenciado($bairro, 'bairros.index');
    }
}