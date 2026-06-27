<?php

namespace Tests\Feature;

use App\Models\Appello;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_un_amministratore_vede_la_dashboard_amministrativa(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Corsi di studio');
        $response->assertSee('Prossimi appelli');
    }

    public function test_un_docente_vede_la_dashboard_docente(): void
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $response = $this->actingAs($docente)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('I miei insegnamenti');
        $response->assertDontSee('Corsi di studio');
    }

    public function test_l_admin_vede_la_card_dei_conflitti_in_dashboard(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01')->next(Carbon::MONDAY));

        $admin = User::factory()->create();
        $admin->assignRole('amministratore');
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $insA = Insegnamento::create(['nome' => 'Basi di Dati', 'anno_frequenza' => 2, 'corso_studio_id' => $corso->id]);
        $insB = Insegnamento::create(['nome' => 'Algoritmi', 'anno_frequenza' => 2, 'corso_studio_id' => $corso->id]);

        $sessione = Sessione::create([
            'nome' => 'Estiva', 'data_inizio' => Carbon::today(), 'data_fine' => Carbon::today()->addDays(30),
        ]);

        $giorno = Carbon::today()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        // Due appelli stesso corso e anno, fascia sovrapposta: in conflitto
        Appello::create(['insegnamento_id' => $insA->id, 'sessione_id' => $sessione->id, 'user_id' => $docente->id,
            'data' => $giorno, 'ora_inizio' => '09:00', 'ora_fine' => '11:00']);
        Appello::create(['insegnamento_id' => $insB->id, 'sessione_id' => $sessione->id, 'user_id' => $docente->id,
            'data' => $giorno, 'ora_inizio' => '10:00', 'ora_fine' => '12:00']);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Conflitti rilevati (2)');

        Carbon::setTestNow();
    }

    public function test_un_ospite_non_autenticato_viene_rediretto_al_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
