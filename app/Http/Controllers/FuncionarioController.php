<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use App\Enums\TipoFuncionario;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\FuncionarioRequest;
use App\Http\Controllers\Concerns\ExcluiRegistro;

class FuncionarioController extends Controller
{
    use ExcluiRegistro;

    public function index(Request $request): View
    {
        $busca   = trim((string) $request->query('busca'));
        $digitos = preg_replace('/\D/', '', $busca);

        $funcionarios = Funcionario::query()
            ->when($request->filled('id'), fn ($query) => $query->whereKey($request->integer('id')))
            ->when($busca !== '', function ($query) use ($busca, $digitos) {
                $query->where(function ($q) use ($busca, $digitos) {
                    $q->where('nome', 'ilike', "%{$busca}%");
                    if ($digitos !== '') {
                        $q->orWhere('cpf', 'like', "%{$digitos}%");
                    }
                });
            })
            ->when($request->filled('tipo'), fn ($query) => $query->where('tipo', $request->integer('tipo')))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('funcionarios.index', [
            'funcionarios' => $funcionarios,
            'tipos'        => TipoFuncionario::opcoes(),
        ]);
    }

    public function create(): View
    {
        return view('funcionarios.form', $this->dadosFormulario(new Funcionario()));
    }

    public function store(FuncionarioRequest $request): RedirectResponse
    {
        Funcionario::create($request->validated());

        return redirect()->route('funcionarios.index')->with('sucesso', 'Funcionário cadastrado com sucesso.');
    }

    public function edit(Funcionario $funcionario): View
    {
        return view('funcionarios.form', $this->dadosFormulario($funcionario));
    }

    public function update(FuncionarioRequest $request, Funcionario $funcionario): RedirectResponse
    {
        $funcionario->update($request->validated());

        return redirect()->route('funcionarios.index')->with('sucesso', 'Funcionário alterado com sucesso.');
    }

    public function destroy(Funcionario $funcionario): RedirectResponse
    {
        return $this->excluirSeNaoReferenciado($funcionario, 'funcionarios.index');
    }

    private function dadosFormulario(Funcionario $funcionario): array
    {
        return [
            'funcionario' => $funcionario,
            'tipos'       => TipoFuncionario::opcoes(),
            'categorias'  => array_combine(Funcionario::CATEGORIAS_CNH, Funcionario::CATEGORIAS_CNH),
        ];
    }
}