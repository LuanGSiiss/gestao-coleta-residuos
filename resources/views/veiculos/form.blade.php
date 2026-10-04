@extends('layouts.app')

@php $editando = $veiculo->exists; @endphp

@section('titulo', $editando ? 'Editar veículo' : 'Novo veículo')

@section('conteudo')
    <div class="card" style="max-width: 48rem;">
        <div class="card-body">
            <form method="POST" action="{{ $editando ? route('veiculos.update', $veiculo) : route('veiculos.store') }}">
                
                @csrf
                @if ($editando) 
                    @method('PUT') 
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <x-form.input name="placa" label="Placa" :value="$veiculo->placa_formatada" required maxlength="8" placeholder="ABC1D23" style="text-transform: uppercase;" autofocus />
                    </div>
                    <div class="col-md-4">
                        <x-form.select name="marca_id" label="Marca" :opcoes="$marcas" :value="$veiculo->marca_id" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="modelo" label="Modelo" :value="$veiculo->modelo" required maxlength="80" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <x-form.select name="tipo" label="Tipo" :opcoes="$tipos" :value="$veiculo->tipo?->value" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="ano" label="Ano" type="number" :value="$veiculo->ano" required min="1950" :max="now()->year + 1" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="capacidade_toneladas" label="Capacidade (toneladas)" type="number" step="0.01" min="0" :value="$veiculo->capacidade_toneladas" />
                    </div>
                </div>

                <x-form.checkbox name="ativo" label="Veículo ativo" :checked="$editando ? $veiculo->ativo : true" />

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                    <a href="{{ route('veiculos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection