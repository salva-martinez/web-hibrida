<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplica el aislamiento por fisioterapeuta al catálogo de ejercicios y a los planes,
     * y añade borrado lógico para que nada se destruya físicamente.
     *
     * - estimulos.fisio_id / ejercicios.fisio_id: cada fisio trabaja con su propio
     *   catálogo. Antes eran globales, por lo que un fisio podía ver (y borrar)
     *   ejercicios que otros usaban en sus planes.
     * - planes.deleted_at: archivar un plan no destruye sus días, sus ejercicios
     *   pautados ni el feedback clínico del paciente.
     */
    public function up(): void
    {
        Schema::table('estimulos', function (Blueprint $table) {
            $table->foreignId('fisio_id')->nullable()->after('id');
            $table->softDeletes();
        });

        Schema::table('ejercicios', function (Blueprint $table) {
            $table->foreignId('fisio_id')->nullable()->after('id');
            $table->softDeletes();
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('estimulos', function (Blueprint $table) {
            $table->index('fisio_id');
        });

        Schema::table('ejercicios', function (Blueprint $table) {
            $table->index('fisio_id');
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->index('fisio_id');
        });
    }

    public function down(): void
    {
        Schema::table('estimulos', function (Blueprint $table) {
            $table->dropIndex(['fisio_id']);
            $table->dropColumn(['fisio_id', 'deleted_at']);
        });

        Schema::table('ejercicios', function (Blueprint $table) {
            $table->dropIndex(['fisio_id']);
            $table->dropColumn(['fisio_id', 'deleted_at']);
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->dropIndex(['fisio_id']);
            $table->dropColumn('deleted_at');
        });
    }
};
