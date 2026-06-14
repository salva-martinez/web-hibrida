<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = ['plan_dia_id', 'dureza', 'dolor', 'evolucion', 'comentario', 'analisis_ia'];

    public function planDia()
    {
        return $this->belongsTo(PlanDia::class);
    }
}
