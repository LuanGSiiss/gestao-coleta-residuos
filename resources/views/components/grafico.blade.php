@props(['id', 'titulo', 'subtitulo' => null, 'grafico'])

@php
    // A altura do gráfico de barras acompanha o número de linhas. A do gráfico de linha é fixa.
    $duasSeries = count($grafico['series']) > 1;
    $altura = $grafico['tipo'] === 'barras' ? count($grafico['rotulos']) * ($duasSeries ? 56 : 42) + ($duasSeries ? 40 : 8) : 300;
@endphp

<figure class="card mb-4">
    <div class="card-body">
        <figcaption class="mb-3">
            <h2 class="h6 mb-0">{{ $titulo }}</h2>
            @if ($subtitulo)
                <div class="small text-body-secondary">{{ $subtitulo }}</div>
            @endif
        </figcaption>

        <div class="grafico" style="height: {{ $altura }}px">
            <canvas data-grafico="{{ $grafico['tipo'] }}" data-dados="grafico-{{ $id }}" role="img" aria-label="{{ $titulo }}"></canvas>
        </div>
        <script type="application/json" id="grafico-{{ $id }}">@json($grafico)</script>
    </div>
</figure>