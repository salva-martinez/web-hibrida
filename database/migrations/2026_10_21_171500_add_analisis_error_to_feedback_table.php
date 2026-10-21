<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motivo por el que no se pudo generar el análisis de IA.
 *
 * Sin esto, el panel del fisioterapeuta muestra "Pendiente" durante dos
 * minutos y luego se queda sin explicación: no hay forma de saber si la IA
 * está lenta, si se ha agotado la cuota o si la clave ha caducado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('analisis_error', 30)->nullable()->after('analisis_ia');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('analisis_error');
        });
    }
};
