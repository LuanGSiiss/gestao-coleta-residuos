<?php

namespace App\Support;

use App\Models\Veiculo;
use App\Models\Funcionario;
use Illuminate\Support\Collection;

/**
 * Opções de motorista, veículo e coletores dos formulários que alocam equipe. 
 * Utilizados na programação e na execução.
 */
final class OpcoesAlocacao
{
    public static function motoristas(?int $atual = null): array
    {
        return Funcionario::query()
            ->motoristas()
            ->where(fn ($query) => $query->where('ativo', true)->when($atual, fn ($query, $id) => $query->orWhere('id', $id)))
            ->orderBy('nome')
            ->get()
            ->mapWithKeys(fn (Funcionario $motorista) => [
                $motorista->id => $motorista->nome . ($motorista->ativo ? '' : ' (inativo)') . ($motorista->cnhVencida() ? ' — CNH vencida' : ''),
            ])
            ->all();
    }

    public static function veiculos(?int $atual = null): array
    {
        return Veiculo::query()
            ->where(fn ($query) => $query->where('ativo', true)->when($atual, fn ($query, $id) => $query->orWhere('id', $id)))
            ->orderBy('placa')
            ->get()
            ->mapWithKeys(fn (Veiculo $veiculo) => [
                $veiculo->id => "{$veiculo->placa_formatada} · {$veiculo->modelo} · {$veiculo->tipo->rotulo()}" . ($veiculo->ativo ? '' : ' (inativo)'),
            ])
            ->all();
    }

    public static function coletores(array $atuais = []): Collection
    {
        return Funcionario::query()
            ->coletores()
            ->where(fn ($query) => $query->where('ativo', true)->when($atuais, fn ($query, $ids) => $query->orWhereIn('id', $ids)))
            ->orderBy('nome')
            ->get();
    }
}