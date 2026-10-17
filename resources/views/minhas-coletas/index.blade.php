@use('App\Enums\DiaSemana')
@use('App\Enums\SituacaoExecucao')
@extends('layouts.app')
@section('titulo', 'Minhas coletas')
@section('subtitulo', 'Hoje, ' . mb_strtolower(DiaSemana::daData(today())->rotulo()) . ', ' . today()->format('d/m/Y'))

@section('conteudo')
    @forelse ($execucoes as $execucao)
        @php $rota = $execucao->rotaVersao->rota; @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="fs-5 fw-semibold">
                            {{ $execucao->hora_inicio_prevista }} às {{ $execucao->hora_fim_prevista }}
                        </div>

                        @unless ($execucao->data->isToday())
                            <div class="small text-danger">
                                Coleta de {{ $execucao->data->format('d/m/Y') }}, ainda em andamento
                            </div>
                        @endunless
                    </div>

                    <div><x-situacao-execucao :execucao="$execucao" /></div>
                </div>

                <dl class="dados mb-3">
                    <dt>Rota</dt>
                    <dd>
                        <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                        <span class="ms-1 fw-semibold">{{ $rota->identificacao }}</span> — {{ $rota->nome }}
                        <span class="text-body-secondary">· coleta {{ mb_strtolower($execucao->programacao->tipo_coleta->rotulo()) }}</span>
                    </dd>

                    <dt>Veículo</dt>
                    <dd>{{ $execucao->veiculo->placa_formatada }} · {{ $execucao->veiculo->modelo }}</dd>

                    <dt>Coletores</dt>
                    <dd>{{ $execucao->coletores->pluck('nome')->join(', ') }}</dd>

                    @if ($execucao->orientacoes)
                        <dt>Orientações</dt>
                        <dd class="texto-preservado">{{ $execucao->orientacoes }}</dd>
                    @endif

                    @if ($execucao->situacao === SituacaoExecucao::CANCELADA)
                        <dt>Motivo do cancelamento</dt>
                        <dd class="texto-preservado">{{ $execucao->motivo_cancelamento }}</dd>
                    @endif

                    @if ($execucao->ocorrencias_count > 0)
                        <dt>Ocorrências</dt>
                        <dd>{{ $execucao->ocorrencias_count === 1 ? '1 registrada' : "{$execucao->ocorrencias_count} registradas" }}</dd>
                    @endif
                </dl>

                <div class="d-grid gap-2 d-sm-flex">
                    @unless ($execucao->impedimentoParaIniciar())
                        <a href="{{ route('coletas.inicio', $execucao) }}" class="btn btn-primary">Iniciar coleta</a>
                    @endunless
                    @unless ($execucao->impedimentoParaConcluir())
                        <a href="{{ route('coletas.conclusao', $execucao) }}" class="btn btn-success">Concluir coleta</a>
                    @endunless
                    <a href="{{ route('coletas.show', $execucao) }}" class="btn btn-outline-secondary">Mapa e detalhes</a>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body tabela-vazia">Nenhuma coleta para você hoje.</div>
        </div>
    @endforelse
@endsection