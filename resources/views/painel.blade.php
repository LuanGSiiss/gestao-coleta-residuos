@use('App\Enums\DiaSemana')
@use('App\Enums\SituacaoExecucao')
@extends('layouts.app')
@section('titulo', 'Painel do dia')
@section('subtitulo', DiaSemana::daData(today())->rotulo() . ', ' . today()->format('d/m/Y') . ' · atualizado às ' . now()->format('H:i'))

@section('acoes')
    <a href="{{ route('painel') }}" class="btn btn-outline-secondary">Atualizar</a>
@endsection

@section('conteudo')
    @php
        $quantas = fn (SituacaoExecucao $situacao) => $grupos->get($situacao->value, collect())->count();
    @endphp

    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-2 mb-3">
        @foreach (SituacaoExecucao::cases() as $situacao)
            <div class="col">
                <div class="total-situacao" style="--cor-situacao: var(--bs-{{ $situacao->cor() }})">
                    <span class="small">{{ $situacao->rotulo() }}</span><strong>{{ $quantas($situacao) }}</strong>
                </div>
            </div>

            @if ($situacao === SituacaoExecucao::NAO_INICIADA)
                <div class="col">
                    <div class="total-situacao" style="--cor-situacao: var(--bs-warning)">
                        <span class="small">Atrasada</span><strong>{{ $atrasadas }}</strong>
                    </div>
                </div>
            @endif
        @endforeach

        <div class="col">
            <div class="total-situacao" style="--cor-situacao: var(--bs-danger)">
                <span class="small">Com ocorrência</span><strong>{{ $comOcorrencia }}</strong>
            </div>
        </div>
    </div>

    @if ($emAndamentoAntes > 0 || $naoIniciadasAntes > 0)
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1">Pendências de dias anteriores</div>
            <ul class="mb-0 ps-3">
                @if ($emAndamentoAntes > 0)
                    <li>
                        {{ $emAndamentoAntes === 1 ? '1 execução continua em andamento.' : "{$emAndamentoAntes} execuções continuam em andamento." }}
                    </li>
                @endif
                @if ($naoIniciadasAntes > 0)
                    <li>
                        {{ $naoIniciadasAntes === 1 ? '1 execução não foi iniciada.' : "{$naoIniciadasAntes} execuções não foram iniciadas." }}
                    </li>
                @endif
            </ul>
        </div>
    @endif

    @if ($total === 0)
        <div class="card">
            <div class="card-body tabela-vazia">
                Nenhuma execução para hoje.
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            @foreach (SituacaoExecucao::cases() as $situacao)
                @php $lista = $grupos->get($situacao->value, collect()); @endphp

                <div class="col">
                    <section class="card h-100 coluna-painel" style="--cor-situacao: var(--bs-{{ $situacao->cor() }})" aria-labelledby="situacao-{{ $situacao->value }}">
                        <h2 class="card-header h6 mb-0 d-flex justify-content-between align-items-center"
                            id="situacao-{{ $situacao->value }}">
                            {{ $situacao->rotulo() }}
                            <span class="badge text-bg-{{ $situacao->cor() }}">{{ $lista->count() }}</span>
                        </h2>

                        @if ($lista->isEmpty())
                            <div class="card-body small text-body-secondary">Nenhuma.</div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach ($lista as $execucao)
                                    <x-execucao-painel :execucao="$execucao" />
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            @endforeach
        </div>
    @endif
@endsection