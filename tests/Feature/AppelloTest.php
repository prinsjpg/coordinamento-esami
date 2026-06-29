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

class AppelloTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;
    private Insegnamento $insegnamento;
    private Sessione $sessione;

    protected function setUp(): void
    {
        parent::setUp();

        // Congela l'orologio su un lunedì di settembre (mese senza festività
        // nazionali), così le date di test sono giorni feriali deterministici.
        Carbon::setTestNow(Carbon::parse('2026-09-01')->next(Carbon::MONDAY));

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->docente = User::factory()->create();
        $this->docente->assignRole('docente');

        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $this->insegnamento = Insegnamento::create([
            'nome' => 'Programmazione',
            'anno_frequenza' => 1,
            'corso_studio_id' => $corso->id,
        ]);
        $this->docente->insegnamenti()->attach($this->insegnamento->id);

        // Sessione con finestra di inserimento aperta (oggi è compreso)
        $this->sessione = Sessione::create([
            'nome' => 'Sessione Estiva',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $this->sessione->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(2),
            'data_fine' => Carbon::today()->addDays(2),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datiValidi(array $override = []): array
    {
        return array_merge([
            'insegnamento_id' => $this->insegnamento->id,
            'sessione_id' => $this->sessione->id,
            // Mercoledì successivo: giorno feriale entro il periodo della sessione
            'data' => Carbon::today()->next(Carbon::WEDNESDAY)->format('Y-m-d'),
            'ora_inizio' => '09:00',
            'ora_fine' => '11:00',
            'aula' => 'Aula A1',
        ], $override);
    }

    public function test_il_docente_crea_un_proprio_appello(): void
    {
        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi());

        $response->assertRedirect(route('appelli.index'));
        $this->assertDatabaseHas('appelli', [
            'insegnamento_id' => $this->insegnamento->id,
            'user_id' => $this->docente->id,
            'aula' => 'Aula A1',
        ]);
    }

    public function test_il_docente_non_puo_usare_un_insegnamento_non_suo(): void
    {
        $altroInsegnamento = Insegnamento::create([
            'nome' => 'Analisi',
            'anno_frequenza' => 1,
            'corso_studio_id' => $this->insegnamento->corso_studio_id,
        ]);

        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'insegnamento_id' => $altroInsegnamento->id,
        ]));

        $response->assertSessionHasErrors('insegnamento_id');
        $this->assertDatabaseCount('appelli', 0);
    }

    public function test_la_data_fuori_dal_periodo_della_sessione_e_rifiutata(): void
    {
        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'data' => Carbon::today()->addDays(60)->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors('data');
        $this->assertDatabaseCount('appelli', 0);
    }

    public function test_il_docente_non_puo_inserire_se_la_finestra_e_chiusa(): void
    {
        $sessioneChiusa = Sessione::create([
            'nome' => 'Sessione Invernale',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $sessioneChiusa->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(20),
            'data_fine' => Carbon::today()->subDays(10),
        ]);

        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'sessione_id' => $sessioneChiusa->id,
        ]));

        $response->assertSessionHasErrors('sessione_id');
        $this->assertDatabaseCount('appelli', 0);
    }

    public function test_il_docente_non_puo_gestire_l_appello_di_un_insegnamento_non_suo(): void
    {
        $altroDocente = User::factory()->create();
        $altroDocente->assignRole('docente');

        // Insegnamento NON assegnato al docente che agisce
        $altroInsegnamento = Insegnamento::create([
            'nome' => 'Analisi',
            'anno_frequenza' => 1,
            'corso_studio_id' => $this->insegnamento->corso_studio_id,
        ]);
        $altroDocente->insegnamenti()->attach($altroInsegnamento->id);

        $appello = Appello::create($this->datiValidi([
            'user_id' => $altroDocente->id,
            'insegnamento_id' => $altroInsegnamento->id,
        ]));

        $this->actingAs($this->docente)->get(route('appelli.edit', $appello))->assertForbidden();
        $this->actingAs($this->docente)
            ->put(route('appelli.update', $appello), $this->datiValidi())
            ->assertForbidden();
    }

    public function test_il_docente_vede_i_propri_appelli_ma_non_quelli_di_insegnamenti_altrui(): void
    {
        $altroDocente = User::factory()->create();
        $altroDocente->assignRole('docente');

        // Insegnamento non assegnato al docente che agisce
        $altroInsegnamento = Insegnamento::create([
            'nome' => 'Analisi',
            'anno_frequenza' => 1,
            'corso_studio_id' => $this->insegnamento->corso_studio_id,
        ]);

        Appello::create($this->datiValidi(['user_id' => $this->docente->id, 'aula' => 'Mia']));
        Appello::create($this->datiValidi([
            'user_id' => $altroDocente->id,
            'insegnamento_id' => $altroInsegnamento->id,
            'aula' => 'Altrui',
        ]));

        $response = $this->actingAs($this->docente)->get(route('appelli.index'));

        $response->assertOk();
        $response->assertSee('Mia');
        $response->assertDontSee('Altrui');
    }

    public function test_il_docente_gestisce_gli_appelli_dei_co_titolari(): void
    {
        $coTitolare = User::factory()->create();
        $coTitolare->assignRole('docente');
        // Entrambi titolari dello stesso insegnamento
        $coTitolare->insegnamenti()->attach($this->insegnamento->id);

        // Appello creato dal co-titolare sull'insegnamento condiviso
        $appello = Appello::create($this->datiValidi([
            'user_id' => $coTitolare->id,
            'aula' => 'Condivisa',
        ]));

        // Il docente lo vede in elenco e può aprirne la modifica
        $this->actingAs($this->docente)->get(route('appelli.index'))->assertSee('Condivisa');
        $this->actingAs($this->docente)->get(route('appelli.edit', $appello))->assertOk();
    }

    public function test_il_docente_rimosso_dall_incarico_non_gestisce_piu_i_propri_appelli(): void
    {
        // Appello creato dal docente su un insegnamento di cui è titolare.
        $appello = Appello::create($this->datiValidi([
            'user_id' => $this->docente->id,
            'aula' => 'AppelloDelDocente',
        ]));

        // L'amministratore lo rimuove dalla titolarità dell'insegnamento.
        $this->docente->insegnamenti()->detach($this->insegnamento->id);

        // Pur essendone l'autore, non lo vede più in elenco né può gestirlo.
        $this->actingAs($this->docente)->get(route('appelli.index'))->assertDontSee('AppelloDelDocente');
        $this->actingAs($this->docente)->get(route('appelli.edit', $appello))->assertForbidden();
        $this->actingAs($this->docente)->delete(route('appelli.destroy', $appello))->assertForbidden();
        $this->assertDatabaseHas('appelli', ['id' => $appello->id]);
    }

    public function test_il_docente_vede_in_elenco_il_conflitto_con_un_appello_altrui(): void
    {
        // Mio appello (anno 1) in un'aula specifica
        Appello::create($this->datiValidi([
            'user_id' => $this->docente->id,
            'aula' => 'Lab X',
            'ora_inizio' => '09:00',
            'ora_fine' => '11:00',
        ]));

        // Appello di un altro docente, insegnamento non visibile al docente:
        // corso/anno diversi ma stessa aula e fascia sovrapposta.
        $altro = User::factory()->create();
        $altro->assignRole('docente');
        $insAltro = Insegnamento::create([
            'nome' => 'Analisi', 'anno_frequenza' => 2, 'corso_studio_id' => $this->insegnamento->corso_studio_id,
        ]);
        Appello::create($this->datiValidi([
            'user_id' => $altro->id,
            'insegnamento_id' => $insAltro->id,
            'aula' => 'lab x',
            'ora_inizio' => '10:00',
            'ora_fine' => '12:00',
        ]));

        $response = $this->actingAs($this->docente)->get(route('appelli.index'));

        $response->assertOk();
        $response->assertSee('conflitto');
    }

    private function sessioneFutura(): Sessione
    {
        // Sessione che inizia tra 20 giorni, con finestra di inserimento già
        // aperta oggi: utile per provare i preappelli.
        $sessione = Sessione::create([
            'nome' => 'Futura',
            'data_inizio' => Carbon::today()->addDays(20),
            'data_fine' => Carbon::today()->addDays(50),
        ]);
        $sessione->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDay(),
            'data_fine' => Carbon::today()->addDays(50),
        ]);

        return $sessione;
    }

    public function test_il_docente_puo_fissare_un_preappello_entro_il_margine(): void
    {
        $sessione = $this->sessioneFutura();

        // Margine default 14 giorni: una data 5 giorni prima dell'inizio sessione
        // (today+15) è un preappello ammesso. today è un lunedì → today+15 feriale.
        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'sessione_id' => $sessione->id,
            'data' => Carbon::today()->addDays(15)->format('Y-m-d'),
        ]));

        $response->assertRedirect(route('appelli.index'));
        $this->assertDatabaseCount('appelli', 1);
    }

    public function test_il_docente_non_puo_fissare_un_appello_oltre_il_margine_preappello(): void
    {
        $sessione = $this->sessioneFutura();

        // today+3 è prima di (inizio sessione − 14 = today+6): oltre il margine.
        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'sessione_id' => $sessione->id,
            'data' => Carbon::today()->addDays(3)->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors('data');
        $this->assertDatabaseCount('appelli', 0);
    }

    public function test_il_filtro_mostra_solo_gli_appelli_in_conflitto(): void
    {
        // Appello senza conflitti
        Appello::create($this->datiValidi([
            'user_id' => $this->docente->id,
            'aula' => 'Libera',
            'ora_inizio' => '08:00',
            'ora_fine' => '09:00',
        ]));

        // Due appelli in conflitto tra loro (stesso corso e anno, sovrapposti)
        $insB = Insegnamento::create([
            'nome' => 'Reti', 'anno_frequenza' => $this->insegnamento->anno_frequenza,
            'corso_studio_id' => $this->insegnamento->corso_studio_id,
        ]);
        $this->docente->insegnamenti()->attach($insB->id);

        Appello::create($this->datiValidi([
            'user_id' => $this->docente->id, 'aula' => 'AppelloUno',
            'ora_inizio' => '10:00', 'ora_fine' => '12:00',
        ]));
        Appello::create($this->datiValidi([
            'user_id' => $this->docente->id, 'insegnamento_id' => $insB->id, 'aula' => 'AppelloDue',
            'ora_inizio' => '11:00', 'ora_fine' => '13:00',
        ]));

        $response = $this->actingAs($this->docente)->get(route('appelli.index', ['conflitti' => 1]));

        $response->assertOk();
        $response->assertSee('AppelloUno');
        $response->assertSee('AppelloDue');
        $response->assertDontSee('Libera');
    }

    public function test_il_form_precompila_insegnamento_e_sessione_dalla_query(): void
    {
        $response = $this->actingAs($this->docente)->get(route('appelli.create', [
            'insegnamento' => $this->insegnamento->id,
            'sessione' => $this->sessione->id,
        ]));

        $response->assertOk();
        $response->assertSee('value="' . $this->insegnamento->id . '" selected', false);
        $response->assertSee('value="' . $this->sessione->id . '" selected', false);
    }

    public function test_non_si_puo_fissare_un_appello_in_una_data_passata(): void
    {
        // Tre giorni fa (venerdì rispetto al lunedì congelato): giorno feriale ma passato
        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'data' => Carbon::today()->subDays(3)->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors('data');
        $this->assertDatabaseCount('appelli', 0);
    }

    public function test_non_si_puo_fissare_un_appello_nel_weekend(): void
    {
        $sabato = Carbon::today()->next(Carbon::SATURDAY)->format('Y-m-d');

        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'data' => $sabato,
        ]));

        $response->assertSessionHasErrors('data');
        $this->assertDatabaseCount('appelli', 0);
    }

    public function test_non_si_puo_fissare_un_appello_in_una_festivita(): void
    {
        // Sessione ampia che comprende il giorno di Natale
        $sessione = Sessione::create([
            'nome' => 'Sessione Invernale',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(180),
        ]);
        $sessione->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(2),
            'data_fine' => Carbon::today()->addDays(2),
        ]);

        $response = $this->actingAs($this->docente)->post(route('appelli.store'), $this->datiValidi([
            'sessione_id' => $sessione->id,
            'data' => Carbon::create(Carbon::today()->year, 12, 25)->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors('data');
        $this->assertDatabaseCount('appelli', 0);
    }

    private function appelloAFinestraChiusa(): Appello
    {
        $sessioneChiusa = Sessione::create([
            'nome' => 'Sessione Chiusa',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $sessioneChiusa->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(20),
            'data_fine' => Carbon::today()->subDays(10),
        ]);

        return Appello::create($this->datiValidi([
            'user_id' => $this->docente->id,
            'sessione_id' => $sessioneChiusa->id,
        ]));
    }

    public function test_il_docente_non_puo_eliminare_un_appello_a_finestra_chiusa(): void
    {
        $appello = $this->appelloAFinestraChiusa();

        $this->actingAs($this->docente)
            ->delete(route('appelli.destroy', $appello))
            ->assertRedirect(route('appelli.index'));

        $this->assertDatabaseHas('appelli', ['id' => $appello->id]);
    }

    public function test_il_docente_non_puo_aprire_la_modifica_a_finestra_chiusa(): void
    {
        $appello = $this->appelloAFinestraChiusa();

        $this->actingAs($this->docente)
            ->get(route('appelli.edit', $appello))
            ->assertRedirect(route('appelli.index'));
    }

    public function test_l_admin_puo_eliminare_un_appello_anche_a_finestra_chiusa(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $appello = $this->appelloAFinestraChiusa();

        $this->actingAs($admin)
            ->delete(route('appelli.destroy', $appello))
            ->assertRedirect(route('appelli.index'));

        $this->assertDatabaseMissing('appelli', ['id' => $appello->id]);
    }

    public function test_l_admin_vede_tutti_gli_appelli_e_ignora_la_finestra(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $sessioneChiusa = Sessione::create([
            'nome' => 'Sessione Invernale',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $sessioneChiusa->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(20),
            'data_fine' => Carbon::today()->subDays(10),
        ]);

        // L'admin può inserire anche con finestra chiusa
        $this->actingAs($admin)->post(route('appelli.store'), $this->datiValidi([
            'sessione_id' => $sessioneChiusa->id,
        ]))->assertRedirect(route('appelli.index'));

        $this->assertDatabaseCount('appelli', 1);
    }

    public function test_il_docente_vede_nel_form_solo_le_sessioni_con_finestra_aperta(): void
    {
        $chiusa = Sessione::create([
            'nome' => 'Sessione Chiusa Test',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $chiusa->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(20),
            'data_fine' => Carbon::today()->subDays(10),
        ]);

        $response = $this->actingAs($this->docente)->get(route('appelli.create'));

        $response->assertOk();
        $response->assertSee('Sessione Estiva');        // finestra aperta (da setUp)
        $response->assertDontSee('Sessione Chiusa Test');
    }

    public function test_l_admin_vede_tutte_le_sessioni_nel_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        $chiusa = Sessione::create([
            'nome' => 'Sessione Chiusa Test',
            'data_inizio' => Carbon::today(),
            'data_fine' => Carbon::today()->addDays(30),
        ]);
        $chiusa->periodiInserimento()->create([
            'data_inizio' => Carbon::today()->subDays(20),
            'data_fine' => Carbon::today()->subDays(10),
        ]);

        $response = $this->actingAs($admin)->get(route('appelli.create'));

        $response->assertOk();
        $response->assertSee('Sessione Estiva');
        $response->assertSee('Sessione Chiusa Test');
    }
}
