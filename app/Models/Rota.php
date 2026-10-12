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

    /**
     * Paleta de cores sugeridas para as rotas.
     */
    public const PALETA = [
        '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b',
        '#e377c2', '#17becf', '#bcbd22', '#393b79', '#637939', '#843c39',
    ];

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

    /** Primeira cor da paleta ainda não usada. Caso tenham sido utilizadas todas as cores, recomeça. */
    public static function sugerirCor(): string
    {
        $usadas = static::query()->pluck('cor')->map(fn ($cor) => mb_strtolower($cor))->all();

        foreach (self::PALETA as $cor) {
            if (!in_array($cor, $usadas, true)) {
                return $cor;
            }
        }

        return self::PALETA[count($usadas) % count(self::PALETA)];
    }
}