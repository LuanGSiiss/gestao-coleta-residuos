<?php

namespace App\Enums;

use App\Enums\Concerns\TemRotulo;

enum SituacaoExecucao: int
{
    use TemRotulo;

    case NAO_INICIADA = 1;
    case EM_ANDAMENTO = 2;
    case CONCLUIDA    = 3;
    case CANCELADA    = 4;

    /**
     * Retorna o rotulo da opção.
     * @return string
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::NAO_INICIADA => 'Não iniciada',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDA    => 'Concluída',
            self::CANCELADA    => 'Cancelada',
        };
    }

    /**
     * Retorna se a opção pode para a opção atual.
     * @param self $destino
     * @return bool
     */
    public function podeIrPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }

    /** 
     * Retorna as transações permitidas de situações da execução.
     * @return array 
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::NAO_INICIADA => [self::EM_ANDAMENTO, self::CANCELADA],
            self::EM_ANDAMENTO => [self::CONCLUIDA, self::CANCELADA],
            self::CONCLUIDA, self::CANCELADA => [],
        };
    }

    /**
     * Retorna a cor relacionada a opção.
     * @return string
     */
    public function cor(): string
    {
        return match ($this) {
            self::NAO_INICIADA => 'secondary',
            self::EM_ANDAMENTO => 'primary',
            self::CONCLUIDA    => 'success',
            self::CANCELADA    => 'dark',
        };
    }
}