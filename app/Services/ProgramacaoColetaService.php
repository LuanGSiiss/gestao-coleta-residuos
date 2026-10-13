<?php

namespace App\Services;

use LogicException;

use App\Models\Rota;
use App\Models\Usuario;
use App\Enums\DiaSemana;
use App\Models\Execucao;
use App\Support\Alocacao;
use App\Support\Cronograma;
use Carbon\CarbonImmutable;
use App\Enums\SituacaoExecucao;
use App\Support\TravaExecucoes;
use App\Models\ProgramacaoColeta;
use App\Enums\SituacaoProgramacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Responsável pelos comportamentos que gravam programação e execuções.
 */
final class ProgramacaoColetaService
{
    /** Campos que mudam a alocação de uma execução e pedem verificação de conflito. */
    private const CAMPOS_DE_ALOCACAO = [
        'motorista_id',
        'veiculo_id',
        'hora_inicio_prevista',
        'hora_fim_prevista',
        'coletores',
    ];

    public function __construct(private readonly ConflitoAlocacao $conflitos)
    {
    }

    /**
     * Cadastra a programação nova e gera uma execução por data.
     *
     * @param DiaSemana[] $dias
     * @return int  Quantidade de execuções geradas.
     */
    public function cadastrar(ProgramacaoColeta $programacao, array $dados, array $coletores, array $dias): int
    {
        return DB::transaction(function () use ($programacao, $dados, $coletores, $dias) {
            TravaExecucoes::adquirir();

            $programacao->fill($dados);
            $programacao->situacao = SituacaoProgramacao::ATIVA;

            $datas = Cronograma::datas($programacao->data_inicio, $programacao->data_fim, $dias);

            $this->conflitos->verificar($this->plano($programacao, $coletores, $datas));

            $programacao->save();
            $programacao->diasSemana()->createMany(
                array_map(fn (DiaSemana $dia) => ['dia_semana' => $dia], $dias)
            );
            $programacao->coletores()->attach($coletores);

            $this->gerarExecucoes($programacao, $coletores, $datas);

            return count($datas);
        });
    }

    /**
     * Altera a programação e leva a alteração às execuções futuras não iniciadas.
     *
     * @return int Quantidade de execuções atualizadas.
     */
    public function alterar(ProgramacaoColeta $programacao, array $dados, array $coletores): int
    {
        return DB::transaction(function () use ($programacao, $dados, $coletores) {
            TravaExecucoes::adquirir();
            $this->recarregarAtiva($programacao);

            $antes = $programacao->dadosPrevistos();

            $programacao->fill($dados)->save();
            $programacao->coletores()->sync($coletores);

            $depois = $programacao->dadosPrevistos($coletores);

            $execucoes = $programacao->execucoes()
                ->naoIniciadas()
                ->futuras()
                ->with('coletores')
                ->orderBy('data')
                ->get();

            $mudancas = [];
            $plano = [];

            foreach ($execucoes as $execucao) {
                $atuais = $execucao->dadosPrevistos();
                $alteracoes = self::propagar($atuais, $antes, $depois);

                if ($alteracoes === []) {
                    continue;
                }

                $mudancas[] = [$execucao, $alteracoes];

                if (array_intersect_key($alteracoes, array_flip(self::CAMPOS_DE_ALOCACAO)) !== []) {
                    $plano[] = Alocacao::deDadosPrevistos($execucao->data->toDateString(), array_merge($atuais, $alteracoes));
                }
            }

            $this->conflitos->verificar($plano, $programacao->id);

            foreach ($mudancas as [$execucao, $alteracoes]) {
                $this->aplicar($execucao, $alteracoes);
            }

            return count($mudancas);
        });
    }

    /**
     * Estende o término e gera as execuções do período acrescentado. As existentes não sofrem alteração.
     *
     * @return int Quantidade de execuções geradas.
     */
    public function prorrogar(ProgramacaoColeta $programacao, CarbonImmutable $novoTermino): int
    {
        return DB::transaction(function () use ($programacao, $novoTermino) {
            TravaExecucoes::adquirir();
            $this->recarregarAtiva($programacao);

            if ($novoTermino->lte($programacao->data_fim)) {
                throw ValidationException::withMessages([
                    'data_fim' => "A programação foi prorrogada enquanto esta tela estava aberta e já vai até {$programacao->data_fim->format('d/m/Y')}.",
                ]);
            }

            $coletores = $programacao->coletores()->pluck('funcionario.id')->all();
            $datas = Cronograma::datas($programacao->inicioDaProrrogacao(), $novoTermino, $programacao->dias());

            $this->conflitos->verificar(
                $this->plano($programacao, $coletores, $datas),
                $programacao->id,
                'data_fim',
            );

            $programacao->data_fim = $novoTermino;
            $programacao->save();

            $this->gerarExecucoes($programacao, $coletores, $datas);

            return count($datas);
        });
    }

    /**
     * Encerra a programação e cancela as execuções futuras não iniciadas.
     *
     * @return int Quantidade de execuções canceladas.
     */
    public function encerrar(ProgramacaoColeta $programacao, Usuario $usuario): int
    {
        return DB::transaction(function () use ($programacao, $usuario) {
            TravaExecucoes::adquirir();
            $this->recarregarAtiva($programacao);

            $agora = CarbonImmutable::now();

            $canceladas = $programacao->execucoes()
                ->naoIniciadas()
                ->futuras()
                ->update([
                    'situacao'            => SituacaoExecucao::CANCELADA->value,
                    'motivo_cancelamento' => "Cancelada pelo encerramento da programação em {$agora->format('d/m/Y')} às {$agora->format('H:i')}, por {$usuario->nome}."
                ]);

            $programacao->situacao = SituacaoProgramacao::ENCERRADA;
            $programacao->encerrada_em = $agora;
            $programacao->save();

            return $canceladas;
        });
    }

    /**
     * Retorna os campos que uma execução futura recebe quando a programação muda.
     * A execução só recebe a mudança de um campo se ainda segue a programação nele, ou seja, se o valor dela é o que a programação tinha antes.
     *
     * @param  array $execucao Dados previstos atuais da execução.
     * @param  array $antes Dados previstos da programação antes da alteração.
     * @param  array $depois Dados previstos da programação depois da alteração.
     * @return array Campos para gravar.
     */
    public static function propagar(array $execucao, array $antes, array $depois): array
    {
        $grupos = [
            ['motorista_id'],
            ['veiculo_id'],
            ['hora_inicio_prevista', 'hora_fim_prevista'],
            ['orientacoes'],
            ['coletores'],
        ];

        $alteracoes = [];
        foreach ($grupos as $campos) {
            $valores = fn (array $dados) => array_map(fn (string $campo) => $dados[$campo], $campos);

            $mudouNaProgramacao = $valores($antes) !== $valores($depois);
            $execucaoSegue = $valores($execucao) === $valores($antes);

            if ($mudouNaProgramacao && $execucaoSegue) {
                foreach ($campos as $campo) {
                    $alteracoes[$campo] = $depois[$campo];
                }
            }
        }

        return $alteracoes;
    }

    /**
     * @param  CarbonImmutable[] $datas
     * @return Alocacao[]
     */
    private function plano(ProgramacaoColeta $programacao, array $coletores, array $datas): array
    {
        $previstos = $programacao->dadosPrevistos($coletores);

        return array_map(
            fn (CarbonImmutable $data) => Alocacao::deDadosPrevistos($data->toDateString(), $previstos),
            $datas,
        );
    }

    /**
     * Gera as execuções para as datas, com os dados da programação e a versão vigente do trajeto, todas na situação Não iniciada.
     *
     * @param CarbonImmutable[] $datas
     */
    private function gerarExecucoes(ProgramacaoColeta $programacao, array $coletores, array $datas): void
    {
        if ($datas === []) {
            return;
        }

        $previstos = $programacao->dadosPrevistos($coletores);
        $versao = $this->versaoVigente($programacao->rota_id);

        Execucao::insert(array_map(fn (CarbonImmutable $data) => [
            'programacao_coleta_id' => $programacao->id,
            'data'                  => $data->toDateString(),
            'hora_inicio_prevista'  => $previstos['hora_inicio_prevista'],
            'hora_fim_prevista'     => $previstos['hora_fim_prevista'],
            'motorista_id'          => $previstos['motorista_id'],
            'veiculo_id'            => $previstos['veiculo_id'],
            'rota_versao_id'        => $versao,
            'situacao'              => SituacaoExecucao::NAO_INICIADA->value,
            'orientacoes'           => $previstos['orientacoes'],
        ], $datas));

        $ids = $programacao->execucoes()
            ->whereIn('data', array_map(fn (CarbonImmutable $data) => $data->toDateString(), $datas))
            ->pluck('id');

        $linhas = [];
        foreach ($ids as $execucaoId) {
            foreach ($previstos['coletores'] as $funcionarioId) {
                $linhas[] = ['execucao_id' => $execucaoId, 'funcionario_id' => $funcionarioId];
            }
        }

        DB::table('execucao_coletor')->insert($linhas);
    }

    /** Grava as alterações numa execução. */
    private function aplicar(Execucao $execucao, array $alteracoes): void
    {
        if (array_key_exists('coletores', $alteracoes)) {
            $execucao->coletores()->sync($alteracoes['coletores']);
            unset($alteracoes['coletores']);
        }

        if ($alteracoes !== []) {
            $execucao->fill($alteracoes)->save();
        }
    }

    /** Retorna a versão vigente da rota */
    private function versaoVigente(int $rotaId): int
    {
        return Rota::whereKey($rotaId)->value('versao_atual_id') ?? throw new LogicException('A rota não tem trajeto.');
    }

    /** Lê novamente a programação do banco já sob a trava e confirma que continua ativa */
    private function recarregarAtiva(ProgramacaoColeta $programacao): void
    {
        $programacao->refresh();

        if (!$programacao->ativa()) {
            throw new LogicException('A programação está encerrada e não admite alterações.');
        }
    }
}