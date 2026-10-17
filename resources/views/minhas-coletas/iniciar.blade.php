@extends('layouts.app')

@php $rota = $execucao->rotaVersao->rota; @endphp

@section('titulo', 'Iniciar coleta')
@section('subtitulo', "Hoje, das {$execucao->hora_inicio_prevista} às {$execucao->hora_fim_prevista} · {$rota->identificacao} — {$rota->nome}")

@section('conteudo')
    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <dl class="dados mb-4">
                <dt>Rota</dt>
                <dd>
                    <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                    <span class="ms-1">{{ $rota->identificacao }} — {{ $rota->nome }}</span>
                </dd>

                <dt>Veículo</dt>
                <dd>{{ $execucao->veiculo->placa_formatada }} · {{ $execucao->veiculo->modelo }}</dd>
            </dl>

            <form method="POST" action="{{ route('coletas.iniciar', $execucao) }}">
                @csrf
                @method('PUT')

                <x-form.input name="hora" label="Hora de início" type="time" required :value="now()->format('H:i')" ajuda="Se você começou antes de registrar, corrija para a hora em que começou." />

                <div class="d-grid gap-2 d-sm-flex">
                    <button type="submit" class="btn btn-primary btn-lg">Iniciar coleta</button>
                    <a href="{{ route('coletas.show', $execucao) }}" class="btn btn-outline-secondary btn-lg">Voltar</a>
                </div>
            </form>
        </div>
    </div>
@endsection