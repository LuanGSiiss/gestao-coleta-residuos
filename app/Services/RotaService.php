<?php

namespace App\Services;

use App\Models\Rota;
use App\Models\Usuario;
use App\Support\Trajeto;
use App\Models\Execucao;
use App\Models\RotaVersao;
use App\Support\TravaExecucoes;
use Illuminate\Support\Facades\DB;

final class RotaService
{
    /**
     * Salva a rota e seus bairros, se o trajeto mudou, também cria uma nova versão.
     * Realiza tudo em uma transação, garantindo que não gere inconsistência em caso de falha me uma das partes.
     */
    public function salvar(Rota $rota, array $dados, array $bairros, Trajeto $trajeto, Usuario $usuario): Rota
    {
        return DB::transaction(function () use ($rota, $dados, $bairros, $trajeto, $usuario) {
            if ($rota->exists) {
                Rota::whereKey($rota->id)->lockForUpdate()->first();
            }

            $rota->fill($dados)->save();
            $rota->bairros()->sync($bairros);

            if ($this->trajetoMudou($rota, $trajeto)) {
                $this->criarVersao($rota, $trajeto, $usuario);
            }

            return $rota;
        });
    }

    /**
     * Retorna se o trajeto da rota mudou comparado ao trajeto passado.
     */
    private function trajetoMudou(Rota $rota, Trajeto $trajeto): bool
    {
        $atual = $rota->versaoAtual;

        if ($atual === null) {
            return true;
        }

        $atual->loadMissing('trechos');

        return !Trajeto::daVersao($atual)->igualA($trajeto);
    }

    private function criarVersao(Rota $rota, Trajeto $trajeto, Usuario $usuario): void
    {
        $versao = $rota->versoes()->create([
            'numero'          => ((int) $rota->versoes()->max('numero')) + 1,
            'extensao_metros' => round($trajeto->extensaoMetros(), 2),
            'usuario_id'      => $usuario->id,
        ]);

        $versao->trechos()->createMany(array_map(fn (array $pontos) => [
            'geometria'       => ['type' => 'LineString', 'coordinates' => $pontos],
            'extensao_metros' => round(Trajeto::extensaoTrecho($pontos), 2),
        ], $trajeto->trechos()));

        $rota->versaoAtual()->associate($versao);
        $rota->save();

        $this->atualizarExecucoesFuturas($rota, $versao);
    }

    /**
     * Atualiza as execuções futuras relacionadas a rota para que utilizem a nova versão. As demais continuam com a versão com que foram realizadas.
     */
    private function atualizarExecucoesFuturas(Rota $rota, RotaVersao $versao): void
    {
        TravaExecucoes::adquirir();

        Execucao::query()
            ->naoIniciadas()
            ->futuras()
            ->whereIn('rota_versao_id', RotaVersao::select('id')->where('rota_id', $rota->id))
            ->update(['rota_versao_id' => $versao->id]);
    }
}