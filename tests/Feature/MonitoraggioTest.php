<?php

namespace Tests\Feature;

use App\Models\Appello;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Models\User;
use App\Services\MonitoraggioService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonitoraggioTest extends TestCase
{
    use RefreshDatabase;

    private MonitoraggioService $service;
    private User $docente;
    private CorsoStudio $corso;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-07')); // lunedì
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->service = app(MonitoraggioService::class);

        $this->docente = User::factory()->create();
        $this->docente->assignRole('docente');

        $this->corso = CorsoStudio::create(['nome' => 'Informatica']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sessioneConFinestra(Carbon $inizio, Carbon $fine): Sessione
    {
        $sessione = Sessione::create([
            'nome' => 'Estiva',
            'data_inizio' => Carbon::today()->subDays(5),
            'data_fine' => Carbon::today()->addDays(60),
        ]);
        $sessione->periodiInserimento()->create(['data_inizio' => $inizio, 'data_fine' => $fine]);

        return $sessione;
    }

    private function insegnamento(string $nome, ?User $docente = null): Insegnamento
    {
        $ins = Insegnamento::create([
            'nome' => $nome,
            'anno_frequenza' => 1,
            'corso_studio_id' => $this->corso->id,
        ]);

        if ($docente !== null) {
            $docente->insegnamenti()->attach($ins->id);
        }

        return $ins;
    }

    private function appelloPer(Insegnamento $ins, Sessione $sessione, User $docente): void
    {
        Appello::create([
            'insegnamento_id' => $ins->id,
            'sessione_id' => $sessione->id,
            'user_id' => $docente->id,
            'data' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'ora_inizio' => '09:00',
            'ora_fine' => '11:00',
        ]);
    }

    public function test_stato_finestra_distingue_i_quattro_casi(): void
    {
        $inScadenza = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(3));
        $chiusa = $this->sessioneConFinestra(Carbon::today()->subDays(10), Carbon::today()->subDays(2));
        $aperta = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(30));
        $nonAperta = $this->sessioneConFinestra(Carbon::today()->addDays(5), Carbon::today()->addDays(10));

        $this->assertSame('in_scadenza', $this->service->statoFinestra($inScadenza));
        $this->assertSame('chiusa', $this->service->statoFinestra($chiusa));
        $this->assertSame('aperta', $this->service->statoFinestra($aperta));
        $this->assertSame('non_aperta', $this->service->statoFinestra($nonAperta));
    }

    public function test_admin_segnala_insegnamenti_senza_appello_con_finestra_in_scadenza(): void
    {
        $sessione = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(3));

        $conAppello = $this->insegnamento('Programmazione', $this->docente);
        $senzaAppello = $this->insegnamento('Reti di Calcolatori', $this->docente);
        $this->appelloPer($conAppello, $sessione, $this->docente);

        $segnalazioni = $this->service->segnalazioniAdmin();

        $this->assertCount(1, $segnalazioni);
        $nomi = $segnalazioni->first()['insegnamenti']->pluck('nome');
        $this->assertTrue($nomi->contains('Reti di Calcolatori'));
        $this->assertFalse($nomi->contains('Programmazione'));
    }

    public function test_admin_ignora_le_sessioni_con_finestra_ampiamente_aperta(): void
    {
        $sessione = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(30));
        $this->insegnamento('Reti di Calcolatori', $this->docente);

        $this->assertTrue($this->service->segnalazioniAdmin()->isEmpty());
    }

    public function test_docente_vede_solo_i_propri_insegnamenti_da_pianificare(): void
    {
        $sessione = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(3));

        $altroDocente = User::factory()->create();
        $altroDocente->assignRole('docente');

        $mio = $this->insegnamento('Reti di Calcolatori', $this->docente);
        $altrui = $this->insegnamento('Sistemi Operativi', $altroDocente);

        $segnalazioni = $this->service->segnalazioniDocente($this->docente);

        $this->assertCount(1, $segnalazioni);
        $nomi = $segnalazioni->first()['insegnamenti']->pluck('nome');
        $this->assertTrue($nomi->contains('Reti di Calcolatori'));
        $this->assertFalse($nomi->contains('Sistemi Operativi'));
    }

    public function test_la_dashboard_admin_mostra_la_sezione_di_monitoraggio(): void
    {
        $sessione = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(3));
        $this->insegnamento('Reti di Calcolatori', $this->docente);

        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Monitoraggio scadenze');
        $response->assertSee('Reti di Calcolatori');
    }

    public function test_la_dashboard_docente_elenca_gli_insegnamenti_da_pianificare(): void
    {
        $sessione = $this->sessioneConFinestra(Carbon::today()->subDay(), Carbon::today()->addDays(3));
        $this->insegnamento('Reti di Calcolatori', $this->docente);

        $response = $this->actingAs($this->docente)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Insegnamenti da pianificare');
        $response->assertSee('Reti di Calcolatori');
    }
}
