<?php

namespace Tests\Unit;

use App\Support\CalendarioFestivita;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CalendarioFestivitaTest extends TestCase
{
    public function test_riconosce_le_festivita_a_data_fissa(): void
    {
        $this->assertSame('Natale', CalendarioFestivita::nomeFestivita(Carbon::parse('2025-12-25')));
        $this->assertSame('Capodanno', CalendarioFestivita::nomeFestivita(Carbon::parse('2026-01-01')));
        $this->assertSame('Festa della Liberazione', CalendarioFestivita::nomeFestivita(Carbon::parse('2026-04-25')));
        $this->assertSame('Ferragosto', CalendarioFestivita::nomeFestivita(Carbon::parse('2026-08-15')));
    }

    public function test_riconosce_la_pasquetta_mobile(): void
    {
        // Pasqua 2026 cade il 5 aprile, quindi il Lunedì dell'Angelo è il 6 aprile
        $this->assertSame('Lunedì dell\'Angelo', CalendarioFestivita::nomeFestivita(Carbon::parse('2026-04-06')));
    }

    public function test_un_giorno_feriale_non_e_festivo(): void
    {
        $this->assertNull(CalendarioFestivita::nomeFestivita(Carbon::parse('2026-03-04')));
    }

    public function test_e_lavorativo_distingue_weekend_e_festivi(): void
    {
        // Mercoledì feriale
        $this->assertTrue(CalendarioFestivita::eLavorativo(Carbon::parse('2026-03-04')));
        // Sabato
        $this->assertFalse(CalendarioFestivita::eLavorativo(Carbon::parse('2026-03-07')));
        // Natale (feriale ma festivo)
        $this->assertFalse(CalendarioFestivita::eLavorativo(Carbon::parse('2025-12-25')));
    }
}
