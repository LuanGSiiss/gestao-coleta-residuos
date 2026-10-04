@extends('layouts.visitante')
@section('titulo', 'Definir senha')

@section('conteudo')
    <h2 class="h5 mb-2">Defina sua senha</h2>
    <p class="text-secondary small mb-4">
        Você entrou com uma senha temporária. Para continuar, escolha uma senha própria, com pelo menos oito caracteres e diferente da temporária.
    </p>

    <x-alertas />
    <form method="POST" action="{{ route('senha.temporaria.update') }}">
        @csrf
        @method('PUT')

        <x-form.input name="password" label="Nova senha" type="password" required minlength="8" autocomplete="new-password" autofocus />
        <x-form.input name="password_confirmation" label="Confirme a nova senha" type="password" required minlength="8" autocomplete="new-password" />

        <button type="submit" class="btn btn-primary w-100">Definir senha</button>
    </form>
    
    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-secondary">Sair</button>
    </form>
@endsection