<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Enums\PerfilUsuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::firstOrCreate(
            ['email' => 'tecnico@coletarsu.test'],
            [
                'nome'     => 'Técnico do Sistema',
                'password' => 'senha1234',
                'perfil'   => PerfilUsuario::TECNICO,
                'ativo'    => true,
            ]
        );

        Usuario::firstOrCreate(
            ['email' => 'gestor@coletarsu.test'],
            [
                'nome'     => 'Gestor da Coleta',
                'password' => 'senha1234',
                'perfil'   => PerfilUsuario::GESTOR,
                'ativo'    => true,
            ]
        );
    }
}
