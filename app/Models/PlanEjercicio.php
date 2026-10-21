<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEjercicio extends Model
{
    use HasFactory;

    protected $table = 'plan_ejercicios';

    protected $fillable = [
        'plan_dia_id', 'ejercicio_id', 'series',
        'repeticiones', 'intensidad', 'kg', 'descanso', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'kg' => 'decimal:2',
        ];
    }

    public function planDia()
    {
        return $this->belongsTo(PlanDia::class);
    }

    public function ejercicio()
    {
        // withTrashed(): un ejercicio desactivado sigue visible en el histórico
        // de los planes donde ya fue pautado.
        return $this->belongsTo(Ejercicio::class)->withTrashed();
    }
}
