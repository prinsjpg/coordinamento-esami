<?php

namespace Tests\Feature;

use App\Models\Appello;
use App\Models\Configurazione;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConflittoTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;
    private Insegnamento $insegnamentoA; // anno 2
    private Insegnamento $insegnamentoB; // anno 2
    private Insegnamento $insegnamentoC; // anno 1
    private Sessione $sessione;
    private string $giorno;

    protected function setUp(): void
    {
        parent::setUp();

        // Orologio congelato su un lunedì di settembre: le date di test sono feriali
        Carbon::setTestNow(Carbon::parse('2026-09-01')->next(Carbon::MONDAY));

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->docente = User::factory()->create();
        $this->docente->assignRole('docente');

        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $this->insegnamentoA = Insegnamento::create(['nome' => 'Basi di Dati', 'anno_frequenza' => 2, 'corso_studio_id' => $corso->id]);
        $this->insegnamentoB = Insegnamento::create(['nome' => 'Algoritmi', 'anno_frequenza' => 2, 'corso_studio_id' => $corso->id]);
        $this->insegnamentoC = Insegnamento::create(['nome' => 'Programmazione', 'anno_frequenza' => 1, 'corso_studio_id' => $corso->id]);
        $this->docente->insegnamenti()->attach([$this->insegnamentoA->id, $this->insegnamentoB->id, $this->insegnamentoC->id]);

        $this->sessione = Sessione::create([
            'nome' => 'Estiva',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $this->sessione->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDay(),
            'data_fine' => Carbon::today()->addDay(),
        ]);

        $this->giorno = Carbon::today()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        // Appello esistente: insegnamento B (anno 2), 10:00-12:00
        Appello::create([
            'insegnamento_id' => $this->insegnamentoB->id,
            'sessione_id' => $this->sessione->id,
            'user_id' => $this->docente->id,
            'data' => $this->giorno,
            'ora_inizio' => '10:00',
            'ora_fine' => '12:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function dati(array $override = []): array
    {
        return array_merge([
            'insegnamento_id' => $this->insegnamentoA->id,
            'sessione_id' => $this->sessione->id,
            'data' => $this->giorno,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
        ], $override);
    }

    public function test_endpoint_rileva_conflitto_stesso_anno_fascia_sovrapposta(): void
    {
        $response = $this->actingAs($this->docente)->getJson(route('appelli.verifica-conflitto', [
            'insegnamento_id' => $this->insegnamentoA->id,
            'data' => $this->giorno,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
        ]));

        $response->assertOk()->assertJson(['conflitto' => true, 'numero' => 1]);
    }

    public function test_endpoint_nessun_conflitto_con_fascia_non_sovrapposta(): void
    {
        $response = $this->actingAs($this->docente)->getJson(route('appelli.verifica-conflitto', [
            'insegnamento_id' => $this->insegnamentoA->id,
            'data' => $this->giorno,
            'ora_inizio' => '12:00',
            'ora_fine' => '14:00',
        ]));

        $response->assertOk()->assertJson(['conflitto' => false, 'numero' => 0]);
    }

    public function test_endpoint_nessun_conflitto_tra_corsi_diversi(): void
    {
        // Insegnamento dello stesso anno (2°) ma di un altro corso di studio
        $altroCorso = CorsoStudio::create(['nome' => 'Matematica']);
        $insAltroCorso = Insegnamento::create([
            'nome' => 'Analisi II', 'anno_frequenza' => 2, 'corso_studio_id' => $altroCorso->id,
        ]);
        $this->docente->insegnamenti()->attach($insAltroCorso->id);

        $response = $this->actingAs($this->docente)->getJson(route('appelli.verifica-conflitto', [
            'insegnamento_id' => $insAltroCorso->id,
            'data' => $this->giorno,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
        ]));

        $response->assertOk()->assertJson(['conflitto' => false]);
    }

    public function test_endpoint_nessun_conflitto_tra_anni_diversi(): void
    {
        $response = $this->actingAs($this->docente)->getJson(route('appelli.verifica-conflitto', [
            'insegnamento_id' => $this->insegnamentoC->id, // anno 1
            'data' => $this->giorno,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
        ]));

        $response->assertOk()->assertJson(['conflitto' => false]);
    }

    public function test_il_docente_non_vede_i_dettagli_del_conflitto(): void
    {
        $response = $this->actingAs($this->docente)->getJson(route('appelli.verifica-conflitto', [
            'insegnamento_id' => $this->insegnamentoA->id,
            'data' => $this->giorno,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
        ]));

        $dettaglio = $response->json('dettagli.0');
        $this->assertArrayHasKey('anno', $dettaglio);
        $this->assertArrayHasKey('orario', $dettaglio);
        $this->assertArrayNotHasKey('insegnamento', $dettaglio);
        $this->assertArrayNotHasKey('docente', $dettaglio);
    }

    public function test_l_admin_vede_i_dettagli_del_conflitto(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $dettaglio = $this->actingAs($admin)->getJson(route('appelli.verifica-conflitto', [
            'insegnamento_id' => $this->insegnamentoA->id,
            'data' => $this->giorno,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
        ]))->json('dettagli.0');

        $this->assertSame('Algoritmi', $dettaglio['insegnamento']);
        $this->assertArrayHasKey('docente', $dettaglio);
    }

    public function test_modalita_blocco_impedisce_il_salvataggio_in_conflitto(): void
    {
        Configurazione::create(['modalita_conflitto' => 'blocco']);

        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->dati());

        $response->assertSessionHasErrors('conflitto');
        $this->assertDatabaseMissing('appelli', ['insegnamento_id' => $this->insegnamentoA->id]);
    }

    public function test_modalita_warning_salva_ma_segnala(): void
    {
        Configurazione::create(['modalita_conflitto' => 'warning']);

        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->dati());

        $response->assertRedirect(route('appelli.index'));
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('appelli', ['insegnamento_id' => $this->insegnamentoA->id]);
    }
}
