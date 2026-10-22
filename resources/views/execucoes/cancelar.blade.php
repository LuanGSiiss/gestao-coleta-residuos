@use('App\Enums\SituacaoExecucao')
@use('App\Support\Voltar')
@extends('layouts.app')

@php $voltar = Voltar::url(route('execucoes.show', $execucao)); @endphp

@section('titulo', "Cancelar execução {$execucao->id}")
@section('subtitulo', $execucao->data->format('d/m/Y') . ', ' . mb_strtolower($execucao->diaSemana()->rotulo()) . " — {$execucao->programacao->descricao}")

@section('conteudo')
    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <dl class="dados mb-4">
                <dt>Horário previsto</dt>
                <dd>{{ $execucao->hora_inicio_prevista }} às {{ $execucao->hora_fim_prevista }}</dd>

                <dt>Rota</dt>
                <dd>
                    <span class="amostra-cor" style="background-color: {{ $execucao->rotaVersao->rota->cor }}"></span>
                    <span class="ms-1">{{ $execucao->rotaVersao->rota->identificacao }} — {{ $execucao->rotaVersao->rota->nome }}</span>
                </dd>

                <dt>Motorista</dt>
                <dd>{{ $execucao->motorista->nome }}</dd>

                <dt>Situação</dt>
                <dd><x-situacao-execucao :execucao="$execucao" /></dd>
            </dl>

            @if ($execucao->situacao === SituacaoExecucao::EM_ANDAMENTO)
                <div class="alert alert-warning">
                    Esta coleta está em andamento desde {{ $execucao->iniciada_em->format('d/m/Y') }} às
                    {{ $execucao->iniciada_em->format('H:i') }}. O início continua registrado, mas a coleta
                    não poderá mais ser concluída.
                </div>
            @endif

            <form method="POST" action="{{ route('execucoes.cancelar', $execucao) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="{{ Voltar::CAMPO }}" value="{{ $voltar }}">

                <x-form.textarea name="motivo" label="Motivo do cancelamento" required :linhas="3" maxlength="500" autofocus />

                <p class="small text-body-secondary">
                    O cancelamento não pode ser desfeito e a execução deixa de contar nos indicadores.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-danger">Cancelar execução</button>
                    <a href="{{ $voltar }}" class="btn btn-outline-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
@endsection