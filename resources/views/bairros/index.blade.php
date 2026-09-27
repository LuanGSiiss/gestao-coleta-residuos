@extends('layouts.app')
@section('titulo', 'Bairros')
@section('subtitulo', 'Usados para categorizar as rotas de coleta')

@section('acoes')
    <a href="{{ route('bairros.create') }}" class="btn btn-primary">Novo bairro</a>
@endsection

@section('conteudo')
    <x-busca :rota="route('bairros.index')" placeholder="Buscar por nome…" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th style="width: 8rem;">Rotas</th>
                        <th style="width: 8rem;">Situação</th>
                        <th style="width: 11rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bairros as $bairro)
                        <tr>
                            <td>{{ $bairro->nome }}</td>
                            <td class="text-secondary">{{ $bairro->rotas_count ?? 0 }}</td>
                            <td>
                                <span class="badge text-bg-{{ $bairro->ativo ? 'success' : 'secondary' }}">{{ $bairro->ativo ? 'Ativo' : 'Inativo' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('bairros.edit', $bairro) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <x-botao-excluir :acao="route('bairros.destroy', $bairro)" :confirmacao="'Excluir o bairro ' . $bairro->nome . '?'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tabela-vazia">
                                @if (request('busca'))
                                    Nenhum bairro encontrado para esta busca.
                                @else
                                    Nenhum bairro cadastrado ainda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $bairros->links() }}
    </div>
@endsection