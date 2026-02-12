<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'apellido1',
        'apellido2',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Scopes
    public function scopeFisios($query)
    {
        return $query->where('role', 'fisio');
    }

    public function scopePacientes($query)
    {
        return $query->where('role', 'paciente');
    }

    // Helpers
    public function isFisio(): bool
    {
        return $this->role === 'fisio';
    }

    public function isPaciente(): bool
    {
        return $this->role === 'paciente';
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido1} {$this->apellido2}");
    }

    // Relations
    public function planesComoPaciente()
    {
        return $this->hasMany(Plan::class, 'paciente_id')->orderBy('fecha', 'desc');
    }

    public function planesComoFisio()
    {
        return $this->hasMany(Plan::class, 'fisio_id');
    }
}
