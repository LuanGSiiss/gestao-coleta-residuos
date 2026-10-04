@extends('layouts.app')

@php $editando = $funcionario->exists; @endphp

@section('titulo', $editando ? 'Editar funcionário' : 'Novo funcionário')

@section('conteudo')
    <div class="card" style="max-width: 48rem;">
        <div class="card-body">
            <form method="POST" action="{{ $editando ? route('funcionarios.update', $funcionario) : route('funcionarios.store') }}"
                  x-data="{tipo: @js((string) old('tipo', $funcionario->tipo?->value)), motorista: @js((string) \App\Enums\TipoFuncionario::MOTORISTA->value)}">
                
                @csrf
                @if ($editando) 
                    @method('PUT') 
                @endif

                <div class="row">
                    <div class="col-md-8">
                        <x-form.input name="nome" label="Nome" :value="$funcionario->nome" required maxlength="120" autofocus />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="cpf" label="CPF" :value="$funcionario->cpf_formatado" required maxlength="14" inputmode="numeric" placeholder="000.000.000-00" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <x-form.input name="cargo" label="Cargo" :value="$funcionario->cargo" required maxlength="60" />
                    </div>
                    <div class="col-md-4">
                        <x-form.select name="tipo" label="Tipo" :opcoes="$tipos" :value="$funcionario->tipo?->value" required x-model="tipo" />
                    </div>
                    <div class="col-md-3">
                        <x-form.input name="carga_horaria_semanal" label="Horas semanais" type="number" :value="$funcionario->carga_horaria_semanal" required min="1" max="60" />
                    </div>
                </div>

                <fieldset class="border rounded p-3 mb-3" x-show="tipo === motorista" x-cloak>
                    <legend class="float-none w-auto px-2 fs-6 mb-0">Habilitação</legend>
                    <p class="small text-body-secondary mb-3">Obrigatória para motoristas.</p>

                    <div class="row">
                        <div class="col-md-5">
                            <x-form.input name="cnh" label="Número da CNH" :value="$funcionario->cnh" maxlength="11" inputmode="numeric" />
                        </div>
                        <div class="col-md-3">
                            <x-form.select name="cnh_categoria" label="Categoria" :opcoes="$categorias" :value="$funcionario->cnh_categoria" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="cnh_validade" label="Validade" type="date" :value="$funcionario->cnh_validade?->format('Y-m-d')" />
                        </div>
                    </div>
                </fieldset>

                <x-form.checkbox name="ativo" label="Funcionário ativo" :checked="$editando ? $funcionario->ativo : true" />

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                    <a href="{{ route('funcionarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection