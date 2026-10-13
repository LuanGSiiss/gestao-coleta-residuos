@use('App\Enums\SituacaoExecucao')
@use('App\Support\Retorno')
@extends('layouts.app')
@section('titulo', "Programação {$programacao->id}")
@section('subtitulo', $programacao->descricao)

@section('acoes')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ Retorno::destino(route('programacoes.index')) }}" class="btn btn-outline-secondary">Voltar</a>

        @if ($programacao->ativa())
            <form method="POST" action="{{ route('programacoes.encerrar', $programacao) }}" data-confirmacao="{{ $confirmacao }}" data-confirmacao-acao="Encerrar">
                @csrf
                <input type="hidden" name="voltar" value="{{ Retorno::atual() }}">
                <button type="submit" class="btn btn-outline-danger">Encerrar</button>
            </form>

            <a href="{{ Retorno::link('programacoes.prorrogacao', $programacao) }}" class="btn btn-outline-secondary">Prorrogar</a>
            <a href="{{ Retorno::link('programacoes.edit', $programacao) }}" class="btn btn-primary">Editar</a>
        @endif
    </div>
@endsection

@section('conteudo')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <dl class="dados mb-0">
                        <dt>Código</dt>
                        <dd>{{ $programacao->id }}</dd>

                        <dt>Situação</dt>
                        <dd>
                            <span class="badge text-bg-{{ $programacao->situacao->cor() }}">{{ $programacao->situacao->rotulo() }}</span>
                            @if ($programacao->encerrada_em)
                                <span class="small text-body-secondary">em {{ $programacao->encerrada_em->format('d/m/Y H:i') }}</span>
                            @endif
                        </dd>

                        <dt>Tipo de coleta</dt>
                        <dd>{{ $programacao->tipo_coleta->rotulo() }}</dd>

                        <dt>Rota</dt>
                        <dd>
                            <span class="amostra-cor" style="background-color: {{ $programacao->rota->cor }}"></span>
                            <a href="{{ Retorno::link('rotas.show', $programacao->rota) }}" class="ms-1">{{ $programacao->rota->identificacao }} — {{ $programacao->rota->nome }}</a>
                        </dd>

                        <dt>Motorista</dt>
                        <dd>
                            {{ $programacao->motorista->nome }}
                            @unless ($programacao->motorista->ativo)
                                <span class="text-body-secondary">(inativo)</span>
                            @endunless
                            @if ($programacao->motorista->cnhVencida())
                                <span class="badge text-bg-danger ms-1">CNH vencida</span>
                            @endif
                        </dd>

                        <dt>Coletores</dt>
                        <dd>
                            @foreach ($programacao->coletores as $coletor)
                                <div>
                                    {{ $coletor->nome }}
                                    @unless ($coletor->ativo)
                                        <span class="text-body-secondary">(inativo)</span>
                                    @endunless
                                </div>
                            @endforeach
                        </dd>

                        <dt>Veículo</dt>
                        <dd>
                            {{ $programacao->veiculo->placa_formatada }} · {{ $programacao->veiculo->modelo }} · {{ $programacao->veiculo->tipo->rotulo() }}
                            @unless ($programacao->veiculo->ativo)
                                <span class="text-body-secondary">(inativo)</span>
                            @endunless
                        </dd>

                        <dt>Período</dt>
                        <dd>{{ $programacao->periodo }}</dd>

                        <dt>Dias e horário</dt>
                        <dd>{{ $programacao->dias_abreviados }}, das {{ $programacao->horario }}</dd>

                        <dt>Orientações</dt>
                        <dd class="texto-preservado">{{ $programacao->orientacoes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex flex-wrap gap-3 small">
                        @foreach (SituacaoExecucao::cases() as $situacao)
                            <span>
                                <span class="badge text-bg-{{ $situacao->cor() }}">{{ $resumo[$situacao->value] ?? 0 }}</span>
                                {{ $situacao->rotulo() }}
                            </span>
                        @endforeach

                        @if ($atrasadas > 0)
                            <span>
                                <span class="badge text-bg-warning">{{ $atrasadas }}</span>
                                Atrasada{{ $atrasadas === 1 ? '' : 's' }}
                            </span>
                        @endif
                    </div>

                    <ul class="nav nav-pills small">
                        @foreach (['proximas' => 'Próximas', 'anteriores' => 'Anteriores', 'todas' => 'Todas'] as $valor => $rotulo)
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ $periodo === $valor ? 'active' : '' }}"
                                   href="{{ Retorno::manter('programacoes.show', [$programacao, 'periodo' => $valor]) }}"
                                   @if ($periodo === $valor) aria-current="page" @endif>{{ $rotulo }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8.5rem;">Data</th>
                                <th style="width: 7.5rem;">Horário</th>
                                <th>Motorista</th>
                                <th>Coletores</th>
                                <th style="width: 6.5rem;">Veículo</th>
                                <th style="width: 6rem;">Trajeto</th>
                                <th style="width: 9rem;">Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($execucoes as $execucao)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $execucao->data->format('d/m/Y') }}
                                        <span class="text-secondary small">{{ $execucao->diaSemana()->abreviado() }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $execucao->hora_inicio_prevista }}–{{ $execucao->hora_fim_prevista }}</td>
                                    <td>{{ $execucao->motorista->nome }}</td>
                                    <td class="small">{{ $execucao->coletores->pluck('nome')->join(', ') }}</td>
                                    <td class="text-nowrap">{{ $execucao->veiculo->placa_formatada }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ Retorno::link('rotas.versoes', [$execucao->rotaVersao->rota, $execucao->rotaVersao->numero]) }}">
                                            {{ $execucao->rotaVersao->rota->identificacao }} v{{ $execucao->rotaVersao->numero }}
                                        </a>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge text-bg-{{ $execucao->situacao->cor() }}"
                                              @if ($execucao->motivo_cancelamento) title="{{ $execucao->motivo_cancelamento }}" @endif>
                                            {{ $execucao->situacao->rotulo() }}
                                        </span>
                                        @if ($execucao->atrasada())
                                            <span class="badge text-bg-warning">Atrasada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="tabela-vazia">
                                        @if ($periodo === 'proximas')
                                            Nenhuma execução de hoje em diante.
                                        @elseif ($periodo === 'anteriores')
                                            Nenhuma execução anterior a hoje.
                                        @else
                                            Nenhuma execução.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $execucoes->links() }}
            </div>
        </div>
    </div>
@endsection