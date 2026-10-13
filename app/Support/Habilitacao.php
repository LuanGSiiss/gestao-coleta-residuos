<?php

namespace App\Support;

use App\Models\Veiculo;
use App\Models\Funcionario;
use Carbon\CarbonInterface;

/**
 * Condições da CNH para alocar um motorista num veículo.
 */
final class Habilitacao
{
    /**
     * Retorna os impedimentos para alocação. Sendo eles: CNH vencida, ou categoria que não permite conduzir o veículo.
     */
    public static function impedimentos(Funcionario $motorista, Veiculo $veiculo): array
    {
        $impedimentos = [];

        if ($motorista->cnhVencida()) {
            $impedimentos[] = "A CNH de {$motorista->nome} venceu em {$motorista->cnh_validade->format('d/m/Y')}. Atualize a validade no cadastro do funcionário ou escolha outro motorista." ;
        }

        if (!$motorista->habilitadoPara($veiculo)) {
            $impedimentos[] = "O veículo {$veiculo->placa_formatada} é do tipo {$veiculo->tipo->rotulo()} e exige CNH categoria C ou superior. Porém, a do {$motorista->nome} é {$motorista->cnh_categoria}. ";
        }

        return $impedimentos;
    }

    /**
     * Retorna os avisos sobre a habilitação, não sendo bloqueantes.
     */
    public static function aviso(Funcionario $motorista, CarbonInterface $termino): ?string
    {
        if ($motorista->cnh_validade === null) {
            return null;
        }

        $validade = $motorista->cnh_validade->format('d/m/Y');

        if ($motorista->cnhVencida()) {
            return "Atenção: a CNH de {$motorista->nome} venceu em {$validade}.";
        }

        if ($motorista->cnhVenceAntesDe($termino)) {
            return "Atenção: a CNH de {$motorista->nome} vence em {$validade}, antes do término da programação, em {$termino->format('d/m/Y')}.";
        }

        return null;
    }
}