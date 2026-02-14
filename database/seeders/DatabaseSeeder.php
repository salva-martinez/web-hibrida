<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Estimulo;
use App\Models\Ejercicio;
use App\Models\Plan;
use App\Models\PlanEjercicio;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fisioterapeuta Admin
        $fisio = User::create([
            'nombre' => 'Jorge',
            'apellido1' => 'Fisio',
            'apellido2' => 'Admin',
            'email' => 'fisio@fisioapp.com',
            'password' => bcrypt('password'),
            'role' => 'fisio',
        ]);

        // 2. Pacientes de ejemplo
        $pacientes = [
            ['nombre' => 'Carlos', 'apellido1' => 'García', 'apellido2' => 'López'],
            ['nombre' => 'Ana', 'apellido1' => 'Martínez', 'apellido2' => 'Ruiz'],
            ['nombre' => 'Beto', 'apellido1' => 'Sánchez', 'apellido2' => 'Gómez'],
            ['nombre' => 'David', 'apellido1' => 'Fernández', 'apellido2' => 'Díaz'],
        ];

        foreach ($pacientes as $p) {
            User::create([
                'nombre' => $p['nombre'],
                'apellido1' => $p['apellido1'],
                'apellido2' => $p['apellido2'],
                'password' => bcrypt('password'),
                'role' => 'paciente',
            ]);
        }

        // 3. Estímulos
        $calentamiento = Estimulo::create([
            'nombre' => 'Calentamiento',
            'descripcion' => 'Ejercicios de calentamiento y activación',
            'orden' => 0,
        ]);

        $basico = Estimulo::create([
            'nombre' => '1. Básico Estructural-Neural',
            'descripcion' => 'Ejercicios básicos multiarticulares de fuerza',
            'orden' => 1,
        ]);

        $auxiliar = Estimulo::create([
            'nombre' => '2. Auxiliares',
            'descripcion' => 'Ejercicios auxiliares complementarios',
            'orden' => 2,
        ]);

        $metabolico = Estimulo::create([
            'nombre' => '3. Metabólico-Elíptica',
            'descripcion' => 'Ejercicios cardiovasculares y metabólicos',
            'orden' => 3,
        ]);

        // 4. Ejercicios
        // Calentamiento
        $eCalen1 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Zancada RI 15s', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $eCalen2 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Sentadillas profundas sin peso', 'video_url' => null]);
        $eCalen3 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Peso muerto a una pierna sin peso', 'video_url' => null]);
        $eCalen4 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'Face pull', 'video_url' => null]);
        $eCalen5 = Ejercicio::create(['estimulo_id' => $calentamiento->id, 'nombre' => 'ISO hold flexión', 'video_url' => null]);

        // Básico
        $eBas1 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Zancada con barra 4" de bajada', 'video_url' => null]);
        $eBas2 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Peso muerto rumano mancuernas bilateral', 'video_url' => null]);
        $eBas3 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Press banca barra', 'video_url' => null]);
        $eBas4 = Ejercicio::create(['estimulo_id' => $basico->id, 'nombre' => 'Remo mancuernas', 'video_url' => null]);

        // Auxiliares
        $eAux1 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Elevaciones de tobillo 3" arriba', 'video_url' => null]);
        $eAux2 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Abducción distal+pertur banco', 'video_url' => null]);
        $eAux3 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'ISO cuad', 'video_url' => null]);
        $eAux4 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Plancha lat', 'video_url' => null]);
        $eAux5 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'ISO copenhagen mod', 'video_url' => null]);
        $eAux6 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Cruces en polea', 'video_url' => null]);
        $eAux7 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Flexiones full rom pausa abajo 2"', 'video_url' => null]);
        $eAux8 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Jalón a una mano', 'video_url' => null]);
        $eAux9 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Jalón agarre neutro', 'video_url' => null]);
        $eAux10 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Press militar a una mano', 'video_url' => null]);
        $eAux11 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Curl bíceps', 'video_url' => null]);
        $eAux12 = Ejercicio::create(['estimulo_id' => $auxiliar->id, 'nombre' => 'Tríceps cuerda en polea', 'video_url' => null]);

        // Metabólico
        $eMet1 = Ejercicio::create(['estimulo_id' => $metabolico->id, 'nombre' => 'Elíptica', 'video_url' => null]);



        // 6. Ejecutar otros seeders
        $this->call([
            PlanHistorialSeeder::class,
        ]);
    }
}
