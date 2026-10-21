<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Asigna la propiedad de los datos existentes.
     *
     * Antes de esta migración no existía el concepto de "fisio propietario": el
     * catálogo de ejercicios era global y los pacientes no pertenecían a nadie. Con el
     * aislamiento por islas hay que decidir de quién es cada registro.
     *
     * Regla: se hereda del fisio que creó el plan más reciente que usa ese registro.
     * Si no hay ningún plan, se asigna al primer fisio del sistema (caso del seed).
     */
    public function up(): void
    {
        $fisioPorDefecto = DB::table('users')
            ->where('role', 'fisio')
            ->orderBy('id')
            ->value('id');

        if (! $fisioPorDefecto) {
            return;
        }

        // 1. Planes: propietario = fisio que lo creó (solo rellenamos los huecos).
        DB::table('planes')->whereNull('fisio_id')->update(['fisio_id' => $fisioPorDefecto]);

        // 2. Ejercicios: propietario = fisio del plan más reciente que lo pauta.
        $ejercicios = DB::table('ejercicios')->whereNull('fisio_id')->get();

        foreach ($ejercicios as $ejercicio) {
            $fisioId = DB::table('plan_ejercicios')
                ->join('plan_dias', 'plan_ejercicios.plan_dia_id', '=', 'plan_dias.id')
                ->join('planes', 'plan_dias.plan_id', '=', 'planes.id')
                ->where('plan_ejercicios.ejercicio_id', $ejercicio->id)
                ->whereNotNull('planes.fisio_id')
                ->orderByDesc('planes.fecha')
                ->value('planes.fisio_id');

            DB::table('ejercicios')
                ->where('id', $ejercicio->id)
                ->update(['fisio_id' => $fisioId ?: $fisioPorDefecto]);
        }

        // 3. Estímulos: propietario = fisio de los ejercicios que los contienen.
        $estimulos = DB::table('estimulos')->whereNull('fisio_id')->get();

        foreach ($estimulos as $estimulo) {
            $fisioId = DB::table('ejercicios')
                ->where('estimulo_id', $estimulo->id)
                ->whereNotNull('fisio_id')
                ->orderBy('fisio_id')
                ->value('fisio_id');

            DB::table('estimulos')
                ->where('id', $estimulo->id)
                ->update(['fisio_id' => $fisioId ?: $fisioPorDefecto]);
        }

        // 4. Pacientes: propietario = fisio de su plan más reciente.
        $pacientes = DB::table('users')->where('role', 'paciente')->whereNull('fisio_id')->get();

        foreach ($pacientes as $paciente) {
            $fisioId = DB::table('planes')
                ->where('paciente_id', $paciente->id)
                ->whereNotNull('fisio_id')
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->value('fisio_id');

            DB::table('users')
                ->where('id', $paciente->id)
                ->update(['fisio_id' => $fisioId ?: $fisioPorDefecto]);
        }
    }

    public function down(): void
    {
        // La asignación de propietario es un proceso de ida: al revertir el esquema,
        // los registros vuelven a ser globales y no se puede saber a quién pertenecían.
    }
};
