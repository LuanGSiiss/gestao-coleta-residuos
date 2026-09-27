@extends('layouts.app')

@php $editando = $marca->exists; @endphp

@section('titulo', $editando ? 'Editar marca' : 'Nova marca')

@section('conteudo')
    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <form method="POST" action="{{ $editando ? route('marcas.update', $marca) : route('marcas.store') }}">
                @csrf

                @if ($editando) 
                    @method('PUT') 
                @endif

                <x-form.input name="nome" label="Nome" :value="$marca->nome"required maxlength="60" autofocus />

                <x-form.checkbox name="ativo" label="Marca ativa" :checked="$marca->exists ? $marca->ativo : true" />

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                    <a href="{{ route('marcas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection