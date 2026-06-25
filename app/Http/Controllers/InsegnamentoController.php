<?php

namespace App\Http\Controllers;

use App\Models\CorsoStudio;
use App\Models\Insegnamento;
use App\Models\User;
use Illuminate\Http\Request;

class InsegnamentoController extends Controller
{
    public function index()
    {
        $insegnamenti = Insegnamento::with(['corsoStudio', 'docenti'])
            ->orderBy('nome')
            ->get();

        return view('insegnamenti.index', compact('insegnamenti'));
    }

    public function create()
    {
        return view('insegnamenti.create', [
            'insegnamento' => new Insegnamento(),
            'corsi' => CorsoStudio::orderBy('nome')->get(),
            'docenti' => $this->docenti(),
            'docentiSelezionati' => [],
        ]);
    }

    public function store(Request $request)
    {
        $dati = $this->validateRequest($request);

        $insegnamento = Insegnamento::create($dati);
        $insegnamento->docenti()->sync($request->input('docenti', []));

        return redirect()->route('insegnamenti.index')->with('success', 'Insegnamento creato.');
    }

    public function edit(Insegnamento $insegnamento)
    {
        return view('insegnamenti.edit', [
            'insegnamento' => $insegnamento,
            'corsi' => CorsoStudio::orderBy('nome')->get(),
            'docenti' => $this->docenti(),
            'docentiSelezionati' => $insegnamento->docenti->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Insegnamento $insegnamento)
    {
        $dati = $this->validateRequest($request);

        $insegnamento->update($dati);
        $insegnamento->docenti()->sync($request->input('docenti', []));

        return redirect()->route('insegnamenti.index')->with('success', 'Insegnamento aggiornato.');
    }

    public function destroy(Insegnamento $insegnamento)
    {
        $insegnamento->delete();

        return redirect()->route('insegnamenti.index')->with('success', 'Insegnamento eliminato.');
    }

    /**
     * Elenco dei docenti selezionabili come titolari.
     */
    private function docenti()
    {
        return User::role('docente')->orderBy('name')->get();
    }

    /**
     * Regole di validazione condivise tra creazione e modifica.
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'anno_frequenza' => 'required|integer|min:1|max:3',
            'corso_studio_id' => 'required|exists:corsi_studio,id',
            'docenti' => 'array',
            'docenti.*' => 'exists:users,id',
        ], [], [
            'corso_studio_id' => 'corso di studio',
            'anno_frequenza' => 'anno di frequenza',
        ]);
    }
}
