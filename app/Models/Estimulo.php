<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estimulo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'orden'];

    public function ejercicios()
    {
        return $this->hasMany(Ejercicio::class);
    }
}
