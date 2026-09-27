@extends('layouts.app')
@section('titulo', 'Marcas de veículo')
@section('subtitulo', 'Fabricantes disponíveis para o cadastro de veículos')

@section('acoes')
    <a href="{{ route('marcas.create') }}" class="btn btn-primary">Nova marca</a>
@endsection

@section('conteudo')
    <x-busca :rota="route('marcas.index')" placeholder="Buscar por nome…" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th style="width: 8rem;">Situação</th>
                        <th style="width: 11rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($marcas as $marca)
                        <tr>
                            <td>{{ $marca->nome }}</td>
                            <td>
                                <span class="badge text-bg-{{ $marca->ativo ? 'success' : 'secondary' }}">{{ $marca->ativo ? 'Ativa' : 'Inativa' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('marcas.edit', $marca) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <x-botao-excluir :acao="route('marcas.destroy', $marca)" :confirmacao="'Excluir a marca ' . $marca->nome . '?'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="tabela-vazia">
                                @if (request('busca'))
                                    Nenhuma marca encontrada para esta busca.
                                @else
                                    Nenhuma marca cadastrada ainda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $marcas->links() }}
    </div>
@endsection