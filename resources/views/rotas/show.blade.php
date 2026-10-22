@use('App\Support\Voltar')
@extends('layouts.app')
@section('titulo', "Rota {$rota->identificacao}")
@section('subtitulo', $rota->nome)

@push('scripts')
    @vite('resources/js/mapa/index.js')
@endpush

@section('acoes')
    <div class="d-flex gap-2">
        <a href="{{ Voltar::url(route('rotas.index')) }}" class="btn btn-outline-secondary">Voltar</a>
        <a href="{{ route('rotas.index') }}" class="btn btn-outline-secondary">Lista de rotas</a>
        <a href="{{ route('rotas.versoes', $rota) }}" class="btn btn-primary">Versões</a>
    </div>
@endsection

@section('conteudo')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <dl class="dados mb-0">
                        <dt>Código</dt>
                        <dd>{{ $rota->id }}</dd>

                        <dt>Identificação</dt>
                        <dd>
                            <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                            <span class="fw-semibold ms-1">{{ $rota->identificacao }}</span>
                        </dd>

                        <dt>Nome</dt>
                        <dd>{{ $rota->nome }}</dd>

                        <dt>Situação</dt>
                        <dd>
                            <span class="badge text-bg-{{ $rota->ativo ? 'success' : 'secondary' }}">{{ $rota->ativo ? 'Ativa' : 'Inativa' }}</span>
                        </dd>

                        <dt>Descrição</dt>
                        <dd class="texto-preservado">{{ $rota->descricao }}</dd>

                        <dt>Bairros de abrangência</dt>
                        <dd class="d-flex flex-wrap gap-1">
                            @foreach ($rota->bairros as $bairro)
                                <span class="badge text-bg-light border fw-normal">
                                    {{ $bairro->nome }}
                                    @unless ($bairro->ativo)
                                        <span class="text-body-secondary">(inativo)</span>
                                    @endunless
                                </span>
                            @endforeach
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header small fw-semibold">Trajeto vigente</div>
                <div class="card-body">
                    @if ($versao)
                        <dl class="dados mb-0">
                            <dt>Versão</dt>
                            <dd>{{ $versao->numero }} de {{ $totalVersoes }}</dd>

                            <dt>Extensão aproximada</dt>
                            <dd>{{ $versao->extensao_formatada }} em {{ $versao->trechos->count() }} trecho(s)</dd>

                            <dt>Registrada em</dt>
                            <dd>{{ $versao->criada_em->format('d/m/Y H:i') }}, por {{ $versao->usuario->nome }}</dd>
                        </dl>
                    @else
                        <p class="text-secondary mb-0">Esta rota ainda não tem trajeto.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="mapa-visualizacao" data-mapa="visualizacao" data-dados="rota-dados"></div>
            </div>
        </div>
    </div>

    <script type="application/json" id="rota-dados">@json($mapa)</script>
@endsection