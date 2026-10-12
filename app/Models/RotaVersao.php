<?php

namespace App\Models;

use LogicException;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotaVersao extends Model
{
    protected $table = 'rota_versao';

    /*
     * A data de criação é a própria criada_em, e não existe data de alteração.
     */
    public const CREATED_AT = 'criada_em';
    public const UPDATED_AT = null;

    protected $fillable = [
        'numero',
        'extensao_metros',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'extensao_metros' => 'decimal:2',
            'criada_em'       => 'datetime',
        ];
    }

    /**
     * Garante a imutabilidade no código. Dessa forma, lança uma exceção ao tentar alterar ou excluir uma versão.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Versões de trajeto são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Versões de trajeto não podem ser excluídas.'));
    }

    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** Ordenados pelo id, que preserva a ordem em que foram gravados. */
    public function trechos(): HasMany
    {
        return $this->hasMany(RotaTrecho::class)->orderBy('id');
    }

    /** 
     * Retorna a extensão formatada em km.
     */
    protected function extensaoFormatada(): Attribute
    {
        return Attribute::get(
            fn () => number_format($this->extensao_metros / 1000, 2, ',', '.') . ' km'
        );
    }
}