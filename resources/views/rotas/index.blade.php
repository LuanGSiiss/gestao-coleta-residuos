@extends('layouts.app')
@section('titulo', 'Rotas')
@section('subtitulo', 'Trajetos de coleta e seus bairros de abrangência')

@section('acoes')
    <div class="d-flex gap-2">
        <a href="{{ route('rotas.mapa') }}" class="btn btn-outline-secondary">Mapa das rotas</a>
        <a href="{{ route('rotas.create') }}" class="btn btn-primary">Nova rota</a>
    </div>
@endsection

@section('conteudo')
    <x-busca :rota="route('rotas.index')" placeholder="Identificação ou nome…">
        <select name="bairro" class="form-select" aria-label="Filtrar por bairro">
            <option value="">Todos os bairros</option>
            @foreach ($bairros as $id => $nome)
                <option value="{{ $id }}" @selected((string) request('bairro') === (string) $id)>{{ $nome }}</option>
            @endforeach
        </select>
    </x-busca>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 6rem;">Código</th>
                        <th style="width: 9rem;">Identificação</th>
                        <th>Nome</th>
                        <th style="width: 6rem;">Bairros</th>
                        <th style="width: 8rem;">Extensão</th>
                        <th style="width: 6rem;">Versão</th>
                        <th style="width: 7rem;">Situação</th>
                        <th style="width: 15rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rotas as $rota)
                        <tr>
                            <td class="text-secondary">{{ $rota->id }}</td>
                            <td class="text-nowrap">
                                <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                                <span class="fw-semibold ms-1">{{ $rota->identificacao }}</span>
                            </td>
                            <td>{{ $rota->nome }}</td>
                            <td class="text-secondary">{{ $rota->bairros_count }}</td>
                            <td class="text-nowrap">{{ $rota->versaoAtual?->extensao_formatada ?? '—' }}</td>
                            <td class="text-secondary">{{ $rota->versaoAtual?->numero ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $rota->ativo ? 'success' : 'secondary' }}">{{ $rota->ativo ? 'Ativa' : 'Inativa' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('rotas.show', $rota) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                                <a href="{{ route('rotas.edit', $rota) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <a href="{{ route('rotas.versoes', $rota) }}" class="btn btn-sm btn-outline-secondary">Versões</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tabela-vazia">
                                @if (request()->anyFilled(['id', 'busca', 'bairro']))
                                    Nenhuma rota encontrada com estes filtros.
                                @else
                                    Nenhuma rota cadastrada ainda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $rotas->links() }}
    </div>
@endsection