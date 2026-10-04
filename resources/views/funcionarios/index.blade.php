@extends('layouts.app')
@section('titulo', 'Funcionários')
@section('subtitulo', 'Motoristas e coletores disponíveis para as programações de coleta')

@section('acoes')
    <a href="{{ route('funcionarios.create') }}" class="btn btn-primary">Novo funcionário</a>
@endsection

@section('conteudo')
    <x-busca :rota="route('funcionarios.index')" placeholder="Nome ou CPF…">
        <select name="tipo" class="form-select" style="max-width: 11rem;" aria-label="Filtrar por tipo">
            <option value="">Todos os tipos</option>
            @foreach ($tipos as $valor => $rotulo)
                <option value="{{ $valor }}" @selected((string) request('tipo') === (string) $valor)>
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
                        <th>Nome</th>
                        <th style="width: 9.5rem;">CPF</th>
                        <th style="width: 12rem;">Tipo</th>
                        <th>Cargo</th>
                        <th style="width: 7rem;">Situação</th>
                        <th style="width: 11rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($funcionarios as $funcionario)
                        <tr>
                            <td>{{ $funcionario->nome }}</td>
                            <td class="text-nowrap">{{ $funcionario->cpf_formatado }}</td>
                            <td>
                                {{ $funcionario->tipo->rotulo() }}
                                @if ($funcionario->cnhVencida())
                                    <span class="badge text-bg-danger ms-1">CNH vencida</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $funcionario->cargo }}</td>
                            <td>
                                <span class="badge text-bg-{{ $funcionario->ativo ? 'success' : 'secondary' }}">{{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('funcionarios.edit', $funcionario) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <x-botao-excluir :acao="route('funcionarios.destroy', $funcionario)" :confirmacao="'Excluir o funcionário ' . $funcionario->nome . '?'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tabela-vazia">
                                @if (request('busca') || request('tipo'))
                                    Nenhum funcionário encontrado com estes filtros.
                                @else
                                    Nenhum funcionário cadastrado ainda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $funcionarios->links() }}
    </div>
@endsection