@extends('layouts.app')

@php $editando = $bairro->exists; @endphp

@section('titulo', $editando ? 'Editar bairro' : 'Novo bairro')

@section('conteudo')
    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <form method="POST" action="{{ $editando ? route('bairros.update', $bairro) : route('bairros.store') }}">
                @csrf

                @if ($editando) 
                    @method('PUT') 
                @endif

                <div class="row">
                    <div class="col-sm-5">
                        <x-form.leitura label="Código" :valor="$bairro->id" vazio="0000" />
                    </div>
                </div>
                <x-form.input name="nome" label="Nome" :value="$bairro->nome" required maxlength="120" autofocus />

                <x-form.checkbox name="ativo" label="Bairro ativo" :checked="$bairro->exists ? $bairro->ativo : true" />

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                    <a href="{{ route('bairros.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection