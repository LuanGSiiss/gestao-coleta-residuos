<?php

namespace App\Models;

use App\Enums\TipoFuncionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Funcionario extends Model
{
    protected $table = 'funcionario';

    /** Categorias da CNH válidas no Brasil. */
    public const CATEGORIAS_CNH = ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'];

    protected $fillable = [
        'nome',
        'cpf',
        'cargo',
        'tipo',
        'carga_horaria_semanal',
        'cnh',
        'cnh_categoria',
        'cnh_validade',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'tipo'         => TipoFuncionario::class,
            'cnh_validade' => 'date',
            'ativo'        => 'boolean',
        ];
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * Filtra os funcionários do tipo Motorista.
     */
    public function scopeMotoristas(Builder $query): Builder
    {
        return $query->where('tipo', TipoFuncionario::MOTORISTA->value);
    }

    /**
     * Filtra os funcionários do tipo Motorista.
     */
    public function scopeColetores(Builder $query): Builder
    {
        return $query->where('tipo', TipoFuncionario::COLETOR->value);
    }

    /** 
     * Retorna o CPF no formato "111.111.111-11".
     */
    protected function cpfFormatado(): Attribute
    {
        return Attribute::get(fn () => $this->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf) : null);
    }

    /**
     * Retorna se a CNH está vencida.
     */
    public function cnhVencida(): bool
    {
        return $this->tipo === TipoFuncionario::MOTORISTA
            && $this->cnh_validade !== null
            && $this->cnh_validade->lt(today());
    }
}