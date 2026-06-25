<?php

namespace App\Http\Controllers;

use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    /** Colonne obbligatorie attese nell'intestazione del CSV. */
    private const COLONNE_RICHIESTE = ['corso', 'insegnamento', 'anno_frequenza'];

    /**
     * Mostra la pagina di import con le istruzioni sul formato.
     */
    public function index()
    {
        return view('import.index');
    }

    /**
     * Genera e scarica un file CSV di esempio.
     */
    public function template()
    {
        $righe = [
            'corso,insegnamento,anno_frequenza,docenti',
            'Corso di Laurea in Informatica,Programmazione,1,docente1@esami.test',
            'Corso di Laurea in Informatica,Basi di Dati,2,docente1@esami.test|docente2@esami.test',
            'Corso di Laurea in Matematica,Analisi Matematica,1,docente2@esami.test',
        ];

        return response(implode("\r\n", $righe) . "\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="struttura-esempio.csv"',
        ]);
    }

    /**
     * Riceve il file CSV, valida le righe e importa la struttura didattica.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ], [], ['file' => 'file CSV']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Impossibile leggere il file caricato.');
        }

        // Rileva il separatore (Excel italiano usa spesso il punto e virgola)
        $primaRiga = fgets($handle);
        $delimitatore = substr_count($primaRiga, ';') > substr_count($primaRiga, ',') ? ';' : ',';
        rewind($handle);

        // Intestazione
        $intestazione = fgetcsv($handle, 0, $delimitatore);
        if ($intestazione === false) {
            fclose($handle);

            return back()->with('error', 'Il file è vuoto.');
        }

        $intestazione = array_map(
            fn ($valore) => strtolower(trim((string) $valore, " \t\n\r\0\x0B\xEF\xBB\xBF")),
            $intestazione
        );

        $mancanti = array_diff(self::COLONNE_RICHIESTE, $intestazione);
        if (! empty($mancanti)) {
            fclose($handle);

            return back()->with('error', 'Colonne obbligatorie mancanti: ' . implode(', ', $mancanti) . '.');
        }

        // Lettura e validazione delle righe
        $righeValide = [];
        $errori = [];
        $numeroRiga = 1; // la riga 1 è l'intestazione

        while (($dati = fgetcsv($handle, 0, $delimitatore)) !== false) {
            $numeroRiga++;

            // Salta le righe completamente vuote
            if (count(array_filter($dati, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $riga = array_combine($intestazione, array_pad($dati, count($intestazione), ''));

            $corso = trim((string) ($riga['corso'] ?? ''));
            $insegnamento = trim((string) ($riga['insegnamento'] ?? ''));
            $anno = trim((string) ($riga['anno_frequenza'] ?? ''));
            $docentiCella = trim((string) ($riga['docenti'] ?? ''));

            $messaggi = [];

            if ($corso === '') {
                $messaggi[] = 'corso mancante';
            }
            if ($insegnamento === '') {
                $messaggi[] = 'insegnamento mancante';
            }
            if (! ctype_digit($anno) || (int) $anno < 1 || (int) $anno > 3) {
                $messaggi[] = "anno di frequenza non valido ('{$anno}', atteso 1-3)";
            }

            // Risoluzione dei docenti per email (devono già esistere come docenti)
            $idDocenti = [];
            if ($docentiCella !== '') {
                $emails = array_filter(array_map('trim', explode('|', $docentiCella)));
                foreach ($emails as $email) {
                    $docente = User::role('docente')->where('email', $email)->first();
                    if ($docente === null) {
                        $messaggi[] = "docente non trovato ('{$email}')";
                    } else {
                        $idDocenti[] = $docente->id;
                    }
                }
            }

            if (! empty($messaggi)) {
                $errori[] = ['riga' => $numeroRiga, 'messaggio' => implode('; ', $messaggi)];

                continue;
            }

            $righeValide[] = [
                'corso' => $corso,
                'insegnamento' => $insegnamento,
                'anno' => (int) $anno,
                'docenti' => $idDocenti,
            ];
        }

        fclose($handle);

        // Import all-or-nothing: in presenza di errori non si scrive nulla
        if (! empty($errori)) {
            return back()->with('import_errori', $errori);
        }

        if (empty($righeValide)) {
            return back()->with('error', 'Il file non contiene righe da importare.');
        }

        $riepilogo = [
            'corsi_creati' => 0,
            'insegnamenti_creati' => 0,
            'insegnamenti_aggiornati' => 0,
            'associazioni' => 0,
        ];

        DB::transaction(function () use ($righeValide, &$riepilogo) {
            foreach ($righeValide as $riga) {
                $corso = CorsoStudio::firstOrCreate(['nome' => $riga['corso']]);
                if ($corso->wasRecentlyCreated) {
                    $riepilogo['corsi_creati']++;
                }

                $insegnamento = Insegnamento::updateOrCreate(
                    ['nome' => $riga['insegnamento'], 'corso_studio_id' => $corso->id],
                    ['anno_frequenza' => $riga['anno']]
                );
                $insegnamento->wasRecentlyCreated
                    ? $riepilogo['insegnamenti_creati']++
                    : $riepilogo['insegnamenti_aggiornati']++;

                if (! empty($riga['docenti'])) {
                    $insegnamento->docenti()->syncWithoutDetaching($riga['docenti']);
                    $riepilogo['associazioni'] += count($riga['docenti']);
                }
            }
        });

        return back()->with('import_riepilogo', $riepilogo);
    }
}
