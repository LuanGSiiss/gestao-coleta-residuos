<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Comportamento do Voltar que leva para página anterior.
 *
 * A página anterior é a que o navegador informa ao abrir esta, pelo url()->previous() do
 * Laravel. Caso não possua onde voltar ou seja a propria tela, será encaminhado para o destino padrão informado.
 */
final class Voltar
{
    /** Nome do campo escondido dos formulários. */
    public const CAMPO = 'voltar';

    /** Nomes das rotas onde não devem se levadas. */
    private const FORMULARIOS = ['create', 'edit', 'prorrogacao', 'cancelamento', 'inicio', 'conclusao', 'login'];

    private const MAX_CARACTERES = 2000;

    /**
     * Retorna o endereço para voltar ou o padrão informado.
     */
    public static function url(string $padrao): string
    {
        $candidatos = [
            request()->input(self::CAMPO) ?? old(self::CAMPO),
            url()->previous(),
        ];

        foreach ($candidatos as $endereco) {
            if (self::aceita($endereco)) {
                return $endereco;
            }
        }

        return $padrao;
    }

    /**
     * Valida para que sejam aceitos apenas endereços deste sistema, de outra tela e que não seja formulário.
     */
    private static function aceita(mixed $endereco): bool
    {
        $raiz = request()->root();

        if (!is_string($endereco) || strlen($endereco) > self::MAX_CARACTERES || !str_starts_with($endereco, $raiz . '/')) {
            return false;
        }

        $tela = self::tela(substr($endereco, strlen($raiz)));

        return $tela !== null && $tela !== request()->route()?->getName() && !in_array(Str::afterLast($tela, '.'), self::FORMULARIOS, true);
    }

    /**
     * Retorna o nome da rota que responde a um caminho do sistema.
     */
    private static function tela(string $caminho): ?string
    {
        $pedido = Request::create($caminho);

        foreach (Route::getRoutes()->get('GET') as $rota) {
            if ($rota->matches($pedido)) {
                return $rota->getName();
            }
        }

        return null;
    }
}