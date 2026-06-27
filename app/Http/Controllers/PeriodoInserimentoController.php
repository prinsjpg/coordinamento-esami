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
        // La finestra può aprirsi anche prima dell'inizio della sessione (così i
        // docenti pubblicano le date in anticipo), ma deve chiudersi entro la
        // fine della sessione: oltre quella data non avrebbe senso inserire.
        $dati = $request->validate([
            'data_inizio' => [
                'required', 'date',
                'before_or_equal:' . $sessione->data_fine->format('Y-m-d'),
            ],
            'data_fine' => [
                'required', 'date', 'after_or_equal:data_inizio',
                'before_or_equal:' . $sessione->data_fine->format('Y-m-d'),
            ],
        ], [
            'data_inizio.before_or_equal' => 'La finestra deve aprirsi entro la fine della sessione (entro il '
                . $sessione->data_fine->format('d/m/Y') . ').',
            'data_fine.before_or_equal' => 'La finestra deve terminare entro la fine della sessione (entro il '
                . $sessione->data_fine->format('d/m/Y') . ').',
        ], [
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
