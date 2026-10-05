@extends('layouts.app')
@section('titulo', 'Usuários')
@section('subtitulo', 'Contas de acesso ao sistema')

@section('acoes')
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">Novo usuário</a>
@endsection

@section('conteudo')
    <x-senha-gerada />

    <x-busca :rota="route('usuarios.index')" placeholder="Nome ou e-mail…">
        <select name="perfil" class="form-select" aria-label="Filtrar por perfil">
            <option value="">Todos os perfis</option>
            @foreach ($perfis as $valor => $rotulo)
                <option value="{{ $valor }}" @selected((string) request('perfil') === (string) $valor)>
                    {{ $rotulo }}
                </option>
            @endforeach
        </select>
    </x-busca>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 6rem;">Código</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th style="width: 8rem;">Perfil</th>
                        <th>Funcionário</th>
                        <th style="width: 11rem;">Situação</th>
                        <th style="width: 6rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td class="text-secondary">{{ $usuario->id }}</td>
                            <td>
                                {{ $usuario->nome }}
                                @if ($usuario->is(auth()->user()))
                                    <span class="text-secondary small">(você)</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $usuario->email }}</td>
                            <td>{{ $usuario->perfil->rotulo() }}</td>
                            <td class="text-secondary">{{ $usuario->funcionario?->nome ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $usuario->ativo ? 'success' : 'secondary' }}">{{ $usuario->ativo ? 'Ativo' : 'Inativo' }}</span>
                                @if ($usuario->deve_alterar_senha)
                                    <span class="badge text-bg-warning">Senha pendente</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tabela-vazia">
                                Nenhum usuário encontrado com estes filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $usuarios->links() }}
    </div>
@endsection