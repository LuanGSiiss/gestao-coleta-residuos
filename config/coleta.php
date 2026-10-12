<?php

return [
    'mapa' => [
        // Ponto inicial que o mapa irá abrir quando não há trajeto para enquadrar.
        // Por padrão Rio do Sul.
        'centro' => [
            (float) env('MAPA_LATITUDE', -27.2142),
            (float) env('MAPA_LONGITUDE', -49.6431),
        ],
        'zoom' => (int) env('MAPA_ZOOM', 14),
    ],

];