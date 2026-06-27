<?php

namespace Tests\Feature;

use App\Models\Sessione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PeriodoInserimentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Sessione $sessione;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('amministratore');

        $this->sessione = Sessione::create([
            'nome' => 'Sessione Estiva',
            'data_inizio' => Carbon::parse('2026-06-01'),
            'data_fine' => Carbon::parse('2026-07-31'),
        ]);
    }

    public function test_admin_aggiunge_un_periodo_dentro_la_sessione(): void
    {
        $response = $this->actingAs($this->admin)->post(route('periodi.store', $this->sessione), [
            'data_inizio' => '2026-06-10',
            'data_fine' => '2026-06-20',
        ]);

        $response->assertRedirect(route('sessioni.show', $this->sessione));
        $this->assertDatabaseHas('periodi_inserimento', [
            'sessione_id' => $this->sessione->id,
            'data_inizio' => '2026-06-10 00:00:00',
            'data_fine' => '2026-06-20 00:00:00',
        ]);
    }

    public function test_il_periodo_non_puo_iniziare_prima_della_sessione(): void
    {
        $response = $this->actingAs($this->admin)->post(route('periodi.store', $this->sessione), [
            'data_inizio' => '2026-05-20',
            'data_fine' => '2026-06-10',
        ]);

        $response->assertSessionHasErrors('data_inizio');
        $this->assertDatabaseCount('periodi_inserimento', 0);
    }

    public function test_il_periodo_non_puo_terminare_dopo_la_sessione(): void
    {
        $response = $this->actingAs($this->admin)->post(route('periodi.store', $this->sessione), [
            'data_inizio' => '2026-07-20',
            'data_fine' => '2026-08-15',
        ]);

        $response->assertSessionHasErrors('data_fine');
        $this->assertDatabaseCount('periodi_inserimento', 0);
    }
}
