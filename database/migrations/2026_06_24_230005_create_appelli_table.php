<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appelli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insegnamento_id')->constrained('insegnamenti')->cascadeOnDelete();
            $table->foreignId('sessione_id')->constrained('sessioni')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // docente
            $table->date('data');
            $table->time('ora_inizio');
            $table->time('ora_fine');
            $table->string('aula')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appelli');
    }
};
