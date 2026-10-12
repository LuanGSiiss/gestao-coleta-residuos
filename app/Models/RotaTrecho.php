<?php

namespace App\Models;

use LogicException;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotaTrecho extends Model
{
    protected $table = 'rota_trecho';

    public $timestamps = false;

    protected $fillable = [
        'geometria',
        'extensao_metros',
    ];

    protected function casts(): array
    {
        return [
            'geometria'       => 'array',
            'extensao_metros' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Trechos de uma versão são imutáveis.'));
    }

    public function versao(): BelongsTo
    {
        return $this->belongsTo(RotaVersao::class, 'rota_versao_id');
    }
}