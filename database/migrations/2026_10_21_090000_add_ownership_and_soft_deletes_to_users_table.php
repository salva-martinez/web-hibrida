<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el concepto de "propietario" (fisio) a los usuarios y el borrado lógico.
     *
     * - users.fisio_id: para los pacientes, indica el fisioterapeuta propietario.
     *   Para los propios fisioterapeutas queda NULL (cada fisio es su propia isla).
     * - users.deleted_at: borrado lógico. Una cuenta desactivada no aparece en las
     *   listas ni puede iniciar sesión, pero sus datos se conservan íntegros.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fisio_id')->nullable()->after('role');
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('fisio_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['fisio_id']);
            $table->dropColumn(['fisio_id', 'deleted_at']);
        });
    }
};
