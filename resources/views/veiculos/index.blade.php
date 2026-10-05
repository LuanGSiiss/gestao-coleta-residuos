@extends('layouts.app')
@section('titulo', 'Veículos')
@section('subtitulo', 'Frota disponível para as programações de coleta')

@section('acoes')
    <a href="{{ route('veiculos.create') }}" class="btn btn-primary">Novo veículo</a>
@endsection

@section('conteudo')
    <x-busca :rota="route('veiculos.index')" placeholder="Placa ou modelo…">
        <select name="marca" class="form-select" aria-label="Filtrar por marca">
            <option value="">Todas as marcas</option>
            @foreach ($marcas as $id => $nome)
                <option value="{{ $id }}" @selected((string) request('marca') === (string) $id)>{{ $nome }}</option>
            @endforeach
        </select>
    </x-busca>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 6rem;">Código</th>
                        <th style="width: 8rem;">Placa</th>
                        <th>Modelo</th>
                        <th>Marca</th>
                        <th>Tipo</th>
                        <th style="width: 5rem;">Ano</th>
                        <th style="width: 7rem;">Situação</th>
                        <th style="width: 11rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($veiculos as $veiculo)
                        <tr>
                            <td class="text-secondary">{{ $veiculo->id }}</td>
                            <td class="text-nowrap fw-semibold">{{ $veiculo->placa_formatada }}</td>
                            <td>{{ $veiculo->modelo }}</td>
                            <td>{{ $veiculo->marca->nome }}</td>
                            <td>{{ $veiculo->tipo->rotulo() }}</td>
                            <td>{{ $veiculo->ano }}</td>
                            <td>
                                <span class="badge text-bg-{{ $veiculo->ativo ? 'success' : 'secondary' }}">{{ $veiculo->ativo ? 'Ativo' : 'Inativo' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('veiculos.edit', $veiculo) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <x-botao-excluir :acao="route('veiculos.destroy', $veiculo)" :confirmacao="'Excluir o veículo ' . $veiculo->placa_formatada . '?'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tabela-vazia">
                                @if (request()->anyFilled(['id', 'busca', 'marca']))
                                    Nenhum veículo encontrado com estes filtros.
                                @else
                                    Nenhum veículo cadastrado ainda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $veiculos->links() }}
    </div>
@endsection