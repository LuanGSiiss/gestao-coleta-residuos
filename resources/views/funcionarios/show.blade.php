@use('App\Enums\TipoFuncionario')
@use('App\Support\Voltar')
@extends('layouts.app')
@section('titulo', $funcionario->nome)
@section('subtitulo', "{$funcionario->tipo->rotulo()} · {$funcionario->cargo}")

@section('acoes')
    <a href="{{ Voltar::url(route('funcionarios.index')) }}" class="btn btn-outline-secondary">Voltar</a>
@endsection

@section('conteudo')
    <div class="card" style="max-width: 48rem;">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.leitura label="Código" :valor="$funcionario->id" />
                </div>
                <div class="col-sm-8 col-md-9">
                    <x-form.leitura label="Situação">
                        <span class="badge text-bg-{{ $funcionario->ativo ? 'success' : 'secondary' }}">{{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}</span>
                    </x-form.leitura>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <x-form.leitura label="Nome" :valor="$funcionario->nome" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="CPF" :valor="$funcionario->cpf_formatado" />
                </div>
            </div>

            <div class="row">
                <div class="col-md-5">
                    <x-form.leitura label="Cargo" :valor="$funcionario->cargo" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Tipo" :valor="$funcionario->tipo->rotulo()" />
                </div>
                <div class="col-md-3">
                    <x-form.leitura label="Horas semanais" :valor="$funcionario->carga_horaria_semanal" />
                </div>
            </div>

            @if ($funcionario->tipo === TipoFuncionario::MOTORISTA)
                <fieldset class="border rounded p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 mb-0">Habilitação</legend>

                    <div class="row">
                        <div class="col-md-5">
                            <x-form.leitura label="Número da CNH" :valor="$funcionario->cnh" />
                        </div>
                        <div class="col-md-3">
                            <x-form.leitura label="Categoria" :valor="$funcionario->cnh_categoria" />
                        </div>
                        <div class="col-md-4">
                            <x-form.leitura label="Validade">
                                {{ $funcionario->cnh_validade?->format('d/m/Y') ?? '—' }}
                                @if ($funcionario->cnhVencida())
                                    <span class="badge text-bg-danger ms-1">Vencida</span>
                                @endif
                            </x-form.leitura>
                        </div>
                    </div>
                </fieldset>
            @endif
        </div>
    </div>
@endsection