<?php

namespace App\Http\Controllers;

use App\Models\PeriodoInserimento;
use App\Models\Sessione;
use Illuminate\Http\Request;

class PeriodoInserimentoController extends Controller
{
    /**
     * Aggiunge un periodo di inserimento a una sessione.
     */
    public function store(Request $request, Sessione $sessione)
    {
        $dati = $request->validate([
            'data_inizio' => 'required|date',
            'data_fine' => 'required|date|after_or_equal:data_inizio',
        ], [], [
            'data_inizio' => 'data di inizio',
            'data_fine' => 'data di fine',
        ]);

        $sessione->periodiInserimento()->create($dati);

        return redirect()->route('sessioni.show', $sessione)->with('success', 'Periodo di inserimento aggiunto.');
    }

    /**
     * Rimuove un periodo di inserimento.
     */
    public function destroy(Sessione $sessione, PeriodoInserimento $periodo)
    {
        $periodo->delete();

        return redirect()->route('sessioni.show', $sessione)->with('success', 'Periodo di inserimento eliminato.');
    }
}
