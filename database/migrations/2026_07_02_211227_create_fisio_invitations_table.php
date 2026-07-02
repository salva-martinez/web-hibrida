<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fisio_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email')->nullable();
            $table->foreignId('invited_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('used_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fisio_invitations');
    }
};
