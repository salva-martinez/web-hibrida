<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = ['paciente_id', 'fisio_id', 'titulo', 'fecha', 'activo'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(User::class, 'paciente_id');
    }

    public function fisio()
    {
        return $this->belongsTo(User::class, 'fisio_id');
    }

    public function planEjercicios()
    {
        return $this->hasMany(PlanEjercicio::class)->orderBy('orden');
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }

    /**
     * Get plan exercises grouped by estímulo.
     */
    public function ejerciciosPorEstimulo()
    {
        return $this->planEjercicios()
            ->with('ejercicio.estimulo')
            ->get()
            ->groupBy(fn($pe) => $pe->ejercicio->estimulo->nombre);
    }
}
