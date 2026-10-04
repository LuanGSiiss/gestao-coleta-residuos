<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Garante que seja criado os registros em ambiente de teste/desenvolvimento.
        if (app()->environment('local', 'testing')) {
            $this->call([
                CadastrosDesenvolvimentoSeeder::class,
                UsuarioDesenvolvimentoSeeder::class,
            ]);
        }
    }
}
