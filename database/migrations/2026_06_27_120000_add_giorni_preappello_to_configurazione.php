<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurazione', function (Blueprint $table) {
            // Giorni di anticipo entro cui è ammesso un "preappello", cioè un
            // appello con data precedente l'inizio della sessione.
            $table->unsignedSmallInteger('giorni_preappello')->default(14)->after('modalita_conflitto');
        });
    }

    public function down(): void
    {
        Schema::table('configurazione', function (Blueprint $table) {
            $table->dropColumn('giorni_preappello');
        });
    }
};
