<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Estimulo;
use App\Models\Ejercicio;
use App\Models\Plan;
use App\Models\PlanEjercicio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fisioterapeuta Admin
        // Cada fisio es su propia isla: `fisio_id` queda NULL porque no depende
        // de ningún otro profesional.
        $fisio = User::create([
            'nombre' => 'Jorge',
            'apellido1' => 'Fisio',
            'apellido2' => 'Admin',
            'email' => 'fisio@fisioapp.com',
            'password' => bcrypt('password'),
            'role' => 'fisio',
            'fisio_id' => null,
            'email_verified_at' => now(),
        ]);

        // 2. Pacientes de ejemplo (pertenecen al fisio admin)
        $pacientes = [
            ['nombre' => 'Carlos', 'apellido1' => 'García', 'apellido2' => 'López', 'fecha_nacimiento' => '1985-03-14'],
            ['nombre' => 'Ana', 'apellido1' => 'Martínez', 'apellido2' => 'Ruiz', 'fecha_nacimiento' => '1990-07-22'],
            ['nombre' => 'Beto', 'apellido1' => 'Sánchez', 'apellido2' => 'Gómez', 'fecha_nacimiento' => '1978-11-05'],
            ['nombre' => 'David', 'apellido1' => 'Fernández', 'apellido2' => 'Díaz', 'fecha_nacimiento' => '1995-01-30'],
        ];

        foreach ($pacientes as $p) {
            User::create([
                'nombre' => $p['nombre'],
                'apellido1' => $p['apellido1'],
                'apellido2' => $p['apellido2'],
                'fecha_nacimiento' => $p['fecha_nacimiento'],
                'password' => bcrypt(Str::random(32)),
                'role' => 'paciente',
                'fisio_id' => $fisio->id,
                'email_verified_at' => now(),
            ]);
        }

        // 3. Estímulos
        $calentamiento = Estimulo::create([
            'nombre' => 'Calentamiento',
            'descripcion' => 'Ejercicios de calentamiento y activación',
            'orden' => 0,
            'fisio_id' => $fisio->id,
        ]);

        $basico = Estimulo::create([
            'nombre' => '1. Básico Estructural-Neural',
            'descripcion' => 'Ejercicios básicos multiarticulares de fuerza',
            'orden' => 1,
            'fisio_id' => $fisio->id,
        ]);

        $auxiliar = Estimulo::create([
            'nombre' => '2. Auxiliares',
            'descripcion' => 'Ejercicios auxiliares complementarios',
            'orden' => 2,
            'fisio_id' => $fisio->id,
        ]);

        $metabolico = Estimulo::create([
            'nombre' => '3. Metabólico-Elíptica',
            'descripcion' => 'Ejercicios cardiovasculares y metabólicos',
            'orden' => 3,
            'fisio_id' => $fisio->id,
        ]);

        // 4. Ejercicios
        // Calentamiento
        $eCalen1 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Zancada RI 15s', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'fisio_id' => $fisio->id]);
        $eCalen2 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Sentadillas profundas sin peso', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eCalen3 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Peso muerto a una pierna sin peso', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eCalen4 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Face pull', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eCalen5 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'ISO hold flexión', 'video_url' => null, 'fisio_id' => $fisio->id]);

        // Básico
        $eBas1 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Zancada con barra 4" de bajada', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eBas2 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Peso muerto rumano mancuernas bilateral', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eBas3 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Press banca barra', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eBas4 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Remo mancuernas', 'video_url' => null, 'fisio_id' => $fisio->id]);

        // Auxiliares
        $eAux1 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Elevaciones de tobillo 3" arriba', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux2 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Abducción distal+pertur banco', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux3 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'ISO cuad', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux4 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Plancha lat', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux5 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'ISO copenhagen mod', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux6 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Cruces en polea', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux7 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Flexiones full rom pausa abajo 2"', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux8 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Jalón a una mano', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux9 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Jalón agarre neutro', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux10 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Press militar a una mano', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux11 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Curl bíceps', 'video_url' => null, 'fisio_id' => $fisio->id]);
        $eAux12 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Tríceps cuerda en polea', 'video_url' => null, 'fisio_id' => $fisio->id]);

        // Metabólico
        $eMet1 = Ejercicio::create(['estimulo_id' => $metabolico->id, 'nombre' => 'Elíptica', 'video_url' => null, 'fisio_id' => $fisio->id]);



        // 6. Ejecutar otros seeders
        $this->call([
            PlanHistorialSeeder::class,
        ]);
    }
}
