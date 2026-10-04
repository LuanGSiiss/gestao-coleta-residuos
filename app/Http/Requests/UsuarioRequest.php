<?php

namespace App\Http\Requests;

use Closure;
use App\Models\Usuario;
use App\Enums\PerfilUsuario;
use App\Enums\TipoFuncionario;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Foundation\Http\FormRequest;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trata para que apenas um usuário do tipo motorista tenha um motorista vínculado.
     */
    protected function prepareForValidation(): void
    {
        if ((int) $this->input('perfil') !== PerfilUsuario::MOTORISTA->value) {
            $this->merge(['funcionario_id' => null]);
        }
    }

    public function rules(): array
    {
        $usuario   = $this->route('usuario');
        $motorista = PerfilUsuario::MOTORISTA->value;

        return [
            'nome'  => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:180',
                Rule::unique('usuario', 'email')->ignore($usuario?->id),
            ],
            'perfil' => [
                'required',
                Rule::enum(PerfilUsuario::class),
                function (string $attribute, mixed $value, Closure $fail) use ($usuario) {
                    if ($this->editandoProprioUsuario($usuario) && (int) $value !== $usuario->perfil->value) {
                        $fail('Você não pode alterar o próprio perfil.');
                    }
                },
            ],
            'funcionario_id' => [
                "required_if:perfil,{$motorista}",
                'nullable',
                'integer',
                $this->regraFuncionario($usuario),
                Rule::unique('usuario', 'funcionario_id')->ignore($usuario?->id),
            ],
            'ativo' => [
                'required',
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($usuario) {
                    if ($this->editandoProprioUsuario($usuario) && !filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        $fail('Você não pode inativar o próprio usuário.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'           => 'nome',
            'email'          => 'e-mail',
            'perfil'         => 'perfil',
            'funcionario_id' => 'funcionário vinculado',
            'ativo'          => 'situação',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'               => 'Já existe um usuário com este e-mail.',
            'funcionario_id.required_if' => 'Selecione o funcionário vinculado a este motorista.',
            'funcionario_id.exists'      => 'Selecione um funcionário motorista ativo.',
            'funcionario_id.unique'      => 'Este funcionário já está vinculado a outro usuário.',
        ];
    }

    /**
     * Valida para não permitir que um usuário altere o próprio perfil o se inative.
     */
    private function editandoProprioUsuario(?Usuario $usuario): bool
    {
        return $usuario !== null && $usuario->is($this->user());
    }

    /**
     * Valida para permitir informar apenas Funcionários do tipo motorista e ativos, exceto o já vinculado a este usuário.
     */
    private function regraFuncionario(?Usuario $usuario): Exists
    {
        return Rule::exists('funcionario', 'id')->where(function ($query) use ($usuario) {
            $query->where('tipo', TipoFuncionario::MOTORISTA->value)
                  ->where(function ($q) use ($usuario) {
                      $q->where('ativo', 1);

                      if ($usuario?->funcionario_id) {
                          $q->orWhere('id', $usuario->funcionario_id);
                      }
                  });
        });
    }
}