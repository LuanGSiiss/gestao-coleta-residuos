@use('App\Enums\SituacaoExecucao')
@use('App\Support\Voltar')
@extends('layouts.app')

@php
    $agora = $execucao->data->isToday() || $execucao->situacao === SituacaoExecucao::EM_ANDAMENTO;
    $rota = $execucao->rotaVersao->rota;
@endphp

@section('titulo', 'Registrar ocorrência')
@section('subtitulo', "Coleta de {$execucao->data->format('d/m/Y')}, das {$execucao->hora_inicio_prevista} às {$execucao->hora_fim_prevista} · {$rota->identificacao} — {$rota->nome}")

@section('conteudo')
    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <form method="POST" action="{{ $acao }}">
                @csrf
                <input type="hidden" name="{{ Voltar::CAMPO }}" value="{{ $voltar }}">

                <x-form.input name="titulo" label="Título" required maxlength="120" autofocus placeholder="Rua interditada, veículo com defeito…" />

                <x-form.textarea name="descricao" label="Descrição" required :linhas="4" maxlength="2000" />

                <div class="row">
                    <div class="col-sm-7">
                        <x-form.input name="data" label="Data" type="date" required
                                      :value="($agora ? today() : $execucao->data)->toDateString()"
                                      :min="$execucao->data->toDateString()"
                                      :max="today()->toDateString()" />
                    </div>
                    <div class="col-sm-5">
                        <x-form.input name="hora" label="Hora" type="time" required :value="$agora ? now()->format('H:i') : null" />
                    </div>
                </div>

                <x-form.input name="endereco" label="Endereço" maxlength="200" placeholder="Rua e número, ou um ponto de referência" />

                <div class="d-grid gap-2 d-sm-flex">
                    <button type="submit" class="btn btn-primary">Registrar ocorrência</button>
                    <a href="{{ $voltar }}" class="btn btn-outline-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <p class="small text-body-secondary mt-3" style="max-width: 40rem;">Depois de registrada, a ocorrência não pode ser alterada nem excluída.</p>
@endsection