<?php

namespace App\Http\Requests;

use Closure;
use App\Rules\Cpf;
use App\Models\Funcionario;
use App\Enums\TipoFuncionario;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class FuncionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara os valores antes de realizar a validação.  
     * Trata para salvar o CPF só com o digitos no banco, podendo ser digitado com ou sem pontuação. 
     * Trata para que a habilitação só exista para motoristas.
     */
    protected function prepareForValidation(): void
    {
        $motorista = (int) $this->input('tipo') === TipoFuncionario::MOTORISTA->value;

        $this->merge([
            'cpf'           => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'cnh'           => $motorista && $this->filled('cnh') ? preg_replace('/\D/', '', $this->input('cnh')) : null,
            'cnh_categoria' => $motorista && $this->filled('cnh_categoria') ? mb_strtoupper($this->input('cnh_categoria')) : null,
            'cnh_validade'  => $motorista ? $this->input('cnh_validade') : null,
        ]);
    }

    public function rules(): array
    {
        $funcionario = $this->route('funcionario');
        $tipoMotorista   = TipoFuncionario::MOTORISTA->value;

        return [
            'nome'  => ['required', 'string', 'max:120'],
            'cpf'   => [
                'required',
                new Cpf(),
                Rule::unique('funcionario', 'cpf')->ignore($funcionario?->id),
            ],
            'cargo' => ['required', 'string', 'max:60'],
            'tipo'  => [
                'required',
                Rule::enum(TipoFuncionario::class),
                $this->regraVinculoUsuario($funcionario),
            ],
            'carga_horaria_semanal' => ['required', 'integer', 'between:1,60'],
            'cnh'           => ["required_if:tipo,{$tipoMotorista}", 'nullable', 'digits_between:9,11'],
            'cnh_categoria' => ["required_if:tipo,{$tipoMotorista}", 'nullable', Rule::in(Funcionario::CATEGORIAS_CNH)],
            'cnh_validade'  => ["required_if:tipo,{$tipoMotorista}", 'nullable', 'date'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'                  => 'nome',
            'cpf'                   => 'CPF',
            'cargo'                 => 'cargo',
            'tipo'                  => 'tipo',
            'carga_horaria_semanal' => 'carga horária semanal',
            'cnh'                   => 'número da CNH',
            'cnh_categoria'         => 'categoria da CNH',
            'cnh_validade'          => 'validade da CNH',
            'ativo'                 => 'situação',
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.unique'                => 'Já existe um funcionário cadastrado com este CPF.',
            'cnh.required_if'           => 'O número da CNH é obrigatório para motoristas.',
            'cnh_categoria.required_if' => 'A categoria da CNH é obrigatória para motoristas.',
            'cnh_validade.required_if'  => 'A validade da CNH é obrigatória para motoristas.',
        ];
    }

    /**
     * Valida para que não seja possível alterar um funcionário para coletor caso ele esteja vinculado a um usuário motorista.
     */
    private function regraVinculoUsuario(?Funcionario $funcionario): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($funcionario) {
            if ($funcionario && (int) $value !== TipoFuncionario::MOTORISTA->value && $funcionario->usuario()->exists()) {
                $fail('Este funcionário está vinculado a um usuário motorista. Remova o vínculo antes de alterar o tipo.');
            }
        };
    }
}