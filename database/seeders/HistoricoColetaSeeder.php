<?php

namespace Database\Seeders;

use App\Enums\DiaSemana;
use App\Enums\SituacaoExecucao;
use App\Enums\SituacaoProgramacao;
use App\Enums\TipoColeta;
use App\Services\ExecucaoService;
use App\Support\Trajeto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Doze semanas de coletas já feitas, para ver o painel e os indicadores (RF25, RF26) com dados.
 *
 * Só para desenvolvimento e demonstração. Grava direto no banco, sem passar pelas telas nem
 * pelos serviços, porque o sistema, de propósito, não deixa registrar o passado: a programação
 * não começa antes de hoje (RN19) e a coleta só é iniciada no próprio dia (RN26).
 *
 * Usa os cadastros do CadastrosDesenvolvimentoSeeder e o gestor do UsuarioDesenvolvimentoSeeder
 * (bloco 2). Os números saem de um sorteio com semente fixa: num banco limpo, no mesmo dia, o
 * resultado se repete.
 *
 * Uso: php artisan db:seed --class=HistoricoColetaSeeder
 */
class HistoricoColetaSeeder extends Seeder
{
    private const SEMANAS = 12;

    /**
     * Há 15 dias, a rota D01 ganhou duas ruas: é a versão 2 do trajeto (RN35, RN40). A data fica
     * no meio dos últimos 30 dias, o período com que os indicadores abrem, e por isso eles
     * mostram coletas das duas versões em qualquer dia da semana.
     */
    private const DIAS_DESDE_A_ALTERACAO = 15;

    /**
     * As três rotas de demonstração. O trajeto é um zigue-zague de ruas paralelas, como o de
     * uma coleta que passa rua por rua. A quantidade e o tempo de cada coleta acompanham a
     * extensão do trajeto, com uma variação sorteada.
     *
     * "pendencia" é a coleta que o painel mostra entre as pendências de dias anteriores: a
     * mais recente até tantos dias atrás fica naquela situação.
     */
    private const ROTAS = [
        [
            'identificacao' => 'D01', 'nome' => 'Centro (demonstração)', 'cor' => '#1f77b4', 'bairro' => 'Centro',
            'origem' => [-27.2185, -49.6520], 'ruas' => 10, 'ruas_alteracao' => 12, 'comprimento' => 1300,
            'tipo' => TipoColeta::CONVENCIONAL, 'dias' => [DiaSemana::SEGUNDA, DiaSemana::QUARTA, DiaSemana::SEXTA],
            'inicio' => '07:00', 'fim' => '11:00',
            'motorista' => '29141777638', 'veiculo' => 'RSU1A23', 'coletores' => ['39825979194', '34167211017'],
            'kg_por_km' => 520, 'minutos_por_km' => 15, 'deslocamento_km' => 9,
            'pendencia' => null,
        ],
        [
            'identificacao' => 'D02', 'nome' => 'Jardim América (demonstração)', 'cor' => '#ff7f0e', 'bairro' => 'Jardim América',
            'origem' => [-27.2020, -49.6400], 'ruas' => 12, 'ruas_alteracao' => null, 'comprimento' => 1400,
            'tipo' => TipoColeta::CONVENCIONAL, 'dias' => [DiaSemana::TERCA, DiaSemana::QUINTA, DiaSemana::SABADO],
            'inicio' => '07:00', 'fim' => '11:00',
            'motorista' => '29141777638', 'veiculo' => 'RSU1A23', 'coletores' => ['39825979194', '34167211017'],
            'kg_por_km' => 340, 'minutos_por_km' => 14.5, 'deslocamento_km' => 11,
            'pendencia' => [SituacaoExecucao::EM_ANDAMENTO, 2],
        ],
        [
            'identificacao' => 'D03', 'nome' => 'Seletiva Norte (demonstração)', 'cor' => '#2ca02c', 'bairro' => 'Navegantes',
            'origem' => [-27.1950, -49.6560], 'ruas' => 8, 'ruas_alteracao' => null, 'comprimento' => 1000,
            'tipo' => TipoColeta::SELETIVA, 'dias' => [DiaSemana::TERCA, DiaSemana::SEXTA],
            'inicio' => '13:00', 'fim' => '16:00',
            'motorista' => '01152449303', 'veiculo' => 'MTL4521', 'coletores' => ['94580730224'],
            'kg_por_km' => 150, 'minutos_por_km' => 17, 'deslocamento_km' => 6,
            'pendencia' => [SituacaoExecucao::NAO_INICIADA, 7],
        ],
    ];

    /** O CPF do motorista Carlos, que tem usuário: ele registra as ocorrências das coletas dele. */
    private const CPF_CARLOS = '29141777638';

    private const MOTIVOS_CANCELAMENTO = [
        'chuva forte e alagamento no trajeto.',
        'caminhão em manutenção.',
        'bloqueio da via para evento.',
    ];

    private const OCORRENCIAS = [
        ['Rua interditada', 'Obra da companhia de saneamento. Desvio pela rua paralela.', 'Rua XV de Novembro, 410'],
        ['Contêiner danificado', 'Tampa quebrada no ponto da praça; resíduo espalhado.', null],
        ['Veículo bloqueando a via', 'Carro estacionado em fila dupla; espera de 15 minutos.', 'Rua Duque de Caxias, 88'],
        ['Resíduo fora do padrão', 'Entulho de obra junto com o lixo doméstico; não foi recolhido.', null],
    ];

    private Randomizer $sorteio;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Este seeder grava dados fictícios e não roda em produção.');

            return;
        }

        if (DB::table('rota')->where('identificacao', 'D01')->exists()) {
            $this->command->warn('O histórico de demonstração já existe (rota D01). Nada foi gravado.');

            return;
        }

        $gestor = DB::table('usuario')->where('email', 'gestor@coletarsu.test')->first();
        $pessoas = DB::table('funcionario')->pluck('id', 'cpf');
        $veiculos = DB::table('veiculo')->pluck('id', 'placa');
        $cpfs = array_merge(...array_map(fn (array $rota) => [$rota['motorista'], ...$rota['coletores']], self::ROTAS));

        if ($gestor === null || ! $pessoas->has($cpfs) || ! $veiculos->has(['RSU1A23', 'MTL4521'])) {
            $this->command->error('Faltam os cadastros de desenvolvimento. Rode antes: php artisan db:seed');

            return;
        }

        $autores = [
            'gestor'    => $gestor->id,
            'motorista' => DB::table('usuario')->where('email', 'motorista@coletarsu.test')->value('id') ?? $gestor->id,
        ];

        $this->sorteio = new Randomizer(new Mt19937(2026));

        $hoje = CarbonImmutable::today();
        $fim = $hoje->subDay();
        $inicio = $hoje->subDays($hoje->dayOfWeekIso - 1)->subWeeks(self::SEMANAS);   // uma segunda-feira
        $alteracao = $hoje->subDays(self::DIAS_DESDE_A_ALTERACAO)->setTime(6, 30);

        $total = 0;

        DB::transaction(function () use ($gestor, $pessoas, $veiculos, $autores, $hoje, $inicio, $fim, $alteracao, &$total) {
            foreach (self::ROTAS as $dados) {
                $versoes = $this->criarRota($dados, $gestor->id, $inicio, $alteracao);
                $coletores = array_map(fn (string $cpf) => $pessoas[$cpf], $dados['coletores']);

                [$situacaoPendente, $diasAtras] = $dados['pendencia'] ?? [null, null];
                $diaPendente = $situacaoPendente === null ? null : self::ultimoDia($dados['dias'], $hoje->subDays($diasAtras));

                $programacaoId = DB::table('programacao_coleta')->insertGetId([
                    'descricao'    => "Coleta {$dados['nome']}",
                    'rota_id'      => $versoes['rota_id'],
                    'motorista_id' => $pessoas[$dados['motorista']],
                    'veiculo_id'   => $veiculos[$dados['veiculo']],
                    'tipo_coleta'  => $dados['tipo']->value,
                    'data_inicio'  => $inicio->toDateString(),
                    'data_fim'     => $fim->toDateString(),
                    'hora_inicio'  => $dados['inicio'],
                    'hora_fim'     => $dados['fim'],
                    'orientacoes'  => null,
                    'situacao'     => SituacaoProgramacao::ATIVA->value,
                    'created_at'   => $inicio->subDays(3)->setTime(10, 0),
                    'updated_at'   => $inicio->subDays(3)->setTime(10, 0),
                ]);

                DB::table('programacao_coleta_dia_semana')->insert(array_map(
                    fn (DiaSemana $dia) => ['programacao_coleta_id' => $programacaoId, 'dia_semana' => $dia->value],
                    $dados['dias'],
                ));
                DB::table('programacao_coleta_coletor')->insert(array_map(
                    fn (int $coletor) => ['programacao_coleta_id' => $programacaoId, 'funcionario_id' => $coletor],
                    $coletores,
                ));

                // Uma execução para cada dia da programação (RN22), com a versão do trajeto
                // vigente naquele dia (RN36).
                for ($data = $inicio; $data->lte($fim); $data = $data->addDay()) {
                    if (! in_array(DiaSemana::daData($data), $dados['dias'], true)) {
                        continue;
                    }

                    $versao = $data->lt($alteracao->startOfDay()) ? $versoes['primeira'] : $versoes['atual'];
                    $realizado = $diaPendente !== null && $data->isSameDay($diaPendente)
                        ? $this->pendente($situacaoPendente, $dados, $data)
                        : $this->realizado($dados, $data, $versao['km'], $gestor->nome);

                    $execucaoId = DB::table('execucao')->insertGetId([
                        'programacao_coleta_id' => $programacaoId,
                        'data'                  => $data->toDateString(),
                        'hora_inicio_prevista'  => $dados['inicio'],
                        'hora_fim_prevista'     => $dados['fim'],
                        'motorista_id'          => $pessoas[$dados['motorista']],
                        'veiculo_id'            => $veiculos[$dados['veiculo']],
                        'rota_versao_id'        => $versao['id'],
                        ...$realizado,
                    ]);

                    DB::table('execucao_coletor')->insert(array_map(
                        fn (int $coletor) => ['execucao_id' => $execucaoId, 'funcionario_id' => $coletor],
                        $coletores,
                    ));

                    $autor = $dados['motorista'] === self::CPF_CARLOS ? $autores['motorista'] : $autores['gestor'];
                    $this->talvezOcorrencia($execucaoId, $realizado, $autor);
                    $total++;
                }
            }
        });

        $this->command->info("Histórico gerado: {$total} execuções de {$inicio->format('d/m/Y')} a {$fim->format('d/m/Y')}, em três rotas de demonstração.");
    }

    /**
     * A rota, o bairro e as versões do trajeto, gravados como o RotaService grava (RN40).
     * Devolve o id da rota e, de cada versão, o id e a extensão em quilômetros.
     */
    private function criarRota(array $dados, int $usuarioId, CarbonImmutable $inicio, CarbonImmutable $alteracao): array
    {
        $criadaEm = $inicio->subDays(7)->setTime(9, 0);

        $rotaId = DB::table('rota')->insertGetId([
            'identificacao' => $dados['identificacao'],
            'nome'          => $dados['nome'],
            'descricao'     => 'Rota fictícia, criada pelo HistoricoColetaSeeder para demonstrar o painel e os indicadores.',
            'cor'           => $dados['cor'],
            'ativo'         => 1,
            'created_at'    => $criadaEm,
            'updated_at'    => $criadaEm,
        ]);

        $bairroId = DB::table('bairro')->where('nome', $dados['bairro'])->value('id')
            ?? DB::table('bairro')->insertGetId(['nome' => $dados['bairro'], 'ativo' => 1, 'created_at' => $criadaEm, 'updated_at' => $criadaEm]);
        DB::table('rota_bairro')->insert(['rota_id' => $rotaId, 'bairro_id' => $bairroId]);

        $primeira = $this->criarVersao($rotaId, 1, $dados, $dados['ruas'], $usuarioId, $criadaEm);
        $atual = $dados['ruas_alteracao'] === null
            ? $primeira
            : $this->criarVersao($rotaId, 2, $dados, $dados['ruas_alteracao'], $usuarioId, $alteracao);

        DB::table('rota')->where('id', $rotaId)->update(['versao_atual_id' => $atual['id']]);

        return ['rota_id' => $rotaId, 'primeira' => $primeira, 'atual' => $atual];
    }

    private function criarVersao(int $rotaId, int $numero, array $dados, int $ruas, int $usuarioId, CarbonImmutable $criadaEm): array
    {
        $pontos = self::zigueZague($dados['origem'], $ruas, $dados['comprimento']);
        $metros = round(Trajeto::extensaoTrecho($pontos), 2);

        $versaoId = DB::table('rota_versao')->insertGetId([
            'rota_id'         => $rotaId,
            'numero'          => $numero,
            'extensao_metros' => $metros,
            'criada_em'       => $criadaEm,
            'usuario_id'      => $usuarioId,
        ]);

        DB::table('rota_trecho')->insert([
            'rota_versao_id'  => $versaoId,
            'geometria'       => json_encode(['type' => 'LineString', 'coordinates' => $pontos]),
            'extensao_metros' => $metros,
        ]);

        return ['id' => $versaoId, 'km' => $metros / 1000];
    }

    /**
     * Ruas paralelas a cada 120 m, percorridas em zigue-zague a partir de [latitude, longitude].
     * Os pontos saem na ordem do GeoJSON, [longitude, latitude], como o Trajeto espera.
     */
    private static function zigueZague(array $origem, int $ruas, int $comprimento): array
    {
        [$latitude, $longitude] = $origem;
        $leste = round($longitude + $comprimento / (111_320 * cos(deg2rad($latitude))), 6);

        $pontos = [];
        for ($rua = 0; $rua < $ruas; $rua++) {
            $y = round($latitude + $rua * 120 / 111_320, 6);
            [$saida, $chegada] = $rua % 2 === 0 ? [$longitude, $leste] : [$leste, $longitude];
            $pontos[] = [$saida, $y];
            $pontos[] = [$chegada, $y];
        }

        return $pontos;
    }

    /** O dia mais recente, até $ate, em que a rota tem coleta. */
    private static function ultimoDia(array $dias, CarbonImmutable $ate): CarbonImmutable
    {
        $data = $ate;
        while (! in_array(DiaSemana::daData($data), $dias, true)) {
            $data = $data->subDay();
        }

        return $data;
    }

    /** O que uma coleta comum registra: quase sempre concluída, às vezes cancelada. */
    private function realizado(array $dados, CarbonImmutable $data, float $km, string $gestor): array
    {
        $previsto = $data->setTimeFromTimeString($dados['inicio']);

        if ($this->sorteio->getInt(1, 20) === 1) {
            $motivo = self::MOTIVOS_CANCELAMENTO[$this->sorteio->getInt(0, count(self::MOTIVOS_CANCELAMENTO) - 1)];
            $momento = $previsto->subMinutes($this->sorteio->getInt(20, 50));

            return [...self::semRealizado(SituacaoExecucao::CANCELADA),
                'motivo_cancelamento' => ExecucaoService::motivoComAutor($motivo, $gestor, $momento)];
        }

        $iniciada = $previsto->addMinutes($this->sorteio->getInt(-5, 15));
        $minutos = (int) round($dados['minutos_por_km'] * $km * $this->variacao(0.08));

        return [...self::semRealizado(SituacaoExecucao::CONCLUIDA),
            'iniciada_em'   => $iniciada,
            'concluida_em'  => $iniciada->addMinutes($minutos),
            'quilometragem' => round(($km + $dados['deslocamento_km']) * $this->variacao(0.05), 1),
            'quantidade_kg' => round($dados['kg_por_km'] * $km * $this->variacao(0.12), -1),
            'observacoes'   => $this->sorteio->getInt(1, 8) === 1 ? 'Volume acima do normal nos pontos comerciais.' : null,
        ];
    }

    /** As duas pendências: a não iniciada fica sem nada; a em andamento, só com o início. */
    private function pendente(SituacaoExecucao $situacao, array $dados, CarbonImmutable $data): array
    {
        return $situacao === SituacaoExecucao::EM_ANDAMENTO
            ? [...self::semRealizado($situacao), 'iniciada_em' => $data->setTimeFromTimeString($dados['inicio'])->addMinutes(6)]
            : self::semRealizado($situacao);
    }

    private static function semRealizado(SituacaoExecucao $situacao): array
    {
        return [
            'situacao'            => $situacao->value,
            'iniciada_em'         => null,
            'concluida_em'        => null,
            'quilometragem'       => null,
            'quantidade_kg'       => null,
            'observacoes'         => null,
            'motivo_cancelamento' => null,
        ];
    }

    /** RF20 — uma coleta concluída em cada dez, mais ou menos, tem ocorrência; a que ficou em andamento, sempre. */
    private function talvezOcorrencia(int $execucaoId, array $realizado, int $autorId): void
    {
        $emAndamento = $realizado['situacao'] === SituacaoExecucao::EM_ANDAMENTO->value;
        $concluida = $realizado['situacao'] === SituacaoExecucao::CONCLUIDA->value;

        if (! $emAndamento && ! ($concluida && $this->sorteio->getInt(1, 10) === 1)) {
            return;
        }

        [$titulo, $descricao, $endereco] = $emAndamento
            ? ['Pane no caminhão', 'O caminhão parou no meio do trajeto e foi rebocado. A coleta foi interrompida.', null]
            : self::OCORRENCIAS[$this->sorteio->getInt(0, count(self::OCORRENCIAS) - 1)];
        $momento = $realizado['iniciada_em']->addMinutes($this->sorteio->getInt(20, 90));

        DB::table('ocorrencia')->insert([
            'execucao_id'   => $execucaoId,
            'titulo'        => $titulo,
            'descricao'     => $descricao,
            'data_hora'     => $momento,
            'endereco'      => $endereco,
            'usuario_id'    => $autorId,
            'registrada_em' => $momento->addMinutes(10),
        ]);
    }

    /** Um fator entre 1 - $margem e 1 + $margem: com 0.08, de 0,92 a 1,08. */
    private function variacao(float $margem): float
    {
        return $this->sorteio->getFloat(1 - $margem, 1 + $margem);
    }
}