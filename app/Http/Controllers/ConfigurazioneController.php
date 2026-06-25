<?php

namespace App\Http\Controllers;

use App\Models\Configurazione;
use Illuminate\Http\Request;

class ConfigurazioneController extends Controller
{
    /**
     * Mostra il form di configurazione (riga unica di impostazioni).
     */
    public function edit()
    {
        $configurazione = Configurazione::firstOrCreate([], ['modalita_conflitto' => 'blocco']);

        return view('configurazione.edit', compact('configurazione'));
    }

    /**
     * Aggiorna la modalità di gestione dei conflitti.
     */
    public function update(Request $request)
    {
        $dati = $request->validate([
            'modalita_conflitto' => 'required|in:blocco,warning',
        ], [], [
            'modalita_conflitto' => 'modalità di gestione dei conflitti',
        ]);

        $configurazione = Configurazione::firstOrCreate([], ['modalita_conflitto' => 'blocco']);
        $configurazione->update($dati);

        return redirect()->route('configurazione.edit')->with('success', 'Configurazione aggiornata.');
    }
}
