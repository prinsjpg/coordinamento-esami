<?php

namespace App\Http\Controllers;

use App\Models\Appello;
use App\Models\Insegnamento;
use App\Models\Sessione;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function store(Request $request)
    {
        $user = $request->user();
        $dati = $this->validateRequest($request, $user);

        $sessione = Sessione::findOrFail($dati['sessione_id']);

        if ($errore = $this->violazioniSessione($sessione, $dati['data'], $user)) {
            return back()->withInput()->withErrors($errore);
        }

        $appello = new Appello($dati);
        $appello->user_id = $user->id;
        $appello->save();

        return redirect()->route('appelli.index')->with('success', 'Appello creato.');
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

    public function update(Request $request, Appello $appello)
    {
        $user = $request->user();
        $this->autorizza($user, $appello);

        $dati = $this->validateRequest($request, $user);
        $sessione = Sessione::findOrFail($dati['sessione_id']);

        if ($errore = $this->violazioniSessione($sessione, $dati['data'], $user)) {
            return back()->withInput()->withErrors($errore);
        }

        $appello->update($dati);

        return redirect()->route('appelli.index')->with('success', 'Appello aggiornato.');
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
            'data' => ['required', 'date'],
            'ora_inizio' => ['required', 'date_format:H:i'],
            'ora_fine' => ['required', 'date_format:H:i', 'after:ora_inizio'],
            'aula' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'insegnamento_id.in' => 'Seleziona un insegnamento tra quelli a te assegnati.',
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
}
