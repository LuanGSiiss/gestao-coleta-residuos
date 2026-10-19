@props(['execucao'])
@use('App\Enums\SituacaoExecucao')
@use('App\Support\Retorno')

@php
    $rota = $execucao->rotaVersao->rota;
    $atrasada = $execucao->atrasada();
    $ocorrencias = $execucao->ocorrencias_count;
@endphp

{{-- Componente de um execução no painel. Representando uma execução apresentada no painel. --}}
<a href="{{ Retorno::link('execucoes.show', $execucao) }}"
   @class([
       'list-group-item list-group-item-action',
       'execucao-painel-atrasada' => $atrasada,
       'execucao-painel-ocorrencia' => $ocorrencias > 0,
   ])>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
        <span class="fw-semibold">{{ $execucao->hora_inicio_prevista }}–{{ $execucao->hora_fim_prevista }}</span>

        @if ($atrasada || $ocorrencias > 0)
            <span class="d-flex flex-wrap gap-1">
                @if ($atrasada)
                    <span class="badge text-bg-warning">Atrasada</span>
                @endif
                @if ($ocorrencias > 0)
                    <span class="badge text-bg-danger">{{ $ocorrencias === 1 ? '1 ocorrência' : "{$ocorrencias} ocorrências" }}</span>
                @endif
            </span>
        @endif
    </div>

    <div class="small text-truncate">
        <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
        <span class="fw-semibold ms-1">{{ $rota->identificacao }}</span> — {{ $rota->nome }}
    </div>
    <div class="small text-body-secondary text-truncate">
        {{ $execucao->motorista->nome }} · {{ $execucao->veiculo->placa_formatada }}
    </div>

    @if ($execucao->situacao === SituacaoExecucao::EM_ANDAMENTO)
        <div class="small">Iniciada às {{ $execucao->iniciada_em->format('H:i') }}</div>
    @elseif ($execucao->situacao === SituacaoExecucao::CONCLUIDA)
        <div class="small">
            {{ $execucao->iniciada_em->format('H:i') }}–{{ $execucao->concluida_em->format('H:i') }} · {{ $execucao->tempo_total }}
        </div>
    @elseif ($execucao->situacao === SituacaoExecucao::CANCELADA)
        <div class="small text-body-secondary limite-2-linhas">{{ $execucao->motivo_cancelamento }}</div>
    @endif
</a>