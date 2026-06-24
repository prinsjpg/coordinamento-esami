<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_insegnamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('insegnamento_id')->constrained('insegnamenti')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'insegnamento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_insegnamento');
    }
};
