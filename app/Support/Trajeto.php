<?php

namespace App\Support;

use JsonException;
use InvalidArgumentException;

use App\Models\RotaVersao;

/**
 * Classe de Trajeto de uma rota
 * Sendo um conjunto de trechos, cada um uma sequência de pontos.
 * 
 * Os pontos seguem a ordem do GeoJSON "[longitude, latitude]", que é a inversa da usada normalmente e pelo Leaflet.
 */
final class Trajeto
{
    /**
     * Raio da Terra em metros, sendo o mesmo que o Leaflet usa, para que a extensão exibida durante o desenho seja exatamente a que fica gravada.
     */
    private const RAIO_TERRA = 6371000;

    /** Define as casas decimais de precisão. */
    private const CASAS_DECIMAIS = 6;

    private const MAX_TRECHOS = 1000;
    private const MAX_PONTOS_POR_TRECHO = 5000;

    /** @param list<list<array{0: float, 1: float}>> $trechos */
    private function __construct(private readonly array $trechos)
    {
    }

    /**
     * Valida o GeoJSON enviado pelo mapa, lançando uma mensagem para o usuário quando algo está errado.
     */
    public static function deGeoJson(string $json): self
    {
        try {
            $dados = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('O trajeto enviado não é um GeoJSON válido.');
        }

        if (!is_array($dados) || !isset($dados['type'], $dados['features']) || ($dados['type']) !== 'FeatureCollection' || !is_array($dados['features'])) {
            throw new InvalidArgumentException('O trajeto deve ser uma coleção de trechos.');
        }

        $trechos = [];
        foreach ($dados['features'] as $feature) {
            $geometria = is_array($feature) ? ($feature['geometry'] ?? null) : null;

            // O recorte de um trecho devolve uma única camada com várias partes
            // (MultiLineString). Cada parte vira um trecho próprio.
            $linhas = match ($geometria['type'] ?? null) {
                'LineString'      => [$geometria['coordinates'] ?? null],
                'MultiLineString' => $geometria['coordinates'] ?? null,
                default           => throw new InvalidArgumentException('O trajeto só pode conter linhas.'),
            };

            if (!is_array($linhas)) {
                throw new InvalidArgumentException('Um dos trechos tem formato inválido.');
            }

            foreach ($linhas as $linha) {
                $pontos = self::normalizarLinha($linha);

                if ($pontos !== null) {
                    $trechos[] = $pontos;
                }
            }
        }

        if ($trechos === []) {
            throw new InvalidArgumentException('Desenhe ao menos um trecho no mapa.');
        }

        if (count($trechos) > self::MAX_TRECHOS) {
            throw new InvalidArgumentException('O trajeto tem trechos demais.');
        }

        return new self($trechos);
    }

    /** Remonta o trajeto de uma versão salva. Os trechos precisam estar carregados. */
    public static function daVersao(RotaVersao $versao): self
    {
        return new self(
            $versao->trechos
                ->map(fn ($trecho) => array_map(
                    fn (array $ponto) => [
                        round((float) $ponto[0], self::CASAS_DECIMAIS),
                        round((float) $ponto[1], self::CASAS_DECIMAIS),
                    ],
                    $trecho->geometria['coordinates'],
                ))
                ->values()
                ->all()
        );
    }

    /** @return array */
    public function trechos(): array
    {
        return $this->trechos;
    }

    /**
     * Retorna a extensão em metros do trajeto com base nos trechos.
     */
    public function extensaoMetros(): float
    {
        return array_sum(array_map(self::extensaoTrecho(...), $this->trechos));
    }

    /**
     * Realiza a soma das distâncias entre pontos consecutivos, pela fórmula de Haversine, que já considera a curvatura da Terra.
     */
    public static function extensaoTrecho(array $pontos): float
    {
        $total = 0.0;

        for ($i = 1, $n = count($pontos); $i < $n; $i++) {
            [$longitude1, $latitude1] = $pontos[$i - 1];
            [$longitude2, $latitude2] = $pontos[$i];

            $dLatitude  = deg2rad($latitude2 - $latitude1);
            $dLongitude = deg2rad($longitude2 - $longitude1);

            $a = sin($dLatitude / 2) ** 2 + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLongitude / 2) ** 2;

            $total += 2 * self::RAIO_TERRA * asin(min(1.0, sqrt($a)));
        }

        return $total;
    }

    /**
     * Compara o trecho passado por parâmetro com o atual.
     */
    public function igualA(self $outro): bool
    {
        return $this->trechos === $outro->trechos;
    }

    /** Converte para GeoJSON, no formato que o Leaflet utiliza. */
    public function paraFeatureCollection(array $propriedades = []): array
    {
        return [
            'type'     => 'FeatureCollection',
            'features' => array_map(fn (array $pontos) => [
                'type'       => 'Feature',
                'properties' => (object) $propriedades,
                'geometry'   => ['type' => 'LineString', 'coordinates' => $pontos],
            ], $this->trechos),
        ];
    }

    /**
     * Realiza a validação e arredondamento dos pontos de uma linha. Colocando null para linhas incorretas(sem dois pontos distintos)
     * sendo descartadas em vez de recusadas.
     * @return array
     */
    private static function normalizarLinha(mixed $linha): ?array
    {
        if (!is_array($linha) || count($linha) > self::MAX_PONTOS_POR_TRECHO) {
            throw new InvalidArgumentException('Um dos trechos tem formato inválido ou pontos demais.');
        }

        $pontos = [];
        foreach ($linha as $ponto) {
            if (!is_array($ponto) || !is_numeric($ponto[0] ?? null) || !is_numeric($ponto[1] ?? null)) {
                throw new InvalidArgumentException('Um dos trechos contém um ponto inválido.');
            }

            $longitude = round((float) $ponto[0], self::CASAS_DECIMAIS);
            $latitude  = round((float) $ponto[1], self::CASAS_DECIMAIS);

            if ($longitude < -180 || $longitude > 180 || $latitude < -90 || $latitude > 90) {
                throw new InvalidArgumentException('Um dos trechos contém coordenadas inválidas.');
            }

            // Pontos repetidos em sequência, como num duplo clique, não acrescentam nada.
            if ($pontos !== [] && $pontos[array_key_last($pontos)] === [$longitude, $latitude]) {
                continue;
            }

            $pontos[] = [$longitude, $latitude];
        }

        return count($pontos) >= 2 ? $pontos : null;
    }
}