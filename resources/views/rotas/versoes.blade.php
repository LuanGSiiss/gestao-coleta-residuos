@use('App\Support\Retorno')
@extends('layouts.app')
@section('titulo', "Versões da rota {$rota->identificacao}")
@section('subtitulo', $rota->nome)

@push('scripts')
    @vite('resources/js/mapa/index.js')
@endpush

@section('acoes')
    <div class="d-flex gap-2">
        <a href="{{ Retorno::destino(route('rotas.show', $rota)) }}" class="btn btn-outline-secondary">Voltar</a>
        <a href="{{ Retorno::link('rotas.show', $rota) }}" class="btn btn-outline-secondary">Ver rota</a>
        <a href="{{ Retorno::link('rotas.edit', $rota) }}" class="btn btn-primary">Editar rota</a>
    </div>
@endsection

@section('conteudo')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="list-group list-group-flush">
                    @foreach ($versoes as $versao)
                        @php $atual = $versao->is($selecionada); @endphp

                        <a href="{{ Retorno::manter('rotas.versoes', [$rota, $versao->numero]) }}" 
                           class="list-group-item list-group-item-action {{ $atual ? 'active' : '' }}"
                           @if ($atual) aria-current="true" @endif>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Versão {{ $versao->numero }}</strong>
                                @if ($versao->id === $rota->versao_atual_id)
                                    <span class="badge text-bg-success">Vigente</span>
                                @endif
                            </div>
                            <div class="small">
                                {{ $versao->criada_em->format('d/m/Y H:i') }} · {{ $versao->usuario->nome }}
                            </div>
                            <div class="small">
                                {{ $versao->extensao_formatada }} · {{ $versao->trechos_count }} trecho(s)
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="mapa-visualizacao" data-mapa="visualizacao" data-dados="versao-dados"></div>

                <div class="card-footer small d-flex flex-wrap gap-3">
                    <span>
                        <span class="amostra-cor" style="background-color: {{ $rota->cor }}"></span>
                        Versão {{ $selecionada->numero }}
                    </span>

                    @if ($selecionada->id !== $rota->versao_atual_id)
                        <span class="text-body-secondary">
                            Tracejado cinza: versão vigente, para comparação
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="versao-dados">@json($mapa)</script>
@endsection