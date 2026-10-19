@extends('layouts.app')
@section('titulo', 'Indicadores')
@section('subtitulo', 'Coletas concluídas de ' . $periodo->descricao())

@push('scripts')
    @vite('resources/js/graficos/index.js')
@endpush

@section('conteudo')
    <form method="GET" action="{{ route('indicadores') }}" class="row g-2 align-items-center mb-2">
        <div class="col-sm-6 col-lg-3">
            <div class="input-group">
                <span class="input-group-text">De</span>
                <input type="date" name="de" value="{{ $periodo->de->toDateString() }}" class="form-control" aria-label="Data inicial">
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="input-group">
                <span class="input-group-text">Até</span>
                <input type="date" name="ate" value="{{ $periodo->ate->toDateString() }}" class="form-control" aria-label="Data final">
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <select name="rota" class="form-select" aria-label="Filtrar por rota">
                <option value="">Todas as rotas</option>
                @foreach ($rotas as $rota)
                    <option value="{{ $rota->id }}" @selected(($filtros['rota'] ?? null) === $rota->id)>
                        {{ $rota->identificacao }} — {{ $rota->nome }}{{ $rota->ativo ? '' : ' (inativa)' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-6 col-lg-2">
            <select name="tipo" class="form-select" aria-label="Filtrar por tipo de coleta">
                <option value="">Todos os tipos</option>
                @foreach ($tipos as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? null) === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-1 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Aplicar</button>
        </div>
    </form>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <span class="small text-body-secondary">Períodos:</span>
        @foreach ($atalhos as $rotulo => $atalho)
            <a href="{{ route('indicadores', [...$atalho->parametros(), ...$filtros]) }}"
               @class(['btn btn-sm btn-outline-secondary', 'active' => $atalho->igualA($periodo)])
               @if ($atalho->igualA($periodo)) aria-current="true" @endif>{{ $rotulo }}</a>
        @endforeach
    </div>

    @if ($alteracoes->isNotEmpty())
        <div class="alert alert-info">
            <div class="fw-semibold mb-1">Trajeto alterado no período</div>
            <ul class="mb-2 ps-3">
                @foreach ($alteracoes as $versao)
                    <li>
                        {{ $versao->rota->identificacao }} — {{ $versao->rota->nome }}: versão {{ $versao->numero }}, em {{ $versao->criada_em->format('d/m/Y') }}
                    </li>
                @endforeach
            </ul>
            <div class="small">
                Cada coleta entra nos indicadores com o trajeto que seguiu. Por isso, no gráfico da distância, cada versão tem a sua linha.
            </div>
        </div>
    @endif

    @if ($coletas === 0)
        <div class="card">
            <div class="card-body tabela-vazia">
                Nenhuma coleta concluída neste período{{ $filtros ? ' com estes filtros' : '' }}.
            </div>
        </div>
    @else
        <div class="row row-cols-2 row-cols-lg-4 g-2 mb-4">
            @foreach ($resumo as $rotulo => $valor)
                <div class="col">
                    <div class="indicador-resumo">
                        <span class="small text-body-secondary">{{ $rotulo }}</span>
                        <strong>{{ $valor }}</strong>
                    </div>
                </div>
            @endforeach
        </div>

        <x-grafico id="evolucao" titulo="Evolução da quantidade coletada"
                   subtitulo="Média por dia de cada semana, em toneladas, por tipo de coleta. Nas semanas que o período corta, conta só os dias dentro dele."
                   :grafico="$evolucao" />

        <x-grafico id="quantidade" titulo="Quantidade coletada por rota"
                   subtitulo="Soma do período, em toneladas."
                   :grafico="$quantidade" />

        <x-grafico id="tempo" titulo="Tempo médio de execução por rota"
                   subtitulo="Média por coleta. O previsto é o horário da execução; o realizado vai do início ao término registrados."
                   :grafico="$tempo" />

        <x-grafico id="distancia" titulo="Extensão do trajeto e quilometragem registrada"
                   subtitulo="Média por coleta. A quilometragem registrada inclui o deslocamento até a rota e até o destino dos resíduos."
                   :grafico="$distancia" />
    @endif
@endsection