<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Enums\PerfilUsuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CriarTecnico extends Command
{
    protected $signature = 'usuario:criar-tecnico';

    protected $description = 'Cria um usuário Técnico com senha temporária gerada.';

    public function handle(): int
    {
        $nome  = $this->ask('Nome');
        $email = $this->ask('E-mail');

        $validacao = Validator::make(
            ['nome' => $nome, 'email' => $email],
            [
                'nome'  => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:180', 'unique:usuario,email'],
            ]
        );

        if ($validacao->fails()) {
            foreach ($validacao->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        $usuario = new Usuario([
            'nome'   => $nome,
            'email'  => $email,
            'perfil' => PerfilUsuario::TECNICO,
            'ativo'  => true,
        ]);

        $senha = $usuario->definirSenhaTemporaria();
        $usuario->save();

        $this->newLine();
        $this->info('Técnico criado.');
        $this->line("  E-mail:           {$email}");
        $this->line("  Senha temporária: {$senha}");
        $this->newLine();
        $this->warn('Anote a senha agora: ela não será exibida novamente e deverá ser trocada no primeiro acesso.');

        return self::SUCCESS;
    }
}