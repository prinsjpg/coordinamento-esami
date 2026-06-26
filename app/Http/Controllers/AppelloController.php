<?php

namespace App\Http\Controllers;

use App\Models\Appello;
use App\Models\Configurazione;
use App\Models\Insegnamento;
use App\Models\Sessione;
use App\Services\ConflittoService;
use App\Support\CalendarioFestivita;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AppelloController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // L'amministratore vede tutti gli appelli, il docente solo i propri
        if ($user->hasRole('amministratore')) {
            $appelli = Appello::with(['insegnamento.corsoStudio', 'sessione', 'docente'])
                ->orderBy('data')->orderBy('ora_inizio')->get();
        } else {
            $appelli = $user->appelli()->with(['insegnamento.corsoStudio', 'sessione'])
                ->orderBy('data')->orderBy('ora_inizio')->get();
        }

        return view('appelli.index', [
            'appelli' => $appelli,
            'isAdmin' => $user->hasRole('amministratore'),
        ]);
    }

    public function create(Request $request)
    {
        return view('appelli.create', [
            'appello' => new Appello(),
            'insegnamenti' => $this->insegnamentiDisponibili($request->user()),
            'sessioni' => Sessione::orderByDesc('data_inizio')->get(),
        ]);
    }

    public function store(Request $request, ConflittoService $conflitti)
    {
        $user = $request->user();
        $dati = $this->validateRequest($request, $user);

        if ($errore = $this->violazioneGiorno($dati['data'])) {
            return back()->withInput()->withErrors($errore);
        }

        $sessione = Sessione::findOrFail($dati['sessione_id']);

        if ($errore = $this->violazioniSessione($sessione, $dati['data'], $user)) {
            return back()->withInput()->withErrors($errore);
        }

        $insegnamento = Insegnamento::with('corsoStudio')->findOrFail($dati['insegnamento_id']);
        $aula = $dati['aula'] ?? null;

        $trovati = $conflitti->trovaConflitti(
            $insegnamento->id, $dati['data'], $dati['ora_inizio'], $dati['ora_fine'], $aula
        );

        if ($trovati->isNotEmpty() && $this->modalitaConflitto() === 'blocco') {
            return back()->withInput()->withErrors(['conflitto' => $this->messaggioConflitto($trovati, $insegnamento, $aula)]);
        }

        $appello = new Appello($dati);
        $appello->user_id = $user->id;
        $appello->save();

        return redirect()->route('appelli.index')
            ->with('success', 'Appello creato.')
            ->with($trovati->isNotEmpty() ? ['warning' => $this->messaggioConflitto($trovati, $insegnamento, $aula)] : []);
    }

    public function edit(Request $request, Appello $appello)
    {
        $this->autorizza($request->user(), $appello);

        return view('appelli.edit', [
            'appello' => $appello,
            'insegnamenti' => $this->insegnamentiDisponibili($request->user()),
            'sessioni' => Sessione::orderByDesc('data_inizio')->get(),
        ]);
    }

    public function update(Request $request, Appello $appello, ConflittoService $conflitti)
    {
        $user = $request->user();
        $this->autorizza($user, $appello);

        $dati = $this->validateRequest($request, $user);

        if ($errore = $this->violazioneGiorno($dati['data'])) {
            return back()->withInput()->withErrors($errore);
        }

        $sessione = Sessione::findOrFail($dati['sessione_id']);

        if ($errore = $this->violazioniSessione($sessione, $dati['data'], $user)) {
            return back()->withInput()->withErrors($errore);
        }

        $insegnamento = Insegnamento::with('corsoStudio')->findOrFail($dati['insegnamento_id']);
        $aula = $dati['aula'] ?? null;

        $trovati = $conflitti->trovaConflitti(
            $insegnamento->id, $dati['data'], $dati['ora_inizio'], $dati['ora_fine'], $aula, $appello->id
        );

        if ($trovati->isNotEmpty() && $this->modalitaConflitto() === 'blocco') {
            return back()->withInput()->withErrors(['conflitto' => $this->messaggioConflitto($trovati, $insegnamento, $aula)]);
        }

        $appello->update($dati);

        return redirect()->route('appelli.index')
            ->with('success', 'Appello aggiornato.')
            ->with($trovati->isNotEmpty() ? ['warning' => $this->messaggioConflitto($trovati, $insegnamento, $aula)] : []);
    }

    /**
     * Endpoint AJAX: verifica in tempo reale la presenza di conflitti.
     */
    public function verificaConflitto(Request $request, ConflittoService $conflitti)
    {
        $dati = $request->validate([
            'insegnamento_id' => ['required', 'integer'],
            'data' => ['required', 'date'],
            'ora_inizio' => ['required', 'date_format:H:i'],
            'ora_fine' => ['required', 'date_format:H:i', 'after:ora_inizio'],
            'aula' => ['nullable', 'string', 'max:255'],
            'appello_id' => ['nullable', 'integer'],
        ]);

        $insegnamento = Insegnamento::with('corsoStudio')->find($dati['insegnamento_id']);

        if ($insegnamento === null) {
            return response()->json(['conflitto' => false, 'numero' => 0, 'dettagli' => []]);
        }

        $aula = $dati['aula'] ?? null;

        $trovati = $conflitti->trovaConflitti(
            $insegnamento->id,
            $dati['data'],
            $dati['ora_inizio'],
            $dati['ora_fine'],
            $aula,
            isset($dati['appello_id']) ? (int) $dati['appello_id'] : null
        );

        // Visibilità differenziata: il docente vede solo anno, fascia e motivo,
        // l'amministratore vede anche insegnamento, docente e aula.
        $isAdmin = $request->user()->hasRole('amministratore');

        $dettagli = $trovati->map(function (Appello $a) use ($isAdmin, $insegnamento, $aula) {
            $fascia = Str::substr($a->ora_inizio, 0, 5) . '–' . Str::substr($a->ora_fine, 0, 5);

            $dettaglio = [
                'anno' => $a->insegnamento->anno_frequenza,
                'orario' => $fascia,
                'motivi' => $this->motiviConflitto($a, $insegnamento, $aula),
            ];

            if ($isAdmin) {
                $dettaglio['insegnamento'] = $a->insegnamento->nome;
                $dettaglio['docente'] = $a->docente->name;
                $dettaglio['aula'] = $a->aula;
            }

            return $dettaglio;
        })->values();

        return response()->json([
            'conflitto' => $trovati->isNotEmpty(),
            'numero' => $trovati->count(),
            'dettagli' => $dettagli,
        ]);
    }

    public function destroy(Request $request, Appello $appello)
    {
        $this->autorizza($request->user(), $appello);

        $appello->delete();

        return redirect()->route('appelli.index')->with('success', 'Appello eliminato.');
    }

    /**
     * Insegnamenti selezionabili: tutti per l'admin, solo i propri per il docente.
     */
    private function insegnamentiDisponibili($user)
    {
        $query = $user->hasRole('amministratore')
            ? Insegnamento::query()
            : $user->insegnamenti();

        return $query->with('corsoStudio')->orderBy('nome')->get();
    }

    /**
     * Verifica che l'utente possa gestire questo appello.
     */
    private function autorizza($user, Appello $appello): void
    {
        if (! $user->hasRole('amministratore') && $appello->user_id !== $user->id) {
            abort(403);
        }
    }

    /**
     * Regole di validazione comuni a creazione e modifica.
     */
    private function validateRequest(Request $request, $user): array
    {
        $insegnamentiPermessi = $this->insegnamentiDisponibili($user)->pluck('id')->all();

        return $request->validate([
            'insegnamento_id' => ['required', Rule::in($insegnamentiPermessi)],
            'sessione_id' => ['required', 'exists:sessioni,id'],
            'data' => ['required', 'date', 'after_or_equal:today'],
            'ora_inizio' => ['required', 'date_format:H:i'],
            'ora_fine' => ['required', 'date_format:H:i', 'after:ora_inizio'],
            'aula' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'insegnamento_id.in' => 'Seleziona un insegnamento tra quelli a te assegnati.',
            'data.after_or_equal' => 'Non è possibile fissare un appello in una data già passata.',
        ], [
            'insegnamento_id' => 'insegnamento',
            'sessione_id' => 'sessione',
            'ora_inizio' => 'ora di inizio',
            'ora_fine' => 'ora di fine',
        ]);
    }

    /**
     * Controlla i vincoli legati alla sessione: la data deve cadere nel periodo
     * della sessione e, per i docenti, la finestra di inserimento deve essere aperta.
     *
     * @return array<string, string>|null  Errori da mostrare, oppure null se tutto è valido.
     */
    private function violazioniSessione(Sessione $sessione, string $data, $user): ?array
    {
        $giorno = Carbon::parse($data);

        if ($giorno->lt($sessione->data_inizio) || $giorno->gt($sessione->data_fine)) {
            return ['data' => 'La data dell\'appello deve rientrare nel periodo della sessione ('
                . $sessione->data_inizio->format('d/m/Y') . ' – ' . $sessione->data_fine->format('d/m/Y') . ').'];
        }

        // L'amministratore non è soggetto al vincolo della finestra di inserimento
        if ($user->hasRole('amministratore')) {
            return null;
        }

        $oggi = Carbon::today();
        $finestraAperta = $sessione->periodiInserimento()
            ->whereDate('data_inizio', '<=', $oggi)
            ->whereDate('data_fine', '>=', $oggi)
            ->exists();

        if (! $finestraAperta) {
            return ['sessione_id' => 'La finestra di inserimento per questa sessione non è attualmente aperta.'];
        }

        return null;
    }

    /**
     * Verifica che la data dell'appello non cada nel weekend o in una festività.
     *
     * @return array<string, string>|null  Errore da mostrare, oppure null se valida.
     */
    private function violazioneGiorno(string $data): ?array
    {
        $giorno = Carbon::parse($data);

        if ($giorno->isWeekend()) {
            return ['data' => 'Non è possibile fissare un appello di sabato o domenica.'];
        }

        if ($festa = CalendarioFestivita::nomeFestivita($giorno)) {
            return ['data' => "Non è possibile fissare un appello in un giorno festivo ({$festa})."];
        }

        return null;
    }

    /**
     * Modalità di gestione dei conflitti impostata dall'amministratore.
     */
    private function modalitaConflitto(): string
    {
        return Configurazione::query()->value('modalita_conflitto') ?? 'blocco';
    }

    /**
     * Determina per quali motivi un appello esistente è in conflitto con quello
     * che si sta inserendo: "studenti" (stesso corso e anno) e/o "aula".
     *
     * @return array<int, string>
     */
    private function motiviConflitto(Appello $altro, Insegnamento $nuovo, ?string $aula): array
    {
        $motivi = [];

        if ((int) $altro->insegnamento->anno_frequenza === (int) $nuovo->anno_frequenza
            && (int) $altro->insegnamento->corso_studio_id === (int) $nuovo->corso_studio_id) {
            $motivi[] = 'studenti';
        }

        if ($aula !== null && trim($aula) !== ''
            && mb_strtolower(trim((string) $altro->aula)) === mb_strtolower(trim($aula))) {
            $motivi[] = 'aula';
        }

        return $motivi;
    }

    /**
     * Messaggio riassuntivo dei conflitti rilevati, distinguendo tra conflitto
     * sugli studenti (stesso corso e anno) e conflitto sull'aula.
     *
     * @param  \Illuminate\Support\Collection<int, Appello>  $conflitti
     */
    private function messaggioConflitto($conflitti, Insegnamento $insegnamento, ?string $aula): string
    {
        $studenti = $conflitti->filter(
            fn (Appello $a) => in_array('studenti', $this->motiviConflitto($a, $insegnamento, $aula), true)
        );
        $perAula = $conflitti->filter(
            fn (Appello $a) => in_array('aula', $this->motiviConflitto($a, $insegnamento, $aula), true)
        );

        $parti = [];

        if ($studenti->isNotEmpty()) {
            $parti[] = "{$studenti->count()} appello/i dello stesso anno ({$insegnamento->anno_frequenza}°) "
                . "del corso «{$insegnamento->corsoStudio->nome}»";
        }

        if ($perAula->isNotEmpty()) {
            $parti[] = "{$perAula->count()} appello/i nella stessa aula («" . trim((string) $aula) . "»)";
        }

        return 'Conflitto con ' . implode(' e con ', $parti) . ' nella stessa data e fascia oraria.';
    }
}
