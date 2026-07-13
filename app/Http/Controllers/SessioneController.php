<?php

namespace App\Http\Controllers;

use App\Models\Sessione;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SessioneController extends Controller
{
    public function index()
    {
        $sessioni = Sessione::withCount(['periodiInserimento', 'appelli'])
            ->orderByDesc('data_inizio')
            ->get();

        return view('sessioni.index', compact('sessioni'));
    }

    public function create()
    {
        return view('sessioni.create', ['sessione' => new Sessione()]);
    }

    public function store(Request $request)
    {
        $dati = $this->validateRequest($request);

        Sessione::create($dati);

        return redirect()->route('sessioni.index')->with('success', 'Sessione creata.');
    }

    public function show(Sessione $sessione)
    {
        $sessione->load('periodiInserimento');

        return view('sessioni.show', compact('sessione'));
    }

    public function edit(Sessione $sessione)
    {
        return view('sessioni.edit', compact('sessione'));
    }

    public function update(Request $request, Sessione $sessione)
    {
        $dati = $this->validateRequest($request);

        $sessione->update($dati);

        return redirect()->route('sessioni.index')->with('success', 'Sessione aggiornata.');
    }

    public function destroy(Sessione $sessione)
    {
        $sessione->delete();

        return redirect()->route('sessioni.index')->with('success', 'Sessione eliminata.');
    }

    /**
     * Giorni minimi di durata di una sessione (una settimana, estremi inclusi).
     */
    private const DURATA_MINIMA_GIORNI = 7;

    /**
     * Regole di validazione condivise tra creazione e modifica.
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'data_inizio' => ['required', 'date', 'after:today'],
            'data_fine' => [
                'required',
                'date',
                'after_or_equal:data_inizio',
                // La durata si conta con gli estremi inclusi: dal 1 al 7 è una settimana.
                function (string $attributo, mixed $valore, Closure $fail) use ($request) {
                    $inizio = $request->input('data_inizio');

                    if (! $inizio || ! strtotime($inizio)) {
                        return; // data di inizio mancante o non valida: la segnala la sua regola
                    }

                    $minima = Carbon::parse($inizio)->addDays(self::DURATA_MINIMA_GIORNI - 1);

                    if (Carbon::parse($valore)->lt($minima)) {
                        $fail('La sessione deve durare almeno una settimana: la data di fine non può precedere il '
                            . $minima->format('d/m/Y') . '.');
                    }
                },
            ],
        ], [
            'data_inizio.after' => 'La sessione deve iniziare da domani in poi.',
            'data_fine.after_or_equal' => 'La data di fine non può precedere quella di inizio.',
        ], [
            'data_inizio' => 'data di inizio',
            'data_fine' => 'data di fine',
        ]);
    }
}
