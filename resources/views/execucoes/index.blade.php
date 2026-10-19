@use('App\Support\Retorno')
@use('App\Enums\SituacaoExecucao')
@extends('layouts.app')
@section('titulo', 'Execuções')
@section('subtitulo', 'A coleta de cada dia')

@section('acoes')
    @if (Retorno::atual())
        <a href="{{ Retorno::atual() }}" class="btn btn-outline-secondary">Voltar</a>
    @endif
@endsection

@section('conteudo')
    <form method="GET" action="{{ route('execucoes.index') }}" class="row g-2 align-items-center mb-3">
        @if (Retorno::atual())
            <input type="hidden" name="voltar" value="{{ Retorno::atual() }}">
        @endif

        <div class="col-sm-3 col-lg-2">
            <input type="number" name="id" value="{{ request('id') }}" min="1" class="form-control" placeholder="Código" aria-label="Filtrar pelo código">
        </div>

        <div class="col-sm-5 col-lg-3">
            <div class="input-group">
                <span class="input-group-text">De</span>
                <input type="date" name="de" value="{{ $de }}" class="form-control" aria-label="Data inicial">
            </div>
        </div>

        <div class="col-sm-4 col-lg-3">
            <div class="input-group">
                <span class="input-group-text">Até</span>
                <input type="date" name="ate" value="{{ $ate }}" class="form-control" aria-label="Data final">
            </div>
        </div>

        <div class="col-lg-4">
            <select name="situacao" class="form-select" aria-label="Filtrar por situação">
                <option value="">Todas as situações</option>
                @foreach (SituacaoExecucao::cases() as $opcao)
                    <option value="{{ $opcao->value }}" @selected($situacao === (string) $opcao->value)>{{ $opcao->rotulo() }}</option>

                    @if ($opcao === SituacaoExecucao::NAO_INICIADA)
                        <option value="atrasadas" @selected($situacao === 'atrasadas')>Não iniciada e atrasada</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="col-lg-4">
            <select name="programacao" class="form-select" aria-label="Filtrar por programação">
                <option value="">Todas as programações</option>
                @foreach ($programacoes as $programacao)
                    <option value="{{ $programacao->id }}" @selected((string) request('programacao') === (string) $programacao->id)>
                        {{ $programacao->id }} — {{ $programacao->descricao }}{{ $programacao->ativa() ? '' : ' (encerrada)' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-4 col-lg-2">
            <select name="rota" class="form-select" aria-label="Filtrar por rota">
                <option value="">Todas as rotas</option>
                @foreach ($rotas as $rota)
                    <option value="{{ $rota->id }}" @selected((string) request('rota') === (string) $rota->id)>
                        {{ $rota->identificacao }} — {{ $rota->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-4 col-lg-3">
            <select name="motorista" class="form-select" aria-label="Filtrar por motorista">
                <option value="">Todos os motoristas</option>
                @foreach ($motoristas as $motorista)
                    <option value="{{ $motorista->id }}" @selected((string) request('motorista') === (string) $motorista->id)>
                        {{ $motorista->nome }}{{ $motorista->ativo ? '' : ' (inativo)' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-4 col-lg-3">
            <select name="veiculo" class="form-select" aria-label="Filtrar por veículo">
                <option value="">Todos os veículos</option>
                @foreach ($veiculos as $veiculo)
                    <option value="{{ $veiculo->id }}" @selected((string) request('veiculo') === (string) $veiculo->id)>
                        {{ $veiculo->placa_formatada }} · {{ $veiculo->modelo }}{{ $veiculo->ativo ? '' : ' (inativo)' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-4 col-lg-3">
            <select name="tipo" class="form-select" aria-label="Filtrar por tipo de coleta">
                <option value="">Todos os tipos de coleta</option>
                @foreach ($tipos as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected((string) request('tipo') === (string) $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-8 col-lg-9 d-flex flex-wrap align-items-center gap-2">
            <div class="form-check mb-0 me-2">
                <input type="checkbox" name="ocorrencias" value="1" id="filtro-ocorrencias" class="form-check-input" @checked(request()->boolean('ocorrencias'))>
                <label for="filtro-ocorrencias" class="form-check-label">Só com ocorrências</label>
            </div>

            <button type="submit" class="btn btn-outline-secondary">Buscar</button>
            <a href="{{ Retorno::manter('execucoes.index') }}" class="btn btn-outline-secondary">Limpar</a>

            <span class="small text-body-secondary ms-sm-auto">{{ $execucoes->total() === 1 ? '1 execução' : "{$execucoes->total()} execuções" }}</span>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5.5rem;">Código</th>
                        <th style="width: 8.5rem;">Data</th>
                        <th style="width: 7.5rem;">Horário</th>
                        <th>Programação e trajeto</th>
                        <th>Motorista e veículo</th>
                        <th style="width: 10rem;">Situação</th>
                        <th style="width: 9rem;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($execucoes as $execucao)
                        <tr>
                            <td class="text-secondary">{{ $execucao->id }}</td>
                            <td class="text-nowrap">
                                {{ $execucao->data->format('d/m/Y') }}
                                <span class="text-secondary small">{{ $execucao->diaSemana()->abreviado() }}</span>
                            </td>
                            <td class="text-nowrap">{{ $execucao->hora_inicio_prevista }}–{{ $execucao->hora_fim_prevista }}</td>
                            <td>
                                <a href="{{ Retorno::link('programacoes.show', $execucao->programacao) }}">{{ $execucao->programacao->descricao }}</a>
                                <div class="small text-nowrap">
                                    <span class="amostra-cor" style="background-color: {{ $execucao->rotaVersao->rota->cor }}"></span>
                                    <a href="{{ Retorno::link('rotas.versoes', [$execucao->rotaVersao->rota, $execucao->rotaVersao->numero]) }}">{{ $execucao->rotaVersao->rota->identificacao }}, versão {{ $execucao->rotaVersao->numero }}</a>
                                </div>
                            </td>
                            <td>
                                {{ $execucao->motorista->nome }}
                                <div class="small text-body-secondary">{{ $execucao->veiculo->placa_formatada }}</div>
                            </td>
                            <td>
                                <x-situacao-execucao :execucao="$execucao" />
                                @if ($execucao->ocorrencias_count > 0)
                                    <span class="badge text-bg-light border fw-normal">
                                        {{ $execucao->ocorrencias_count === 1 ? '1 ocorrência' : "{$execucao->ocorrencias_count} ocorrências" }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ Retorno::link('execucoes.show', $execucao) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                                @unless ($execucao->impedimentoParaAlterar())
                                    <a href="{{ Retorno::link('execucoes.edit', $execucao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tabela-vazia">Nenhuma execução encontrada com estes filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $execucoes->links() }}
    </div>
@endsection