<?php

namespace App\Models;

use App\Scopes\FisioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'planes';

    protected $fillable = ['paciente_id', 'fisio_id', 'titulo', 'fecha', 'activo'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Aislamiento por fisioterapeuta: un fisio solo ve sus propios planes.
        static::addGlobalScope(new FisioScope);
    }

    /**
     * Planes de un propietario concreto (independiente de la sesión).
     */
    public function scopeDeFisio(Builder $query, $fisioId): Builder
    {
        return $query->where('planes.fisio_id', $fisioId);
    }

    public function paciente()
    {
        return $this->belongsTo(User::class, 'paciente_id')->withTrashed();
    }

    public function fisio()
    {
        return $this->belongsTo(User::class, 'fisio_id')->withTrashed();
    }

    public function planDias()
    {
        return $this->hasMany(PlanDia::class)->orderBy('numero_dia');
    }
}
