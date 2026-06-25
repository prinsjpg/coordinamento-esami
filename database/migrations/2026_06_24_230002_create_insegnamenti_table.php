<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insegnamenti', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedTinyInteger('anno_frequenza');
            $table->foreignId('corso_studio_id')->constrained('corsi_studio')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insegnamenti');
    }
};
