@use('App\Support\Retorno')
@extends('layouts.app')
@section('titulo', 'Mapa das rotas')
@section('subtitulo', 'Trajeto vigente de cada rota')

@push('scripts')
    @vite('resources/js/mapa/index.js')
@endpush

@section('acoes')
    <a href="{{ route('rotas.index') }}" class="btn btn-outline-secondary">Lista de rotas</a>
@endsection

@section('conteudo')
    <form method="GET" action="{{ route('rotas.mapa') }}" class="row g-2 align-items-center mb-3">
        <div class="col-sm-4">
            <select name="bairro" class="form-select" aria-label="Filtrar por bairro">
                <option value="">Todos os bairros</option>
                @foreach ($bairros as $id => $nome)
                    <option value="{{ $id }}" @selected((string) request('bairro') === (string) $id)>{{ $nome }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-3">
            <select name="situacao" class="form-select" aria-label="Filtrar por situação">
                <option value="ativas" @selected($situacao === 'ativas')>Rotas ativas</option>
                <option value="inativas" @selected($situacao === 'inativas')>Rotas inativas</option>
                <option value="todas" @selected($situacao === 'todas')>Todas as rotas</option>
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filtrar</button>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-9">
            <div class="card">
                <div class="mapa-visualizacao" data-mapa="visualizacao" data-dados="mapa-geral-dados"></div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-header small fw-semibold">Legenda</div>
                <ul class="list-group list-group-flush small">
                    @forelse ($rotas as $rota)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                            <a href="{{ Retorno::link('rotas.show', $rota) }}" class="text-decoration-none">
                                <strong>{{ $rota->identificacao }}</strong> {{ $rota->nome }}
                            </a>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">Nenhuma rota com estes filtros.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <script type="application/json" id="mapa-geral-dados">@json($mapa)</script>
@endsection