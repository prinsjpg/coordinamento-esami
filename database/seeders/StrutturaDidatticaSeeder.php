<?php

namespace Database\Seeders;

use App\Models\Appello;
use App\Models\Configurazione;
use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\PeriodoInserimento;
use App\Models\Sessione;
use App\Models\User;
use App\Support\CalendarioFestivita;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StrutturaDidatticaSeeder extends Seeder
{
    /**
     * Popola corsi, insegnamenti, sessioni, appelli con dati realistici.
     * I dati sono pensati per poter testare la regola dei conflitti
     * (stesso anno di frequenza + stessa data/fascia oraria).
     */
    public function run(): void
    {
        // Docenti creati da RolesAndPermissionsSeeder
        $docente1 = User::where('email', 'docente1@esami.test')->first();
        $docente2 = User::where('email', 'docente2@esami.test')->first();

        // Corsi di studio
        $informatica = CorsoStudio::create(['nome' => 'Corso di Laurea in Informatica']);
        $matematica = CorsoStudio::create(['nome' => 'Corso di Laurea in Matematica']);

        // Insegnamenti (nota: Basi di Dati e Algoritmi sono entrambi del 2° anno)
        $programmazione = Insegnamento::create([
            'nome' => 'Programmazione',
            'anno_frequenza' => 1,
            'corso_studio_id' => $informatica->id,
        ]);
        $basiDati = Insegnamento::create([
            'nome' => 'Basi di Dati',
            'anno_frequenza' => 2,
            'corso_studio_id' => $informatica->id,
        ]);
        $algoritmi = Insegnamento::create([
            'nome' => 'Algoritmi e Strutture Dati',
            'anno_frequenza' => 2,
            'corso_studio_id' => $informatica->id,
        ]);
        $analisi = Insegnamento::create([
            'nome' => 'Analisi Matematica',
            'anno_frequenza' => 1,
            'corso_studio_id' => $matematica->id,
        ]);
        // Stesso anno (2°) di Basi di Dati/Algoritmi, ma corso diverso (Matematica):
        // serve a mostrare che NON genera conflitto pur sovrapponendosi nell'orario.
        $algebra = Insegnamento::create([
            'nome' => 'Algebra Lineare',
            'anno_frequenza' => 2,
            'corso_studio_id' => $matematica->id,
        ]);

        // Associazione docenti <-> insegnamenti
        $docente1->insegnamenti()->attach([$programmazione->id, $basiDati->id]);
        $docente2->insegnamenti()->attach([$algoritmi->id, $analisi->id, $algebra->id]);

        // Sessione e finestra di inserimento (la data odierna rientra nella finestra)
        $sessione = Sessione::create([
            'nome' => 'Sessione Estiva',
            'data_inizio' => Carbon::today()->subDays(10),
            'data_fine' => Carbon::today()->addDays(50),
        ]);
        PeriodoInserimento::create([
            'sessione_id' => $sessione->id,
            'data_inizio' => Carbon::today()->subDays(5),
            'data_fine' => Carbon::today()->addDays(15),
        ]);

        // Appelli di esempio: si parte da oggi + 20 giorni, spostandosi al primo
        // giorno feriale (gli appelli non possono cadere nel weekend o nelle festività)
        $giorno = Carbon::today()->addDays(20);
        while (! CalendarioFestivita::eLavorativo($giorno)) {
            $giorno->addDay();
        }
        $giornoEsame = $giorno->format('Y-m-d');

        // Appello senza conflitti (1° anno)
        Appello::create([
            'insegnamento_id' => $programmazione->id,
            'sessione_id' => $sessione->id,
            'user_id' => $docente1->id,
            'data' => $giornoEsame,
            'ora_inizio' => '10:00',
            'ora_fine' => '12:00',
            'aula' => 'Aula A1',
            'note' => null,
        ]);

        // Coppia in conflitto: stesso corso (Informatica) e stesso anno (2°),
        // stessa data, fasce orarie sovrapposte.
        Appello::create([
            'insegnamento_id' => $basiDati->id,
            'sessione_id' => $sessione->id,
            'user_id' => $docente1->id,
            'data' => $giornoEsame,
            'ora_inizio' => '10:00',
            'ora_fine' => '12:00',
            'aula' => 'Aula B1',
            'note' => null,
        ]);
        Appello::create([
            'insegnamento_id' => $algoritmi->id,
            'sessione_id' => $sessione->id,
            'user_id' => $docente2->id,
            'data' => $giornoEsame,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
            'aula' => 'Aula B2',
            'note' => 'Sovrapposizione con Basi di Dati (stesso corso e anno)',
        ]);

        // Stesso anno (2°) e stessa fascia dei precedenti, ma corso diverso
        // (Matematica): NON è un conflitto perché gli studenti sono diversi.
        Appello::create([
            'insegnamento_id' => $algebra->id,
            'sessione_id' => $sessione->id,
            'user_id' => $docente2->id,
            'data' => $giornoEsame,
            'ora_inizio' => '11:00',
            'ora_fine' => '13:00',
            'aula' => 'Aula M1',
            'note' => 'Stesso anno di Basi di Dati/Algoritmi ma corso diverso: nessun conflitto',
        ]);

        // Impostazioni: modalità di gestione dei conflitti
        Configurazione::firstOrCreate([], ['modalita_conflitto' => 'blocco']);
    }
}
