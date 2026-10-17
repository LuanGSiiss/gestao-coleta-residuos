@extends('layouts.app')

@php $rota = $execucao->rotaVersao->rota; @endphp

@section('titulo', 'Concluir coleta')
@section('subtitulo', "Iniciada em {$execucao->iniciada_em->format('d/m/Y')} às {$execucao->iniciada_em->format('H:i')} · {$rota->identificacao} — {$rota->nome}")

@section('conteudo')
    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('coletas.concluir', $execucao) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-sm-7">
                        <x-form.input name="data" label="Data de término" type="date" required
                                      :value="today()->toDateString()"
                                      :min="$execucao->iniciada_em->toDateString()"
                                      :max="today()->toDateString()" />
                    </div>
                    <div class="col-sm-5">
                        <x-form.input name="hora" label="Hora" type="time" required :value="now()->format('H:i')" />
                    </div>
                </div>

                <x-form.input name="quilometragem" label="Quilometragem percorrida (km)" type="number" required step="0.01" min="0.01" max="1000" inputmode="decimal" />

                <x-form.input name="quantidade_kg" label="Quantidade coletada (kg)" type="number" required step="0.01" min="0" max="100000" inputmode="decimal" />

                <x-form.textarea name="observacoes" label="Observações" :linhas="3" maxlength="2000" />

                <div class="d-grid gap-2 d-sm-flex">
                    <button type="submit" class="btn btn-success btn-lg">Concluir coleta</button>
                    <a href="{{ route('coletas.show', $execucao) }}" class="btn btn-outline-secondary btn-lg">Voltar</a>
                </div>
            </form>
        </div>
    </div>
@endsection