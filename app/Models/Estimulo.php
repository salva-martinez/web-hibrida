<?php

namespace App\Models;

use App\Scopes\FisioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['nombre', 'descripcion', 'orden', 'fisio_id'];

    protected static function booted(): void
    {
        // Cada fisio trabaja con su propio catálogo de estímulos.
        static::addGlobalScope(new FisioScope);
    }

    public function scopeDeFisio(Builder $query, $fisioId): Builder
    {
        return $query->where('estimulos.fisio_id', $fisioId);
    }

    public function ejercicios()
    {
        return $this->hasMany(Ejercicio::class)->withTrashed();
    }
}
