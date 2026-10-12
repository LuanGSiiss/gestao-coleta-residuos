<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * Pilha de navegação das telas de rota, guardada no próprio endereço.
 *
 * A cada link que avança leva, no parâmetro "voltar", o endereço da tela atual, que já
 * carrega o endereço da anterior, e assim por diante.
 */
final class Retorno
{
    public const PARAMETRO = 'voltar';
    private const MAX_TELAS = 5;
    private const MAX_CARACTERES = 2000;

    /**
     * Retorna o endereço de uma rota nomeada levando junto o endereço da tela atual.
     */
    public static function link(string $nome, mixed $parametros = []): string
    {
        return route($nome, array_merge(
            Arr::wrap($parametros),
            [self::PARAMETRO => self::aparar(request()->fullUrl(), self::MAX_TELAS)],
        ));
    }

    /**
     * Retorna o endereço da rota levando junto o endereço da tela atual. Sem empilhar os endereços de volta.
     */
    public static function manter(string $nome, mixed $parametros = []): string
    {
        $voltar = self::atual();

        return route($nome, array_merge(
            Arr::wrap($parametros),
            $voltar === null ? [] : [self::PARAMETRO => $voltar],
        ));
    }

    /**
     * Retorna o endereço de retorno recebido na requisição, se não houver, retorna o padrão.
     */
    public static function destino(string $padrao): string
    {
        return self::atual() ?? $padrao;
    }

    /**
     * Retorna o endereço de retorno recebido na requisição.
     */
    public static function atual(): ?string
    {
        $voltar = request()->input(self::PARAMETRO);

        return self::valido($voltar) ? $voltar : null;
    }

    /**
     * Valida se o endereço é do próprio sistema.
     */
    private static function valido(mixed $endereco): bool
    {
        return is_string($endereco) && strlen($endereco) <= self::MAX_CARACTERES && str_starts_with($endereco, request()->getSchemeAndHttpHost() . '/');
    }

    /**
     * Mantém no endereço só as últimas telas da pilha, descartando as mais antigas.
     */
    private static function aparar(string $endereco, int $telas): string
    {
        [$base, $consulta] = array_pad(explode('?', $endereco, 2), 2, '');
        parse_str($consulta, $parametros);

        if (!array_key_exists(self::PARAMETRO, $parametros)) {
            return $endereco;
        }

        $anterior = $parametros[self::PARAMETRO];

        if ($telas <= 1 || !is_string($anterior)) {
            unset($parametros[self::PARAMETRO]);
        } else {
            $parametros[self::PARAMETRO] = self::aparar($anterior, $telas - 1);
        }

        return $parametros === [] ? $base : $base . '?' . http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
    }
}