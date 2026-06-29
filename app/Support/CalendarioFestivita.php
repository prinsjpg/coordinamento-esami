<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class CalendarioFestivita
{
    /** Festività italiane a data fissa, nel formato 'mm-gg' => nome. */
    private const FESTE_FISSE = [
        '01-01' => 'Capodanno',
        '01-06' => 'Epifania',
        '04-25' => 'Festa della Liberazione',
        '05-01' => 'Festa del Lavoro',
        '06-02' => 'Festa della Repubblica',
        '08-15' => 'Ferragosto',
        '11-01' => 'Tutti i Santi',
        '12-08' => 'Immacolata Concezione',
        '12-25' => 'Natale',
        '12-26' => 'Santo Stefano',
    ];

    /**
     * Indica se la data è un giorno lavorativo (né weekend né festività).
     */
    public static function eLavorativo(Carbon $data): bool
    {
        return ! $data->isWeekend() && self::nomeFestivita($data) === null;
    }

    /**
     * Elenco delle festività italiane di un anno, nel formato 'Y-m-d' => nome.
     * Utile per segnalare i giorni festivi lato client.
     *
     * @return array<string, string>
     */
    public static function festivitaDellAnno(int $anno): array
    {
        $feste = [];

        foreach (self::FESTE_FISSE as $meseGiorno => $nome) {
            $feste["{$anno}-{$meseGiorno}"] = $nome;
        }

        $feste[self::pasqua($anno)->addDay()->format('Y-m-d')] = 'Lunedì dell\'Angelo';

        return $feste;
    }

    /**
     * Restituisce il nome della festività italiana corrispondente alla data,
     * oppure null se non è una festività.
     */
    public static function nomeFestivita(Carbon $data): ?string
    {
        $chiave = $data->format('m-d');

        if (isset(self::FESTE_FISSE[$chiave])) {
            return self::FESTE_FISSE[$chiave];
        }

        // Lunedì dell'Angelo (Pasquetta): il giorno dopo la Pasqua
        if ($data->isSameDay(self::pasqua($data->year)->addDay())) {
            return 'Lunedì dell\'Angelo';
        }

        return null;
    }

    /**
     * Calcola la data della Pasqua per l'anno indicato (algoritmo di Gauss/Meeus).
     */
    private static function pasqua(int $anno): Carbon
    {
        $a = $anno % 19;
        $b = intdiv($anno, 100);
        $c = $anno % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mese = intdiv($h + $l - 7 * $m + 114, 31);
        $giorno = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($anno, $mese, $giorno)->startOfDay();
    }
}
