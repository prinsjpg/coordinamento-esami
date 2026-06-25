<?php

namespace App\Http\Controllers;

use App\Models\Sessione;
use Illuminate\Http\Request;

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
     * Regole di validazione condivise tra creazione e modifica.
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'data_inizio' => 'required|date',
            'data_fine' => 'required|date|after_or_equal:data_inizio',
        ], [], [
            'data_inizio' => 'data di inizio',
            'data_fine' => 'data di fine',
        ]);
    }
}
