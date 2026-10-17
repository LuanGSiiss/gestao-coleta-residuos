@use('App\Support\Retorno')
@use('App\Enums\SituacaoExecucao')
@extends('layouts.app')
@section('titulo', "Execução {$execucao->id}")
@section('subtitulo', $execucao->data->format('d/m/Y') . ', ' . mb_strtolower($execucao->diaSemana()->rotulo()) . " — {$execucao->programacao->descricao}")

@section('acoes')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ Retorno::destino(route('execucoes.index')) }}" class="btn btn-outline-secondary">Voltar</a>

        @unless ($execucao->impedimentoParaCancelar())
            <a href="{{ Retorno::link('execucoes.cancelamento', $execucao) }}" class="btn btn-outline-danger">Cancelar execução</a>
        @endunless
        @unless ($execucao->impedimentoParaAlterar())
            <a href="{{ Retorno::link('execucoes.edit', $execucao) }}" class="btn btn-primary">Editar</a>
        @endunless
    </div>
@endsection

@section('conteudo')
    @php $rota = $execucao->rotaVersao->rota; @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.leitura label="Código" :valor="$execucao->id" />
                </div>
                <div class="col-sm-8 col-md-4">
                    <x-form.leitura label="Data" :valor="$execucao->data->format('d/m/Y') . ', ' . mb_strtolower($execucao->diaSemana()->rotulo())" />
                </div>
                <div class="col-md-5">
                    <x-form.leitura label="Situação">
                        <x-situacao-execucao :execucao="$execucao" />
                    </x-form.leitura>
                </div>
            </div>

            <x-form.leitura label="Programação">
                <a href="{{ Retorno::link('programacoes.show', $execucao->programacao) }}">{{ $execucao->programacao->id }} — {{ $execucao->programacao->descricao }}</a>
                <span class="text-body-secondary">· {{ $execucao->programacao->tipo_coleta->rotulo() }}</span>
            </x-form.leitura>

            <x-form.leitura label="Rota e trajeto">
                <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                <span class="ms-1">{{ $rota->identificacao }} — {{ $rota->nome }}</span> 
                · <a href="{{ Retorno::link('rotas.versoes', [$rota, $execucao->rotaVersao->numero]) }}">versão {{ $execucao->rotaVersao->numero }}</a>
                <span class="text-body-secondary">({{ $execucao->rotaVersao->extensao_formatada }})</span>
            </x-form.leitura>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header small fw-semibold">Equipe e veículo</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <x-form.leitura label="Motorista">
                        {{ $execucao->motorista->nome }}
                        @unless ($execucao->motorista->ativo)
                            <span class="text-body-secondary">(inativo)</span>
                        @endunless
                        @if ($execucao->motorista->cnhVencida())
                            <span class="badge text-bg-danger ms-1">CNH vencida</span>
                        @endif
                    </x-form.leitura>

                    <x-form.leitura label="Veículo">
                        {{ $execucao->veiculo->placa_formatada }} · {{ $execucao->veiculo->modelo }} · {{ $execucao->veiculo->tipo->rotulo() }}
                    </x-form.leitura>
                </div>

                <div class="col-md-6">
                    <x-form.leitura label="Coletores">
                        @foreach ($execucao->coletores as $coletor)
                            <div>{{ $coletor->nome }}</div>
                        @endforeach
                    </x-form.leitura>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header small fw-semibold">Horário e orientações</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <x-form.leitura label="Hora de início prevista" :valor="$execucao->hora_inicio_prevista" />
                </div>
                <div class="col-md-4">
                    <x-form.leitura label="Hora de término prevista" :valor="$execucao->hora_fim_prevista" />
                </div>
            </div>

            <x-form.leitura label="Orientações" :valor="$execucao->orientacoes" multilinha />
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header small fw-semibold">Realizado</div>
        <div class="card-body">
            @if ($execucao->situacao === SituacaoExecucao::NAO_INICIADA)
                <p class="text-body-secondary mb-0">
                    Ainda não iniciada{{ $execucao->atrasada() ? ', e o horário previsto de início já passou' : '' }}.
                </p>
            @else
                @if ($execucao->iniciada_em)
                    <div class="row">
                        <div class="col-md-4">
                            <x-form.leitura label="Início" :valor="$execucao->iniciada_em->format('d/m/Y H:i')" />
                        </div>
                        <div class="col-md-4">
                            <x-form.leitura label="Término" :valor="$execucao->concluida_em?->format('d/m/Y H:i')" />
                        </div>
                        <div class="col-md-4">
                            <x-form.leitura label="Tempo total" :valor="$execucao->tempo_total" />
                        </div>
                    </div>
                @endif

                @if ($execucao->situacao === SituacaoExecucao::CONCLUIDA)
                    <div class="row">
                        <div class="col-md-4">
                            <x-form.leitura label="Quilometragem percorrida" :valor="$execucao->quilometragem_formatada" />
                        </div>
                        <div class="col-md-4">
                            <x-form.leitura label="Quantidade coletada" :valor="$execucao->quantidade_formatada" />
                        </div>
                    </div>

                    <x-form.leitura label="Observações" :valor="$execucao->observacoes" multilinha />
                @endif

                @if ($execucao->situacao === SituacaoExecucao::CANCELADA)
                    <x-form.leitura label="Motivo do cancelamento" :valor="$execucao->motivo_cancelamento" multilinha />
                @endif
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <span class="small fw-semibold">Ocorrências</span>
            @unless ($execucao->impedimentoParaOcorrencia())
                <a href="{{ Retorno::link('execucoes.ocorrencias.create', $execucao) }}" class="btn btn-sm btn-outline-primary">Registrar ocorrência</a>
            @endunless
        </div>

        <x-lista-ocorrencias :ocorrencias="$execucao->ocorrencias" />
    </div>
@endsection