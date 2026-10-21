<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nombre',
        'apellido1',
        'apellido2',
        'fecha_nacimiento',
        'email',
        'password',
        'role',
        'fisio_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_nacimiento' => 'date',
            'password' => 'hashed',
        ];
    }

    // Scopes
    //
    // Nota: este modelo NO lleva el global scope FisioScope (a diferencia de Plan,
    // Estimulo y Ejercicio). El motivo es que el propio fisioterapeuta tiene
    // `fisio_id = NULL`: aplicarle el filtro por propietario lo dejaría fuera de
    // sus propias consultas. El aislamiento de los pacientes se resuelve con los
    // scopes explícitos de abajo y con las comprobaciones de autorización de los
    // controladores.

    public function scopeFisios($query)
    {
        return $query->where('role', 'fisio');
    }

    /**
     * Pacientes visibles para el fisioterapeuta autenticado.
     *
     * Sin sesión de fisio (seeders, consola) devuelve todos los pacientes.
     */
    public function scopePacientes($query)
    {
        $query->where('role', 'paciente');

        $user = Auth::user();

        if ($user && $user->isFisio()) {
            $query->where('fisio_id', $user->id);
        }

        return $query;
    }

    /**
     * Pacientes de un propietario concreto (independiente de la sesión).
     */
    public function scopeDeFisio($query, $fisioId)
    {
        return $query->where('fisio_id', $fisioId);
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

    /**
     * ¿Este recurso pertenece al fisioterapeuta autenticado?
     *
     * Es la comprobación de autorización explícita que usan los controladores del
     * panel de administración (complementa al aislamiento global).
     */
    public function perteneceAlFisioAutenticado(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->isFisio()
            && $this->fisio_id !== null
            && (int) $this->fisio_id === (int) $user->id;
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

    /**
     * Fisio propietario (solo los pacientes lo tienen).
     */
    public function fisio()
    {
        return $this->belongsTo(User::class, 'fisio_id')->withTrashed();
    }

    /**
     * Pacientes que este fisioterapeuta gestiona.
     *
     * OJO: no puede llamarse `pacientes()` porque ese nombre lo usa el scope
     * `scopePacientes()` (User::pacientes()), que es el que filtra por propietario.
     */
    public function pacientesAsignados()
    {
        return $this->hasMany(User::class, 'fisio_id')->where('role', 'paciente');
    }
}
