<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rota extends Model
{
    protected $table = 'rota';

    /** Cor padrão ao criar uma rota. */
    public const COR_PADRAO = '#0d6efd';

    protected $fillable = [
        'identificacao',
        'nome',
        'descricao',
        'cor',
        'ativo',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function bairros(): BelongsToMany
    {
        return $this->belongsToMany(Bairro::class, 'rota_bairro');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(RotaVersao::class);
    }

    public function versaoAtual(): BelongsTo
    {
        return $this->belongsTo(RotaVersao::class, 'versao_atual_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}