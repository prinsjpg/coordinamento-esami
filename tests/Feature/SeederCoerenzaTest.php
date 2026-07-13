<?php

namespace Tests\Feature;

use App\Models\Appello;
use App\Models\Sessione;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * I dati del seeder devono rispettare gli stessi vincoli che il form impone:
 * altrimenti in demo si aprirebbe un appello seminato, si premerebbe Salva e la
 * validazione lo rifiuterebbe. Qui si ri-salva tutto passando dai controller.
 */
class SeederCoerenzaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@esami.test')->firstOrFail();
    }

    public function test_ogni_appello_del_seeder_e_ri_salvabile_dal_form(): void
    {
        $appelli = Appello::all();

        $this->assertNotEmpty($appelli, 'Il seeder non ha creato appelli.');

        foreach ($appelli as $appello) {
            $response = $this->actingAs($this->admin())->put(route('appelli.update', $appello), [
                'insegnamento_id' => $appello->insegnamento_id,
                'sessione_id' => $appello->sessione_id,
                'data' => $appello->data->format('Y-m-d'),
                'ora_inizio' => substr((string) $appello->ora_inizio, 0, 5),
                'ora_fine' => substr((string) $appello->ora_fine, 0, 5),
                'aula' => $appello->aula,
                'note' => $appello->note,
            ]);

            // La modalità conflitti di default è "blocco": i conflitti voluti dal
            // seeder farebbero fallire il salvataggio. Qui interessano solo i
            // vincoli su data e orario, quindi si escludono i campi dei conflitti.
            $response->assertSessionDoesntHaveErrors(['data', 'ora_inizio', 'ora_fine', 'sessione_id']);
        }
    }

    public function test_la_sessione_del_seeder_e_ri_salvabile_dal_form(): void
    {
        $sessione = Sessione::firstOrFail();

        $response = $this->actingAs($this->admin())->put(route('sessioni.update', $sessione), [
            'nome' => $sessione->nome,
            'data_inizio' => $sessione->data_inizio->format('Y-m-d'),
            'data_fine' => $sessione->data_fine->format('Y-m-d'),
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_la_finestra_di_inserimento_e_aperta_oggi(): void
    {
        // Se fosse chiusa, in demo il docente non potrebbe inserire nulla.
        $this->assertTrue(
            Sessione::conFinestraAperta()->exists(),
            'Nessuna sessione ha la finestra di inserimento aperta oggi.'
        );
    }
}
