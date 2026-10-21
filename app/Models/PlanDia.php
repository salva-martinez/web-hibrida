<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanDia extends Model
{
    use HasFactory;

    protected $table = 'plan_dias';

    protected $fillable = ['plan_id', 'numero_dia', 'notas_fisio'];

    public function plan()
    {
        // withTrashed(): los días de un plan archivado siguen siendo legibles.
        return $this->belongsTo(Plan::class)->withTrashed();
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
     * Get plan exercises for this day grouped by estímulo.
     */
    public function ejerciciosPorEstimulo()
    {
        return $this->planEjercicios
            ->loadMissing('ejercicio.estimulo')
            ->sortBy(fn ($pe) => $pe->ejercicio->estimulo->orden)
            ->groupBy(fn ($pe) => $pe->ejercicio->estimulo->nombre);
    }

    /**
     * Clase CSS de la cabecera de un grupo de ejercicios, según el tipo de
     * estímulo (calentamiento, básico, auxiliar o metabólico).
     *
     * Vive en el modelo para que la tabla de ejercicios no la duplique en
     * cada vista.
     */
    public static function claseCssEstimulo(string $nombre): string
    {
        $nombre = mb_strtolower($nombre);

        return match (true) {
            str_contains($nombre, 'calentamiento') => 'estimulo-calentamiento',
            str_contains($nombre, 'auxiliar') => 'estimulo-auxiliar',
            str_contains($nombre, 'metab'), str_contains($nombre, 'elíptica') => 'estimulo-metabolico',
            default => 'estimulo-basico',
        };
    }
}
