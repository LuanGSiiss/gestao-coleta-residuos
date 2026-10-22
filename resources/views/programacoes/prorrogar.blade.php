@use('App\Support\Voltar')
@extends('layouts.app')

@php $voltar = Voltar::url(route('programacoes.show', $programacao)); @endphp

@section('titulo', "Prorrogar programação {$programacao->id}")
@section('subtitulo', $programacao->descricao)

@section('conteudo')
    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <dl class="dados mb-4">
                <dt>Período vigente</dt>
                <dd>{{ $programacao->periodo }}</dd>

                <dt>Dias e horário</dt>
                <dd>{{ $programacao->dias_abreviados }}, das {{ $programacao->horario }}</dd>
            </dl>

            <form method="POST" action="{{ route('programacoes.prorrogar', $programacao) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="{{ Voltar::CAMPO }}" value="{{ $voltar }}">

                <x-form.input name="data_fim" label="Nova data de término" type="date" required autofocus
                              :min="$programacao->data_fim->addDay()->toDateString()"
                              :max="$limite->toDateString()"
                              :ajuda="'Até ' . $limite->format('d/m/Y') . ', 12 meses depois do término vigente.'" />

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Prorrogar</button>
                    <a href="{{ $voltar }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection