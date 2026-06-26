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
use Tests\TestCase;

class StrutturaDidatticaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('amministratore');

        return $admin;
    }

    public function test_un_docente_non_puo_accedere_alla_gestione_struttura(): void
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $this->actingAs($docente)->get(route('corsi.index'))->assertForbidden();
        $this->actingAs($docente)->get(route('struttura.index'))->assertForbidden();
    }

    public function test_admin_crea_un_corso_di_studio(): void
    {
        $response = $this->actingAs($this->admin())->post(route('corsi.store'), [
            'nome' => 'Corso di Laurea in Fisica',
        ]);

        $response->assertRedirect(route('corsi.index'));
        $this->assertDatabaseHas('corsi_studio', ['nome' => 'Corso di Laurea in Fisica']);
    }

    public function test_admin_crea_un_insegnamento_con_docenti_associati(): void
    {
        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $response = $this->actingAs($this->admin())->post(route('insegnamenti.store'), [
            'nome' => 'Reti di Calcolatori',
            'anno_frequenza' => 3,
            'corso_studio_id' => $corso->id,
            'docenti' => [$docente->id],
        ]);

        $response->assertRedirect(route('insegnamenti.index'));

        $insegnamento = Insegnamento::firstWhere('nome', 'Reti di Calcolatori');
        $this->assertNotNull($insegnamento);
        $this->assertTrue($insegnamento->docenti->contains($docente));
    }

    public function test_la_validazione_blocca_un_insegnamento_senza_corso(): void
    {
        $response = $this->actingAs($this->admin())->post(route('insegnamenti.store'), [
            'nome' => 'Senza corso',
            'anno_frequenza' => 1,
        ]);

        $response->assertSessionHasErrors('corso_studio_id');
    }

    public function test_tutte_le_viste_admin_della_struttura_rispondono(): void
    {
        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $insegnamento = Insegnamento::create([
            'nome' => 'Programmazione',
            'anno_frequenza' => 1,
            'corso_studio_id' => $corso->id,
        ]);
        $sessione = \App\Models\Sessione::create([
            'nome' => 'Sessione Estiva',
            'data_inizio' => now(),
            'data_fine' => now()->addDays(30),
        ]);

        $admin = $this->admin();

        $pagine = [
            route('struttura.index'),
            route('corsi.index'),
            route('corsi.create'),
            route('corsi.edit', $corso),
            route('insegnamenti.index'),
            route('insegnamenti.create'),
            route('insegnamenti.edit', $insegnamento),
            route('sessioni.index'),
            route('sessioni.create'),
            route('sessioni.show', $sessione),
            route('sessioni.edit', $sessione),
            route('configurazione.edit'),
        ];

        foreach ($pagine as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    private function corsoConAppello(): CorsoStudio
    {
        $corso = CorsoStudio::create(['nome' => 'Informatica']);
        $ins = Insegnamento::create([
            'nome' => 'Programmazione', 'anno_frequenza' => 1, 'corso_studio_id' => $corso->id,
        ]);
        $sessione = Sessione::create([
            'nome' => 'Estiva', 'data_inizio' => now(), 'data_fine' => now()->addDays(30),
        ]);
        $sessione->periodiInserimento()->create([
            'data_inizio' => now()->subDay(), 'data_fine' => now()->addDay(),
        ]);

        $docente = User::factory()->create();
        $docente->assignRole('docente');

        Appello::create([
            'insegnamento_id' => $ins->id, 'sessione_id' => $sessione->id,
            'user_id' => $docente->id, 'data' => now()->addDays(5)->format('Y-m-d'),
            'ora_inizio' => '09:00', 'ora_fine' => '11:00',
        ]);

        return $corso;
    }

    public function test_il_messaggio_di_cancellazione_del_corso_indica_la_cascata(): void
    {
        $this->corsoConAppello();

        $response = $this->actingAs($this->admin())->get(route('corsi.index'));

        $response->assertOk();
        $response->assertSee('Verranno eliminati anche 1 insegnamento e 1 appello.');
    }

    public function test_il_messaggio_di_cancellazione_della_sessione_indica_la_cascata(): void
    {
        $this->corsoConAppello();

        $response = $this->actingAs($this->admin())->get(route('sessioni.index'));

        $response->assertOk();
        $response->assertSee('Verranno eliminati anche 1 finestra di inserimento e 1 appello.');
    }

    public function test_admin_aggiorna_la_modalita_dei_conflitti(): void
    {
        $response = $this->actingAs($this->admin())->put(route('configurazione.update'), [
            'modalita_conflitto' => 'warning',
        ]);

        $response->assertRedirect(route('configurazione.edit'));
        $this->assertSame('warning', Configurazione::first()->modalita_conflitto);
    }
}
