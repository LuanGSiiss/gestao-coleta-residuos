@use('App\Enums\SituacaoExecucao')
@extends('layouts.app')

@php $rota = $execucao->rotaVersao->rota; @endphp

@section('titulo', $execucao->data->isToday() ? 'Coleta de hoje' : 'Coleta de ' . $execucao->data->format('d/m/Y'))
@section('subtitulo', "{$execucao->hora_inicio_prevista} às {$execucao->hora_fim_prevista} · {$rota->identificacao} — {$rota->nome}")

@push('scripts')
    @vite('resources/js/mapa/index.js')
@endpush

@section('acoes')
    <a href="{{ route('coletas.index') }}" class="btn btn-outline-secondary">Voltar</a>
@endsection

@section('conteudo')
    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-2"><x-situacao-execucao :execucao="$execucao" /></div>

            @if ($execucao->iniciada_em)
                <dl class="dados mb-0">
                    <dt>Início</dt>
                    <dd>{{ $execucao->iniciada_em->format('d/m/Y') }} às {{ $execucao->iniciada_em->format('H:i') }}</dd>

                    @if ($execucao->concluida_em)
                        <dt>Término</dt>
                        <dd>{{ $execucao->concluida_em->format('d/m/Y') }} às {{ $execucao->concluida_em->format('H:i') }}</dd>

                        <dt>Tempo total</dt>
                        <dd>{{ $execucao->tempo_total }}</dd>

                        <dt>Quilometragem e quantidade</dt>
                        <dd>{{ $execucao->quilometragem_formatada }} · {{ $execucao->quantidade_formatada }}</dd>

                        @if ($execucao->observacoes)
                            <dt>Observações</dt>
                            <dd class="texto-preservado">{{ $execucao->observacoes }}</dd>
                        @endif
                    @endif
                </dl>
            @endif

            @if ($execucao->situacao === SituacaoExecucao::CANCELADA)
                <dl class="dados mb-0 mt-2">
                    <dt>Motivo do cancelamento</dt>
                    <dd class="texto-preservado">{{ $execucao->motivo_cancelamento }}</dd>
                </dl>
            @endif

            @if (!$execucao->impedimentoParaIniciar() || !$execucao->impedimentoParaConcluir())
                <div class="d-grid gap-2 d-sm-flex mt-3">
                    @unless ($execucao->impedimentoParaIniciar())
                        <a href="{{ route('coletas.inicio', $execucao) }}" class="btn btn-primary btn-lg">Iniciar coleta</a>
                    @endunless
                    @unless ($execucao->impedimentoParaConcluir())
                        <a href="{{ route('coletas.conclusao', $execucao) }}" class="btn btn-success btn-lg">Concluir coleta</a>
                    @endunless
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="mapa-coleta" data-mapa="visualizacao" data-dados="coleta-mapa"></div>
        <div class="card-footer small text-body-secondary">
            <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
            Rota {{ $rota->identificacao }}, versão {{ $execucao->rotaVersao->numero }} do trajeto · {{ $execucao->rotaVersao->extensao_formatada }}
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header small fw-semibold">Programado</div>
        <div class="card-body">
            <dl class="dados mb-0">
                <dt>Tipo de coleta</dt>
                <dd>{{ $execucao->programacao->tipo_coleta->rotulo() }}</dd>

                <dt>Veículo</dt>
                <dd>{{ $execucao->veiculo->placa_formatada }} · {{ $execucao->veiculo->modelo }}</dd>

                <dt>Coletores</dt>
                <dd>{{ $execucao->coletores->pluck('nome')->join(', ') }}</dd>

                <dt>Orientações</dt>
                <dd class="texto-preservado">{{ $execucao->orientacoes ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <span class="small fw-semibold">Ocorrências</span>
            @unless ($execucao->impedimentoParaOcorrencia())
                <a href="{{ route('coletas.ocorrencias.create', $execucao) }}" class="btn btn-sm btn-outline-primary">Registrar</a>
            @endunless
        </div>

        <x-lista-ocorrencias :ocorrencias="$execucao->ocorrencias" />
    </div>

    <script type="application/json" id="coleta-mapa">@json($mapa)</script>
@endsection