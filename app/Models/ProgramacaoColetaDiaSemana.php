<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Illuminate\Database\Eloquent\Model;

/**
 * Classe que presenta um dia da semana de uma programação.
 */
class ProgramacaoColetaDiaSemana extends Model
{
    protected $table = 'programacao_coleta_dia_semana';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['dia_semana'];

    protected function casts(): array
    {
        return ['dia_semana' => DiaSemana::class];
    }
}