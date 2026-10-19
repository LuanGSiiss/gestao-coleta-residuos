<?php

namespace App\Support;

use DateTimeImmutable;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * O período utilizado nos indicadores, sendo de uma data a outra.
 */
final class Periodo
{
    private function __construct(public readonly CarbonImmutable $de, public readonly CarbonImmutable $ate) {
    }

    /** Retorna um novo periodo datas informadas na ordem correta e com o horário zerado. */
    public static function entre(CarbonImmutable $de, CarbonImmutable $ate): self
    {
        [$de, $ate] = $de->lte($ate) ? [$de, $ate] : [$ate, $de];

        return new self($de->startOfDay(), $ate->startOfDay());
    }

    /** Retorna o periodo padrao, sendo 30 dias atras até hoje. */
    public static function padrao(): self
    {
        $hoje = CarbonImmutable::today();

        return self::entre($hoje->subDays(29), $hoje);
    }

    /**
     * Retorna o periodo com base na requisicao atual.
     */
    public static function daRequisicao(Request $request): self
    {
        $padrao = self::padrao();

        return self::entre(
            self::data($request->query('de')) ?? $padrao->de,
            self::data($request->query('ate')) ?? $padrao->ate,
        );
    }

    /** Retorna os atalhos para a tela. */
    public static function atalhos(): array
    {
        $hoje = CarbonImmutable::today();
        $mesAnterior = $hoje->startOfMonth()->subMonth();

        return [
            'Últimos 30 dias' => self::padrao(),
            'Este mês'        => self::entre($hoje->startOfMonth(), $hoje),
            'Mês anterior'    => self::entre($mesAnterior, $mesAnterior->endOfMonth()),
            'Este ano'        => self::entre($hoje->startOfYear(), $hoje),
        ];
    }

    /**
     * Retorna as semanas, de segunda a domingo, que o período contempla. 
     * Cada uma é cortada nas pontas do período: de uma quarta a outra quarta, a primeira e a última semana ficam incompletas.
     */
    public function semanas(): array
    {
        $semanas = [];

        for ($segunda = $this->de->subDays($this->de->dayOfWeekIso - 1); $segunda->lte($this->ate); $segunda = $segunda->addDays(7)) {
            $inicio = $segunda->lt($this->de) ? $this->de : $segunda;
            $fim = $segunda->addDays(6)->gt($this->ate) ? $this->ate : $segunda->addDays(6);

            $semanas[] = ['segunda' => $segunda, 'inicio' => $inicio, 'fim' => $fim, 'dias' => $inicio->diff($fim)->days + 1];
        }

        return $semanas;
    }

    public function igualA(self $outro): bool
    {
        return $this->de->isSameDay($outro->de) && $this->ate->isSameDay($outro->ate);
    }

    /** Retorna o periodo para ser utilizado como parametro, no formato ['de' => '2026-09-15', 'ate' => '2026-10-14'] */
    public function parametros(): array
    {
        return ['de' => $this->de->toDateString(), 'ate' => $this->ate->toDateString()];
    }

    /** Retorna o periodo no formato "15/09/2026 a 14/10/2026" */
    public function descricao(): string
    {
        return $this->de->format('d/m/Y') . ' a ' . $this->ate->format('d/m/Y');
    }

    /**
     * Retorna a data validando o formato para ser igual a "2026-10-14".
     */
    private static function data(mixed $valor): ?CarbonImmutable
    {
        if (!is_string($valor) || $valor === '') {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return $data !== false && $data->format('Y-m-d') === $valor ? CarbonImmutable::parse($valor) : null;
    }
}