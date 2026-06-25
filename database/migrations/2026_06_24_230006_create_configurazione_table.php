<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurazione', function (Blueprint $table) {
            $table->id();
            $table->enum('modalita_conflitto', ['blocco', 'warning'])->default('blocco');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurazione');
    }
};
