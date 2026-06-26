<?php

namespace App\Http\Controllers;

use App\Models\CorsoStudio;
use Illuminate\Http\Request;

class CorsoStudioController extends Controller
{
    public function index()
    {
        $corsi = CorsoStudio::withCount(['insegnamenti', 'appelli'])->orderBy('nome')->get();

        return view('corsi.index', compact('corsi'));
    }

    public function create()
    {
        return view('corsi.create', ['corso' => new CorsoStudio()]);
    }

    public function store(Request $request)
    {
        $dati = $this->validateRequest($request);

        CorsoStudio::create($dati);

        return redirect()->route('corsi.index')->with('success', 'Corso di studio creato.');
    }

    public function edit(CorsoStudio $corso)
    {
        return view('corsi.edit', compact('corso'));
    }

    public function update(Request $request, CorsoStudio $corso)
    {
        $dati = $this->validateRequest($request, $corso);

        $corso->update($dati);

        return redirect()->route('corsi.index')->with('success', 'Corso di studio aggiornato.');
    }

    public function destroy(CorsoStudio $corso)
    {
        $corso->delete();

        return redirect()->route('corsi.index')->with('success', 'Corso di studio eliminato.');
    }

    /**
     * Regole di validazione condivise tra creazione e modifica.
     */
    private function validateRequest(Request $request, ?CorsoStudio $corso = null): array
    {
        $id = $corso?->id;

        return $request->validate([
            'nome' => "required|string|max:255|unique:corsi_studio,nome,{$id}",
        ], [], [
            'nome' => 'nome del corso',
        ]);
    }
}
