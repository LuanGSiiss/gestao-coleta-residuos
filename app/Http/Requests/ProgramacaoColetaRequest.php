<?php

namespace App\Http\Requests;

use App\Models\Veiculo;
use App\Enums\DiaSemana;
use App\Enums\TipoColeta;
use App\Models\Funcionario;
use App\Support\Cronograma;
use App\Support\Habilitacao;
use App\Enums\TipoFuncionario;
use Illuminate\Validation\Rule;
use App\Models\ProgramacaoColeta;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Foundation\Http\FormRequest;

class ProgramacaoColetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programacao = $this->route('programacao');
        $coletoresAtuais = $programacao?->coletores()->pluck('funcionario.id')->all() ?? [];

        $regras = [
            'descricao'    => ['required', 'string', 'max:120'],
            'motorista_id' => [
                'required',
                'integer',
                $this->regraFuncionario(TipoFuncionario::MOTORISTA, $programacao ? [$programacao->motorista_id] : []),
            ],
            'veiculo_id'   => ['required', 'integer', $this->regraVeiculo($programacao)],
            'coletores'    => ['required', 'array', 'min:1', 'max:10'],
            'coletores.*'  => ['integer', 'distinct', $this->regraFuncionario(TipoFuncionario::COLETOR, $coletoresAtuais)],
            'hora_inicio'  => ['required', 'date_format:H:i'],
            'hora_fim'     => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'orientacoes'  => ['nullable', 'string', 'max:2000'],
        ];

        if ($programacao === null) {
            $regras += [
                'tipo_coleta'   => ['required', Rule::enum(TipoColeta::class)],
                'rota_id'       => ['required', 'integer', $this->regraRota()],
                'data_inicio'   => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'data_fim'      => ['required', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
                'dias_semana'   => ['required', 'array', 'min:1'],
                'dias_semana.*' => ['integer', 'distinct', Rule::enum(DiaSemana::class)],
            ];
        }

        return $regras;
    }

    /**
     * Regras que dependem de mais de um campo. Cada uma só roda se os campos que usa já passaram nas regras de cima.
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validarPeriodo($validator),
            fn (Validator $validator) => $this->validarHabilitacao($validator),
        ];
    }

    public function attributes(): array
    {
        return [
            'descricao'     => 'descrição',
            'rota_id'       => 'rota',
            'motorista_id'  => 'motorista',
            'veiculo_id'    => 'veículo',
            'coletores'     => 'coletores',
            'coletores.*'   => 'coletor',
            'hora_inicio'   => 'hora de início',
            'hora_fim'      => 'hora de término',
            'orientacoes'   => 'orientações',
            'tipo_coleta'   => 'tipo de coleta',
            'data_inicio'   => 'data de início',
            'data_fim'      => 'data de término',
            'dias_semana'   => 'dias da semana',
            'dias_semana.*' => 'dia da semana',
        ];
    }

    public function messages(): array
    {
        return [
            'rota_id.exists'             => 'Selecione uma rota ativa e com trajeto.',
            'motorista_id.exists'        => 'Selecione um motorista ativo.',
            'veiculo_id.exists'          => 'Selecione um veículo ativo.',
            'coletores.required'         => 'Selecione ao menos um coletor.',
            'coletores.min'              => 'Selecione ao menos um coletor.',
            'coletores.max'              => 'Selecione no máximo 10 coletores.',
            'coletores.*.exists'         => 'Um dos coletores selecionados está inativo ou não é coletor.',
            'hora_fim.after'             => 'A hora de término deve ser posterior à de início.',
            'data_inicio.after_or_equal' => 'A data de início não pode ser anterior a hoje.',
            'data_fim.after_or_equal'    => 'A data de término não pode ser anterior à de início.',
            'dias_semana.required'       => 'Selecione ao menos um dia da semana.',
            'dias_semana.min'            => 'Selecione ao menos um dia da semana.',
        ];
    }

    public function dadosDaProgramacao(): array
    {
        return $this->safe()->except(['coletores', 'dias_semana']);
    }

    public function coletores(): array
    {
        return array_map('intval', $this->validated('coletores'));
    }

    /** @return DiaSemana[] */
    public function diasSemana(): array
    {
        return array_map(fn ($dia) => DiaSemana::from((int) $dia), $this->input('dias_semana', []));
    }

    /** Valida o periodo informando. Garantindo que possua no máximo 12 meses e ao menos um dia de coleta. */
    private function validarPeriodo(Validator $validator): void
    {
        if ($this->route('programacao') !== null || $validator->errors()->hasAny(['data_inicio', 'data_fim', 'dias_semana', 'dias_semana.*'])) {
            return;
        }

        $inicio = $this->date('data_inicio');
        $fim = $this->date('data_fim');
        $limite = Cronograma::limite($inicio);

        if ($fim->gt($limite)) {
            $validator->errors()->add('data_fim', "A programação pode durar no máximo 12 meses: o término vai até {$limite->format('d/m/Y')}.");

            return;
        }

        if (Cronograma::datas($inicio, $fim, $this->diasSemana()) === []) {
            $validator->errors()->add('dias_semana', 'Nenhum dos dias selecionados cai dentro do período.');
        }
    }

    /**
     * Valida o periodo informando. Garantindo que o motorista não possua CNH vencida e tenha categoria C ou superior quando for um veiculo compactador. 
     * Se não for alterado os dados de motorista ou veiculo, apenas exibe um aviso.
     */
    private function validarHabilitacao(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['motorista_id', 'veiculo_id'])) {
            return;
        }

        $programacao = $this->route('programacao');
        $motoristaId = $this->integer('motorista_id');
        $veiculoId = $this->integer('veiculo_id');

        if ($programacao !== null && $motoristaId === (int) $programacao->motorista_id && $veiculoId === (int) $programacao->veiculo_id) {
            return;
        }

        $impedimentos = Habilitacao::impedimentos(
            Funcionario::findOrFail($motoristaId),
            Veiculo::findOrFail($veiculoId),
        );

        foreach ($impedimentos as $impedimento) {
            $validator->errors()->add('motorista_id', $impedimento);
        }
    }

    /**
     * Valida para que a rota seja ativa e com trajeto.
     */
    private function regraRota(): Exists
    {
        return Rule::exists('rota', 'id')
            ->where('ativo', 1)
            ->whereNotNull('versao_atual_id');
    }

    /** Valida para que o funcionário seja do tipo certo e ativo. */
    private function regraFuncionario(TipoFuncionario $tipo, array $vinculados): Exists
    {
        return Rule::exists('funcionario', 'id')
            ->where('tipo', $tipo->value)
            ->where(function ($query) use ($vinculados) {
                $query->where('ativo', 1);

                if ($vinculados !== []) {
                    $query->orWhereIn('id', $vinculados);
                }
            });
    }

    /** Valida para que o veículo esteja ativo. */
    private function regraVeiculo(?ProgramacaoColeta $programacao): Exists
    {
        return Rule::exists('veiculo', 'id')->where(function ($query) use ($programacao) {
            $query->where('ativo', 1);

            if ($programacao) {
                $query->orWhere('id', $programacao->veiculo_id);
            }
        });
    }
}