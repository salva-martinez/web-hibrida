<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_ejercicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes')->onDelete('cascade');
            $table->foreignId('ejercicio_id')->constrained('ejercicios')->onDelete('cascade');
            $table->integer('series')->default(1);
            $table->string('repeticiones')->default('10'); // String to allow ranges like "6-10"
            $table->integer('intensidad')->default(5); // RPE 1-10
            $table->decimal('kg', 6, 2)->nullable();
            $table->string('descanso')->nullable(); // e.g. "2-3'"
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_ejercicios');
    }
};
