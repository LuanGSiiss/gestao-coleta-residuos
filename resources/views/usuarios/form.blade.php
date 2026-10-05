@extends('layouts.app')

@php $editando = $usuario->exists; @endphp

@section('titulo', $editando ? 'Editar usuário' : 'Novo usuário')

@section('conteudo')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $editando ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
                          x-data="{perfil: @js((string) old('perfil', $usuario->perfil?->value)), motorista: @js((string) \App\Enums\PerfilUsuario::MOTORISTA->value)
                          }">
                        @csrf
                        @if ($editando) 
                            @method('PUT') 
                        @endif

                        <x-form.codigo :valor="$usuario->id" />
                        <x-form.input name="nome" label="Nome" :value="$usuario->nome" required maxlength="120" autofocus />
                        <x-form.input name="email" label="E-mail" type="email" :value="$usuario->email" required maxlength="180" />
                        <x-form.select name="perfil" label="Perfil" :opcoes="$perfis" :value="$usuario->perfil?->value" required x-model="perfil" />

                        <div x-show="perfil === motorista" x-cloak>
                            @if ($motoristas->isEmpty())
                                <div class="alert alert-info small">
                                    Não há funcionário motorista ativo sem usuário. Cadastre o funcionário antes de criar este usuário.
                                </div>
                            @endif

                            <x-form.select name="funcionario_id" label="Funcionário vinculado" :opcoes="$motoristas" :value="$usuario->funcionario_id" />
                        </div>

                        <x-form.checkbox name="ativo" label="Usuário ativo" :checked="$editando ? $usuario->ativo : true" />

                        @unless ($editando)
                            <p class="small text-secondary">
                                Uma senha temporária será gerada e exibida após o cadastro.
                            </p>
                        @endunless

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($editando)
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6">Senha</h2>
                        <p class="small text-secondary">
                            @if ($usuario->deve_alterar_senha)
                                Este usuário ainda não definiu a própria senha.
                            @else
                                O usuário já definiu a própria senha.
                            @endif
                            Redefinir gera uma nova senha temporária, que precisará ser trocada no próximo acesso.
                        </p>

                        <form method="POST" action="{{ route('usuarios.redefinir-senha', $usuario) }}" 
                              data-confirmacao="Gerar uma nova senha temporária para {{ $usuario->nome }}?" 
                              data-confirmacao-acao="Redefinir senha"
                              data-confirmacao-estilo="warning">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning">Redefinir senha</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection