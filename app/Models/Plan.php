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

    public function planDias()
    {
        return $this->hasMany(PlanDia::class)->orderBy('numero_dia');
    }
}
