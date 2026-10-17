@use('App\Support\Retorno')
@use('App\Enums\SituacaoExecucao')
@extends('layouts.app')
@section('titulo', "Programação {$programacao->id}")
@section('subtitulo', $programacao->descricao)

@section('acoes')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ Retorno::destino(route('programacoes.index')) }}" class="btn btn-outline-secondary">Voltar</a>

        <a href="{{ Retorno::link('execucoes.index', $programacao->filtroDasExecucoes()) }}" class="btn btn-outline-secondary">Execuções</a>

        @if ($programacao->ativa())
            <form method="POST" action="{{ route('programacoes.encerrar', $programacao) }}" data-confirmacao="{{ $confirmacao }}" data-confirmacao-acao="Encerrar">
                @csrf
                <input type="hidden" name="voltar" value="{{ Retorno::atual() }}">
                <button type="submit" class="btn btn-outline-danger">Encerrar</button>
            </form>

            <a href="{{ Retorno::link('programacoes.prorrogacao', $programacao) }}" class="btn btn-outline-secondary">Prorrogar</a>
            <a href="{{ Retorno::link('programacoes.edit', $programacao) }}" class="btn btn-primary">Editar</a>
        @endif
    </div>
@endsection

@section('conteudo')
    <div class="card mb-3">
        <div class="card-header small fw-semibold">Execuções</div>
        <div class="card-body">
            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-2">
                @foreach (SituacaoExecucao::cases() as $situacao)
                    <div class="col">
                        <a href="{{ Retorno::link('execucoes.index', ['programacao' => $programacao->id, 'situacao' => $situacao->value]) }}"
                           class="total-situacao" style="--cor-situacao: var(--bs-{{ $situacao->cor() }})">
                            <span class="small">{{ $situacao->rotulo() }}</span>
                            <strong>{{ $resumo[$situacao->value] ?? 0 }}</strong>
                        </a>
                    </div>

                    @if ($situacao === SituacaoExecucao::NAO_INICIADA)
                        <div class="col">
                            <a href="{{ Retorno::link('execucoes.index', ['programacao' => $programacao->id, 'situacao' => 'atrasadas']) }}"
                               class="total-situacao" style="--cor-situacao: var(--bs-warning)">
                                <span class="small">Atrasada</span>
                                <strong>{{ $atrasadas }}</strong>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.leitura label="Código" :valor="$programacao->id" />
                </div>
                <div class="col-sm-8 col-md-9">
                    <x-form.leitura label="Situação">
                        <span class="badge text-bg-{{ $programacao->situacao->cor() }}">
                            {{ $programacao->situacao->rotulo() }}
                        </span>
                        @if ($programacao->encerrada_em)
                            <span class="small text-body-secondary ms-1">
                                em {{ $programacao->encerrada_em->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </x-form.leitura>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <x-form.leitura label="Descrição" :valor="$programacao->descricao" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Tipo de coleta" :valor="$programacao->tipo_coleta->rotulo()" />
                </div>
            </div>

            <x-form.leitura label="Rota">
                <span class="amostra-cor" style="background-color: {{ $programacao->rota->cor }}"></span>
                <a href="{{ Retorno::link('rotas.show', $programacao->rota) }}" class="ms-1">{{ $programacao->rota->identificacao }} — {{ $programacao->rota->nome }}</a>
                @unless ($programacao->rota->ativo)
                    <span class="text-body-secondary">(inativa)</span>
                @endunless
            </x-form.leitura>

            <x-form.leitura label="Orientações" :valor="$programacao->orientacoes" multilinha />
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header small fw-semibold">Equipe e veículo</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <x-form.leitura label="Motorista">
                        {{ $programacao->motorista->nome }}
                        @unless ($programacao->motorista->ativo)
                            <span class="text-body-secondary">(inativo)</span>
                        @endunless
                        @if ($programacao->motorista->cnhVencida())
                            <span class="badge text-bg-danger ms-1">CNH vencida</span>
                        @endif
                    </x-form.leitura>

                    <x-form.leitura label="Veículo">
                        {{ $programacao->veiculo->placa_formatada }} · {{ $programacao->veiculo->modelo }} · {{ $programacao->veiculo->tipo->rotulo() }}
                        @unless ($programacao->veiculo->ativo)
                            <span class="text-body-secondary">(inativo)</span>
                        @endunless
                    </x-form.leitura>
                </div>

                <div class="col-md-6">
                    <x-form.leitura label="Coletores">
                        @foreach ($programacao->coletores as $coletor)
                            <div>
                                {{ $coletor->nome }}
                                @unless ($coletor->ativo)
                                    <span class="text-body-secondary">(inativo)</span>
                                @endunless
                            </div>
                        @endforeach
                    </x-form.leitura>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header small fw-semibold">Cronograma</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <x-form.leitura label="Data de início" :valor="$programacao->data_inicio->format('d/m/Y')" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Data de término" :valor="$programacao->data_fim->format('d/m/Y')" />
                </div>
            </div>

            <x-form.leitura label="Dias da semana">
                <x-dias-semana :dias="$programacao->dias()" />
            </x-form.leitura>

            <div class="row">
                <div class="col-md-4">
                    <x-form.leitura label="Hora de início" :valor="$programacao->hora_inicio" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Hora de término" :valor="$programacao->hora_fim" />
                </div>
            </div>
        </div>
    </div>
@endsection