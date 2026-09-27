<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bairro extends Model
{
    protected $table = 'bairro';

    protected $fillable = [
        'nome',
        'ativo'
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    // public function rotas(): BelongsToMany
    // {
    //     return $this->belongsToMany(Rota::class, 'rota_bairro');
    // }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * Adiciona o filtro para retornar apenas os registros disponíveis em um select.
     */
    public function scopeParaSelecao(Builder $query, ?int $incluirId = null): Builder
    {
        return $query->where(fn ($q) => $q
                        ->where('ativo', true)
                        ->when($incluirId, fn ($q, $id) => $q->orWhere('id', $id)))
                     ->orderBy('nome');
    }
}