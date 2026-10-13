<?php

namespace App\Support;

/**
 * Uma alocação define quem e o que fica ocupado, em que data e horário.
 *
 * Utiliza para realizar a comparação de conflitos da programação.
 */
final class Alocacao
{
    /**
     * @param string    $data        no formato Y-m-d
     * @param string    $horaInicio  no formato HH:MM
     * @param string    $horaFim     no formato HH:MM
     * @param list<int> $coletores
     */
    public function __construct(
        public readonly string $data,
        public readonly string $horaInicio,
        public readonly string $horaFim,
        public readonly int $motoristaId,
        public readonly array $coletores,
        public readonly int $veiculoId,
    ) {
    }

    /**
     * Retorna os valores a partir de dados previstos de uma programação ou de uma execução.
     */
    public static function deDadosPrevistos(string $data, array $previstos): self
    {
        return new self(
            $data,
            $previstos['hora_inicio_prevista'],
            $previstos['hora_fim_prevista'],
            $previstos['motorista_id'],
            $previstos['coletores'],
            $previstos['veiculo_id'],
        );
    }

    /**
     * Retorna o motorista e coletores juntos.
     */
    public function funcionarios(): array
    {
        return [$this->motoristaId, ...$this->coletores];
    }

    /**
     * Retorna os recusos em conflito entre as duas alocações.
     * Só há disputa na mesma data e com horários sobrepostos.
     *
     * @return array vazio quando não há conflito.
     */
    public function recursosEmConflito(Alocacao $outra): array
    {
        $sobrepoe = $this->data === $outra->data && $this->horaInicio < $outra->horaFim && $this->horaFim > $outra->horaInicio;

        if (!$sobrepoe) {
            return [];
        }

        $recursos = [];

        if ($this->veiculoId === $outra->veiculoId) {
            $recursos[] = ['tipo' => 'veiculo', 'id' => $this->veiculoId];
        }

        foreach (array_intersect($this->funcionarios(), $outra->funcionarios()) as $id) {
            $recursos[] = ['tipo' => 'funcionario', 'id' => $id];
        }

        return $recursos;
    }
}