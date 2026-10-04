<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Funcionario;
use App\Enums\PerfilUsuario;
use Illuminate\Database\Seeder;

/**
 * Cadastro de Usuários de desenvolvimento com senha conhecida ("senha1234").
 */
class UsuarioDesenvolvimentoSeeder extends Seeder
{
    public function run(): void
    {
        $this->criar('tecnico@coletarsu.test', 'Técnico do Sistema', PerfilUsuario::TECNICO, senhaTemporaria: false);
        $this->criar('gestor@coletarsu.test',  'Gestor da Coleta',   PerfilUsuario::GESTOR,  senhaTemporaria: false);

        $this->criar(
            'motorista@coletarsu.test',
            'Carlos Eduardo Souza',
            PerfilUsuario::MOTORISTA,
            senhaTemporaria: true,
            funcionarioId: Funcionario::where('cpf', '29141777638')->value('id'),
        );
    }

    private function criar(string $email, string $nome, PerfilUsuario $perfil, bool $senhaTemporaria, ?int $funcionarioId = null): void 
    {
        $usuario = Usuario::firstOrNew(['email' => $email]);

        $usuario->forceFill([
            'nome'               => $nome,
            'password'           => 'senha1234',
            'perfil'             => $perfil,
            'ativo'              => true,
            'deve_alterar_senha' => $senhaTemporaria,
            'funcionario_id'     => $funcionarioId,
        ])->save();
    }
}