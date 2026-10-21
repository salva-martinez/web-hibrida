<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = ['plan_dia_id', 'dureza', 'dolor', 'evolucion', 'comentario', 'analisis_ia', 'analisis_error'];

    public function planDia()
    {
        // Sin withTrashed(): PlanDia no usa SoftDeletes, y el macro only-with-trashed
        // solo existe en los builders de los modelos que sí lo usan.
        return $this->belongsTo(PlanDia::class);
    }
}
