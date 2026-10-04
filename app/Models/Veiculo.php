<?php

namespace App\Models;

use App\Enums\TipoVeiculo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Veiculo extends Model
{
    protected $table = 'veiculo';

    protected $fillable = [
        'modelo',
        'marca_id',
        'placa',
        'ano',
        'tipo',
        'capacidade_toneladas',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'tipo'                 => TipoVeiculo::class,
            'capacidade_toneladas' => 'decimal:2',
            'ativo'                => 'boolean',
        ];
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * Retorna a placa do veículo formatada, sendo removido o hifen que era utilizado no formato antigo das placas.
     */
    protected function placaFormatada(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->placa) {
                return null;
            }

            return preg_match('/^[A-Z]{3}\d{4}$/', $this->placa) ? substr($this->placa, 0, 3) . '-' . substr($this->placa, 3) : $this->placa;
        });
    }
}