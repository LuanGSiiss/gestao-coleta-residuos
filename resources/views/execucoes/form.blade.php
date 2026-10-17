@use('App\Support\Retorno')
@extends('layouts.app')
@section('titulo', "Alterar execução {$execucao->id}")
@section('subtitulo', $execucao->data->format('d/m/Y') . ', ' . mb_strtolower($execucao->diaSemana()->rotulo()) . " — {$execucao->programacao->descricao}")

@section('acoes')
    <div class="d-flex gap-2">
        <a href="{{ Retorno::destino(route('execucoes.show', $execucao)) }}" class="btn btn-outline-secondary">Voltar</a>
        <button type="submit" form="form-execucao" class="btn btn-primary">Salvar alterações</button>
    </div>
@endsection

@section('conteudo')
    @php $rota = $execucao->rotaVersao->rota; @endphp

    <form id="form-execucao" method="POST" action="{{ route('execucoes.update', $execucao) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="voltar" value="{{ Retorno::atual() }}">

        <div class="alert alert-info">A alteração vale só para esta execução.</div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4 col-md-3">
                        <x-form.leitura label="Código" :valor="$execucao->id" />
                    </div>
                    <div class="col-sm-8 col-md-4">
                        <x-form.leitura label="Data" :valor="$execucao->data->format('d/m/Y') . ', ' . mb_strtolower($execucao->diaSemana()->rotulo())" />
                    </div>
                    <div class="col-md-5">
                        <x-form.leitura label="Situação">
                            <x-situacao-execucao :execucao="$execucao" />
                        </x-form.leitura>
                    </div>
                </div>

                <x-form.leitura label="Programação">
                    {{ $execucao->programacao->id }} — {{ $execucao->programacao->descricao }}
                    <span class="text-body-secondary">· {{ $execucao->programacao->tipo_coleta->rotulo() }}</span>
                </x-form.leitura>

                <x-form.leitura label="Rota e trajeto">
                    <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                    <span class="ms-1">{{ $rota->identificacao }} — {{ $rota->nome }}</span>· versão {{ $execucao->rotaVersao->numero }}
                </x-form.leitura>

                <p class="small text-body-secondary mb-0">
                    A data e a rota vêm da programação e não mudam na execução.
                </p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header small fw-semibold">Equipe e veículo</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="motorista_id" label="Motorista" :opcoes="$motoristas" :value="$execucao->motorista_id" required />

                        <x-form.select name="veiculo_id" label="Veículo" :opcoes="$veiculos" :value="$execucao->veiculo_id" required />
                    </div>

                    <div class="col-md-6">
                        <x-form.coletores :coletores="$coletores" :selecionados="$coletoresSelecionados" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header small fw-semibold">Horário e orientações</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <x-form.input name="hora_inicio_prevista" label="Hora de início prevista" type="time" :value="$execucao->hora_inicio_prevista" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="hora_fim_prevista" label="Hora de término prevista" type="time" :value="$execucao->hora_fim_prevista" required />
                    </div>
                </div>

                <x-form.textarea name="orientacoes" label="Orientações" :value="$execucao->orientacoes" :linhas="3" maxlength="2000" />
            </div>
        </div>
    </form>
@endsection