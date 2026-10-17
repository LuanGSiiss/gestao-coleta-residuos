@use('App\Support\Retorno')
@extends('layouts.app')
@section('titulo', 'Programações de coleta')
@section('subtitulo', 'Cronogramas recorrentes de coleta e as execuções geradas por eles')

@section('acoes')
    <a href="{{ Retorno::link('programacoes.create') }}" class="btn btn-primary">Nova programação</a>
@endsection

@section('conteudo')
    <form method="GET" action="{{ route('programacoes.index') }}" class="row g-2 align-items-center mb-3">
        <div class="col-sm-2">
            <input type="number" name="id" value="{{ request('id') }}" min="1" class="form-control" placeholder="Código" aria-label="Filtrar pelo código">
        </div>

        <div class="col-sm">
            <input type="search" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Descrição…" aria-label="Filtrar pela descrição">
        </div>

        <div class="col-sm-3">
            <select name="rota" class="form-select" aria-label="Filtrar por rota">
                <option value="">Todas as rotas</option>
                @foreach ($rotas as $rota)
                    <option value="{{ $rota->id }}" @selected((string) request('rota') === (string) $rota->id)>
                        {{ $rota->identificacao }} — {{ $rota->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-2">
            <select name="situacao" class="form-select" aria-label="Filtrar por situação">
                <option value="ativas" @selected($situacao === 'ativas')>Ativas</option>
                <option value="encerradas" @selected($situacao === 'encerradas')>Encerradas</option>
                <option value="todas" @selected($situacao === 'todas')>Todas</option>
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Buscar</button>

            @if (request()->anyFilled(['id', 'busca', 'rota']) || $situacao !== 'ativas')
                <a href="{{ route('programacoes.index') }}" class="btn btn-outline-secondary">Limpar</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 6rem;">Código</th>
                        <th>Descrição</th>
                        <th style="width: 7rem;">Rota</th>
                        <th style="width: 8rem;">Tipo</th>
                        <th>Dias</th>
                        <th style="width: 9rem;">Horário</th>
                        <th style="width: 13rem;">Período</th>
                        <th style="width: 7rem;">Situação</th>
                        <th style="width: 14rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programacoes as $programacao)
                        <tr>
                            <td class="text-secondary">{{ $programacao->id }}</td>
                            <td>
                                <a href="{{ Retorno::link('programacoes.show', $programacao) }}" class="fw-semibold">{{ $programacao->descricao }}</a>
                            </td>
                            <td class="text-nowrap">
                                <span class="amostra-cor" style="background-color: {{ $programacao->rota->cor }}"></span>
                                <span class="ms-1">{{ $programacao->rota->identificacao }}</span>
                            </td>
                            <td>{{ $programacao->tipo_coleta->rotulo() }}</td>
                            <td>{{ $programacao->dias_abreviados }}</td>
                            <td class="text-nowrap">{{ $programacao->horario }}</td>
                            <td class="text-nowrap">{{ $programacao->periodo }}</td>
                            <td>
                                <span class="badge text-bg-{{ $programacao->situacao->cor() }}">{{ $programacao->situacao->rotulo() }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ Retorno::link('programacoes.show', $programacao) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                                <a href="{{ Retorno::link('execucoes.index', $programacao->filtroDasExecucoes()) }}" class="btn btn-sm btn-outline-secondary">Execuções</a>
                                @if ($programacao->ativa())
                                    <a href="{{ Retorno::link('programacoes.edit', $programacao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tabela-vazia">
                                @if (request()->anyFilled(['id', 'busca', 'rota']) || $situacao !== 'ativas')
                                    Nenhuma programação encontrada com estes filtros.
                                @else
                                    Nenhuma programação ativa ainda.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $programacoes->links() }}
    </div>
@endsection