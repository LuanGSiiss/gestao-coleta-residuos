@extends('layouts.visitante')
@section('titulo', 'Entrar')

@section('conteudo')
    <x-alertas />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <x-form.input name="email" label="E-mail" type="email" required autofocus />
        <x-form.input name="password" label="Senha" type="password" required />

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Manter conectado</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
@endsection