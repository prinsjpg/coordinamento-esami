<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodi_inserimento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessione_id')->constrained('sessioni')->cascadeOnDelete();
            $table->date('data_inizio');
            $table->date('data_fine');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodi_inserimento');
    }
};
