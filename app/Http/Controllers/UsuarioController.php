<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\View\View;
use App\Models\Funcionario;
use App\Enums\PerfilUsuario;
use Illuminate\Http\Request;
use App\Http\Requests\UsuarioRequest;
use Illuminate\Http\RedirectResponse;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca'));

        $usuarios = Usuario::query()
            ->with('funcionario')
            ->when($busca !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('nome', 'ilike', "%{$busca}%")
                ->orWhere('email', 'ilike', "%{$busca}%")))
            ->when($request->filled('perfil'), fn ($query) => $query->where('perfil', $request->integer('perfil')))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'perfis'   => PerfilUsuario::opcoes(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', $this->dadosFormulario(new Usuario()));
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = new Usuario($request->validated());
        $senha   = $usuario->definirSenhaTemporaria();
        $usuario->save();

        return $this->exibirSenhaGerada($usuario, $senha, 'Usuário cadastrado com sucesso.');
    }

    public function edit(Usuario $usuario): View
    {
        return view('usuarios.form', $this->dadosFormulario($usuario));
    }

    public function update(UsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $usuario->update($request->validated());

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário alterado com sucesso.');
    }

    /**
     * Gera uma nova senha temporária, exibido-a apenas uma vez.
     */
    public function redefinirSenha(Usuario $usuario): RedirectResponse
    {
        $senha = $usuario->definirSenhaTemporaria();
        $usuario->save();

        return $this->exibirSenhaGerada($usuario, $senha, 'Senha redefinida com sucesso.');
    }

    private function dadosFormulario(Usuario $usuario): array
    {
        return [
            'usuario'    => $usuario,
            'perfis'     => PerfilUsuario::opcoes(),
            'motoristas' => Funcionario::query()
                ->motoristas()
                ->where(fn ($q) => $q
                    ->where(fn ($q) => $q->ativos()->whereDoesntHave('usuario'))
                    ->when($usuario->funcionario_id, fn ($q, $id) => $q->orWhere('id', $id)))
                ->orderBy('nome')
                ->pluck('nome', 'id'),
        ];
    }

    /**
     * Exibe a senha gerada ao usuário apenas uma vez. No entanto, a senha vai para a sessão como dado "flash", que existe só até a requisição
     * seguinte. Ao recarregar a página, ela some.
     */
    private function exibirSenhaGerada(Usuario $usuario, string $senha, string $mensagem): RedirectResponse
    {
        return redirect()->route('usuarios.index')->with('sucesso', $mensagem)->with('senha_gerada', [
                'nome'  => $usuario->nome,
                'email' => $usuario->email,
                'senha' => $senha,
            ]);
    }
}