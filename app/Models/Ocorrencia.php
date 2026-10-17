<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ocorrencia extends Model
{
    protected $table = 'ocorrencia';

    public const CREATED_AT = 'registrada_em';
    public const UPDATED_AT = null;

    protected $fillable = [
        'titulo',
        'descricao',
        'data_hora',
        'endereco',
    ];

    protected function casts(): array
    {
        return [
            'data_hora'     => 'immutable_datetime',
            'registrada_em' => 'immutable_datetime',
        ];
    }

    /** Impede que ocorreências sejam alteradas ou excluídas. */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ocorrências não podem ser alteradas.'));
        static::deleting(fn () => throw new LogicException('Ocorrências não podem ser excluídas.'));
    }

    public function execucao(): BelongsTo
    {
        return $this->belongsTo(Execucao::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}