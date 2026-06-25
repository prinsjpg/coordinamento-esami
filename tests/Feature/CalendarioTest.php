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

class CalendarioTest extends TestCase
{
    use RefreshDatabase;

    private User $docente1;
    private User $docente2;
    private Sessione $sessione;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->docente1 = User::factory()->create(['name' => 'Mario Rossi']);
        $this->docente1->assignRole('docente');
        $this->docente2 = User::factory()->create(['name' => 'Luigi Bianchi']);
        $this->docente2->assignRole('docente');

        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $insA = Insegnamento::create(['nome' => 'Basi di Dati', 'anno_frequenza' => 2, 'corso_studio_id' => $corso->id]);
        $insB = Insegnamento::create(['nome' => 'Algoritmi', 'anno_frequenza' => 2, 'corso_studio_id' => $corso->id]);

        $this->sessione = Sessione::create([
            'nome' => 'Estiva',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);

        $giorno = Carbon::today()->addDays(5)->format('Y-m-d');

        Appello::create([
            'insegnamento_id' => $insA->id, 'sessione_id' => $this->sessione->id,
            'user_id' => $this->docente1->id, 'data' => $giorno,
            'ora_inizio' => '09:00', 'ora_fine' => '11:00',
        ]);
        Appello::create([
            'insegnamento_id' => $insB->id, 'sessione_id' => $this->sessione->id,
            'user_id' => $this->docente2->id, 'data' => $giorno,
            'ora_inizio' => '14:00', 'ora_fine' => '16:00',
        ]);
    }

    public function test_il_docente_vede_i_propri_appelli_ma_non_i_dettagli_altrui(): void
    {
        $response = $this->actingAs($this->docente1)
            ->get(route('calendario.index', ['sessione' => $this->sessione->id]));

        $response->assertOk();
        $response->assertSee('Basi di Dati');   // proprio appello
        $response->assertSee('Occupato');        // appello altrui mascherato
        $response->assertDontSee('Algoritmi');   // insegnamento altrui nascosto
        $response->assertDontSee('Luigi Bianchi'); // docente altrui nascosto
    }

    public function test_l_admin_vede_tutti_i_dettagli(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $response = $this->actingAs($admin)
            ->get(route('calendario.index', ['sessione' => $this->sessione->id]));

        $response->assertOk();
        $response->assertSee('Basi di Dati');
        $response->assertSee('Algoritmi');
        $response->assertSee('Luigi Bianchi');
        $response->assertDontSee('Occupato');
    }

    public function test_la_home_reindirizza_alla_dashboard(): void
    {
        $this->actingAs($this->docente1)->get('/')->assertRedirect(route('dashboard'));
    }
}
