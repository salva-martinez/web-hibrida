<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEjercicio extends Model
{
    use HasFactory;

    protected $table = 'plan_ejercicios';

    protected $fillable = [
        'plan_id', 'ejercicio_id', 'series',
        'repeticiones', 'intensidad', 'kg', 'descanso', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'kg' => 'decimal:2',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function ejercicio()
    {
        return $this->belongsTo(Ejercicio::class);
    }
}
