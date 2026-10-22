@use('App\Support\Voltar')
@extends('layouts.app')
@section('titulo', "Veículo {$veiculo->placa_formatada}")
@section('subtitulo', "{$veiculo->marca->nome} {$veiculo->modelo}")

@section('acoes')
    <a href="{{ Voltar::url(route('veiculos.index')) }}" class="btn btn-outline-secondary">Voltar</a>
@endsection

@section('conteudo')
    <div class="card" style="max-width: 48rem;">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.leitura label="Código" :valor="$veiculo->id" />
                </div>
                <div class="col-sm-8 col-md-9">
                    <x-form.leitura label="Situação">
                        <span class="badge text-bg-{{ $veiculo->ativo ? 'success' : 'secondary' }}">
                            {{ $veiculo->ativo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </x-form.leitura>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <x-form.leitura label="Placa" :valor="$veiculo->placa_formatada" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Marca">
                        {{ $veiculo->marca->nome }}
                        @unless ($veiculo->marca->ativo)
                            <span class="text-body-secondary">(inativa)</span>
                        @endunless
                    </x-form.leitura>
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Modelo" :valor="$veiculo->modelo" />
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <x-form.leitura label="Tipo" :valor="$veiculo->tipo->rotulo()" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Ano" :valor="$veiculo->ano" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Capacidade"
                                    :valor="$veiculo->capacidade_toneladas === null ? null : number_format((float) $veiculo->capacidade_toneladas, 2, ',', '.') . ' t'"
                                    vazio="Não informada" />
                </div>
            </div>
        </div>
    </div>
@endsection