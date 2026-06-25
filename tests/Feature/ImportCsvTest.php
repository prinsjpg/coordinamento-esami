<?php

namespace Tests\Feature;

use App\Models\Insegnamento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportCsvTest extends TestCase
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

    private function csv(string $contenuto): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('struttura.csv', $contenuto);
    }

    public function test_la_pagina_di_import_e_riservata_all_admin(): void
    {
        $this->actingAs($this->admin())->get(route('import.index'))->assertOk();

        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $this->actingAs($docente)->get(route('import.index'))->assertForbidden();
    }

    public function test_scarica_il_csv_di_esempio(): void
    {
        $response = $this->actingAs($this->admin())->get(route('import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('corso,insegnamento,anno_frequenza,docenti', $response->getContent());
    }

    public function test_importa_un_csv_valido_con_docenti(): void
    {
        $docente = User::factory()->create(['email' => 'mario@esami.test']);
        $docente->assignRole('docente');

        $contenuto = "corso,insegnamento,anno_frequenza,docenti\n"
            . "Corso di Laurea in Informatica,Programmazione,1,mario@esami.test\n"
            . "Corso di Laurea in Informatica,Basi di Dati,2,\n";

        $response = $this->actingAs($this->admin())->post(route('import.store'), [
            'file' => $this->csv($contenuto),
        ]);

        $response->assertSessionHas('import_riepilogo');
        $this->assertDatabaseHas('corsi_studio', ['nome' => 'Corso di Laurea in Informatica']);
        $this->assertDatabaseHas('insegnamenti', ['nome' => 'Programmazione', 'anno_frequenza' => 1]);

        $insegnamento = Insegnamento::firstWhere('nome', 'Programmazione');
        $this->assertTrue($insegnamento->docenti->contains($docente));
    }

    public function test_rileva_il_separatore_punto_e_virgola(): void
    {
        $contenuto = "corso;insegnamento;anno_frequenza;docenti\n"
            . "Matematica;Analisi;1;\n";

        $this->actingAs($this->admin())->post(route('import.store'), [
            'file' => $this->csv($contenuto),
        ])->assertSessionHas('import_riepilogo');

        $this->assertDatabaseHas('insegnamenti', ['nome' => 'Analisi', 'anno_frequenza' => 1]);
    }

    public function test_un_csv_con_errori_non_importa_nulla(): void
    {
        $contenuto = "corso,insegnamento,anno_frequenza,docenti\n"
            . "Informatica,Programmazione,9,\n"            // anno non valido
            . "Informatica,,2,\n";                         // insegnamento mancante

        $response = $this->actingAs($this->admin())->post(route('import.store'), [
            'file' => $this->csv($contenuto),
        ]);

        $response->assertSessionHas('import_errori');
        $this->assertDatabaseCount('corsi_studio', 0);
        $this->assertDatabaseCount('insegnamenti', 0);
    }

    public function test_un_csv_senza_colonne_obbligatorie_viene_rifiutato(): void
    {
        $contenuto = "nome,anno\nInformatica,1\n";

        $this->actingAs($this->admin())->post(route('import.store'), [
            'file' => $this->csv($contenuto),
        ])->assertSessionHas('error');
    }
}
