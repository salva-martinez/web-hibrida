<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create plan_dias table
        Schema::create('plan_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes')->onDelete('cascade');
            $table->integer('numero_dia');
            $table->text('notas_fisio')->nullable();
            $table->timestamps();
        });

        // 2. Add plan_dia_id to plan_ejercicios and feedback
        Schema::table('plan_ejercicios', function (Blueprint $table) {
            $table->foreignId('plan_dia_id')->nullable()->constrained('plan_dias')->onDelete('cascade');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->foreignId('plan_dia_id')->nullable()->constrained('plan_dias')->onDelete('cascade');
        });

        // 3. Data Migration
        $planes = DB::table('planes')->get();
        foreach ($planes as $plan) {
            $planDiaId = DB::table('plan_dias')->insertGetId([
                'plan_id' => $plan->id,
                'numero_dia' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('plan_ejercicios')->where('plan_id', $plan->id)->update(['plan_dia_id' => $planDiaId]);
            DB::table('feedback')->where('plan_id', $plan->id)->update(['plan_dia_id' => $planDiaId]);
        }

        // 4. Drop old plan_id column
        Schema::table('plan_ejercicios', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['plan_id']);
            }
            $table->dropColumn('plan_id');
        });

        Schema::table('feedback', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['plan_id']);
            }
            $table->dropColumn('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('plan_ejercicios', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('planes')->onDelete('cascade');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('planes')->onDelete('cascade');
        });

        $dias = DB::table('plan_dias')->where('numero_dia', 1)->get();
        foreach ($dias as $dia) {
            DB::table('plan_ejercicios')->where('plan_dia_id', $dia->id)->update(['plan_id' => $dia->plan_id]);
            DB::table('feedback')->where('plan_dia_id', $dia->id)->update(['plan_id' => $dia->plan_id]);
        }

        Schema::table('plan_ejercicios', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['plan_dia_id']);
            }
            $table->dropColumn('plan_dia_id');
        });

        Schema::table('feedback', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['plan_dia_id']);
            }
            $table->dropColumn('plan_dia_id');
        });

        Schema::dropIfExists('plan_dias');
    }
};
