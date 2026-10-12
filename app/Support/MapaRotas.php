<?php

namespace App\Support;

use App\Models\Rota;
use Illuminate\Support\Collection;

final class MapaRotas
{
    /**
     * Trajeto vigente de cada rota, como uma única coleção GeoJSON.
     * @param Collection<int, Rota> $rotas coleção de modelos de rotas com os trechos já carregados.
     */
    public static function colecao(Collection $rotas, bool $comLink = true): array
    {
        $features = [];

        foreach ($rotas as $rota) {
            if ($rota->versaoAtual === null) {
                continue;
            }

            $propriedades = [
                'identificacao' => $rota->identificacao,
                'nome'          => $rota->nome,
                'cor'           => $rota->cor,
                'extensao'      => $rota->versaoAtual->extensao_formatada
            ];

            if ($comLink) {
                $propriedades['url'] = Retorno::link('rotas.show', $rota);
            }

            array_push(
                $features,
                ...Trajeto::daVersao($rota->versaoAtual)->paraFeatureCollection($propriedades)['features']
            );
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }
}