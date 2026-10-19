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
            <canvas data-grafico="{{ $grafico['tipo'] }}" data-dados="grafico-{{ $id }}" role="img" aria-label="{{ $titulo }}. Os números estão na tabela logo abaixo."></canvas>
        </div>
        <script type="application/json" id="grafico-{{ $id }}">@json($grafico)</script>

        <details class="mt-3">
            <summary class="small">Ver os números em tabela</summary>

            <div class="table-responsive mt-2">
                <table class="table table-sm align-middle mb-0 tabela-numeros">
                    <thead>
                        <tr>
                            <th>{{ $grafico['coluna'] }}</th>
                            @foreach ($grafico['series'] as $serie)
                                <th class="text-end">{{ $serie['nome'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grafico['rotulos'] as $i => $rotulo)
                            <tr>
                                <td>
                                    @isset($grafico['links'])
                                        <a href="{{ $grafico['links'][$i] }}">{{ $rotulo }}</a>
                                    @else
                                        {{ $grafico['titulos'][$i] ?? $rotulo }}
                                    @endisset
                                    @isset($grafico['detalhes'])
                                        <div class="small text-body-secondary">{{ $grafico['detalhes'][$i] }}</div>
                                    @endisset
                                </td>
                                @foreach ($grafico['series'] as $serie)
                                    <td class="text-end text-nowrap">{{ $serie['textos'][$i] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</figure>