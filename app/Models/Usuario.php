<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Enums\PerfilUsuario;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';

    protected $fillable = [
        'nome',
        'email',
        'perfil',
        'ativo',
        'funcionario_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'           => 'hashed',
            'perfil'             => PerfilUsuario::class,
            'ativo'              => 'boolean',
            'deve_alterar_senha' => 'boolean',
        ];
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * Gera uma senha temporária de dez caracteres alfanuméricos e tornando a troca obrigatória. 
     * Retorna o texto da senha.
     */
    public function definirSenhaTemporaria(): string
    {
        $senha = Str::password(10, symbols: false);

        $this->password = $senha;
        $this->deve_alterar_senha = true;

        return $senha;
    }

    /**
     * Define a senha definitiva do usuário, deixando de ser temporária.
     */
    public function definirSenhaDefinitiva(string $senha): void
    {
        $this->password = $senha;
        $this->deve_alterar_senha = false;
        $this->save();
    }
}