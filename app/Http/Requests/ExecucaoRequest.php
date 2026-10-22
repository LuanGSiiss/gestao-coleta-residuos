<?php

namespace App\Http\Requests;

use App\Models\Execucao;
use App\Enums\TipoFuncionario;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidaAlocacao;

class ExecucaoRequest extends FormRequest
{
    use ValidaAlocacao;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $execucao = $this->execucao();
        $coletoresAtuais = $execucao->coletores()->pluck('funcionario.id')->all();

        return [
            'motorista_id'         => [
                'required',
                'integer',
                $this->regraFuncionario(TipoFuncionario::MOTORISTA, [$execucao->motorista_id]),
            ],
            'veiculo_id'           => ['required', 'integer', $this->regraVeiculo($execucao->veiculo_id)],
            'coletores'            => ['required', 'array', 'min:1', 'max:10'],
            'coletores.*'          => ['integer', 'distinct', $this->regraFuncionario(TipoFuncionario::COLETOR, $coletoresAtuais)],
            'hora_inicio_prevista' => ['required', 'date_format:H:i'],
            'hora_fim_prevista'    => ['required', 'date_format:H:i', 'after:hora_inicio_prevista'],
            'orientacoes'          => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        $execucao = $this->execucao();

        return [
            fn (Validator $validator) => $this->validarHabilitacao($validator, $this->execucao()->motorista_id)
        ];
    }

    public function attributes(): array
    {
        return [
            'motorista_id'         => 'motorista',
            'veiculo_id'           => 'veículo',
            'coletores'            => 'coletores',
            'coletores.*'          => 'coletor',
            'hora_inicio_prevista' => 'hora de início',
            'hora_fim_prevista'    => 'hora de término',
            'orientacoes'          => 'orientações',
        ];
    }

    public function messages(): array
    {
        return [
            'motorista_id.exists'     => 'Selecione um motorista ativo.',
            'veiculo_id.exists'       => 'Selecione um veículo ativo.',
            'coletores.required'      => 'Selecione ao menos um coletor.',
            'coletores.min'           => 'Selecione ao menos um coletor.',
            'coletores.max'           => 'Selecione no máximo 10 coletores.',
            'coletores.*.exists'      => 'Um dos coletores selecionados está inativo ou não é coletor.',
            'hora_fim_prevista.after' => 'A hora de término deve ser posterior à de início.',
        ];
    }

    /** Retorna os dados da execução. */
    public function dadosDaExecucao(): array
    {
        return $this->safe()->only([
            'motorista_id',
            'veiculo_id',
            'hora_inicio_prevista',
            'hora_fim_prevista',
            'orientacoes',
        ]);
    }

    /** Retorna os coletores. */
    public function coletores(): array
    {
        return array_map('intval', $this->validated('coletores'));
    }

    private function execucao(): Execucao
    {
        return $this->route('execucao');
    }
}