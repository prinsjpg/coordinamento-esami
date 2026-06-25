<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_un_ospite_non_autenticato_viene_rediretto_al_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
