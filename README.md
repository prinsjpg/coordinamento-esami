# Sistema Web per il Coordinamento delle Date di Esame

Applicazione web per la gestione e il coordinamento degli appelli d'esame di un
dipartimento universitario. Permette all'amministratore di definire la struttura
didattica (corsi, insegnamenti, sessioni) e ai docenti di pianificare i propri
appelli, con verifica automatica dei conflitti (stesso corso/anno o stessa aula).

Progetto per il corso di **Programmazione Web**.

## Tecnologie

- **PHP 8.3 o superiore** e **Laravel 13**
- **MySQL** (database `esami_coordinamento`)
- **Laravel Jetstream** (Livewire) per autenticazione e profilo
- **Spatie laravel-permission** per ruoli e permessi
- **Bootstrap 5.3** + **jQuery** (via CDN) per l'interfaccia delle pagine applicative
- Verifica conflitti in tempo reale tramite **jQuery/AJAX**

## Ruoli

- **Amministratore**: gestisce la struttura didattica (corsi, insegnamenti,
  sessioni, finestre di inserimento), l'import CSV, la configurazione dei
  conflitti e vede tutti gli appelli.
- **Docente**: crea, modifica ed elimina gli appelli dei propri insegnamenti
  (anche quando condivisi con un co-titolare), scegliendo solo tra gli
  insegnamenti a lui assegnati e all'interno delle finestre di inserimento.

## Regola dei conflitti

Due appelli sono in **conflitto** quando cadono nella **stessa data**, le
**fasce orarie si sovrappongono** e si verifica almeno una di queste condizioni:

- **conflitto studenti**: gli insegnamenti appartengono allo **stesso corso di
  studio** e allo **stesso anno di frequenza** (gli studenti coinvolti sono gli stessi);
- **conflitto aula**: la **stessa aula** risulterebbe occupata due volte (il
  confronto ignora spazi e maiuscole).

La modalità di gestione è configurabile dall'amministratore:

- **Blocco**: impedisce il salvataggio dell'appello in conflitto.
- **Avviso**: consente il salvataggio segnalando il conflitto.

Gli appelli già in conflitto (tipicamente salvati in modalità «avviso») sono
inoltre **evidenziati** nel calendario e nell'elenco, e l'elenco offre un filtro
**«solo conflitti»** per isolarli e intervenire.

La visibilità è differenziata: il docente vede solo data, corso, anno e fascia
occupati degli appelli altrui, mentre l'amministratore vede tutti i dettagli.
I conflitti del docente sono però calcolati sull'insieme completo degli appelli,
non solo sui suoi: altrimenti una doppia prenotazione d'aula sfuggirebbe.

## Calendario

Il **calendario** mostra gli appelli di una sessione raggruppati per data, con
la sessione selezionabile (di default la più recente). Vale la stessa
differenziazione di visibilità dell'elenco, con i conflitti evidenziati.

## Vincoli su date e orari degli appelli

Un appello **non può** essere fissato di **sabato o domenica**, né in un
**giorno festivo** italiano. Le festività a data fissa sono elencate in
`app/Support/CalendarioFestivita.php`, mentre il **Lunedì dell'Angelo** è
calcolato ogni anno a partire dalla data della Pasqua (algoritmo di Gauss/Meeus).

L'appello deve inoltre svolgersi nella **fascia oraria 08:00–18:00**: l'ora di
inizio non può precedere le 08:00 e l'ora di fine non può superare le 18:00.

Entrambi i vincoli sono applicati **lato server** alla creazione e alla modifica,
e valgono anche per l'amministratore. Il form li anticipa lato client: la data
non valida viene segnalata appena scelta, mentre i campi orario sono limitati con
gli attributi `min`/`max`.

## Monitoraggio delle scadenze

La dashboard segnala gli insegnamenti ancora **privi di appello** in base allo
stato della finestra di inserimento della sessione:

- l'**amministratore** vede gli insegnamenti senza appello nelle sessioni con
  finestra **in scadenza** (entro 7 giorni) o **già chiusa**;
- il **docente** vede i propri insegnamenti da pianificare finché la finestra è
  aperta, in scadenza o appena chiusa.

## Finestre di inserimento e preappelli

Ogni sessione ha una o più **finestre di inserimento**, cioè i periodi in cui i
docenti possono creare, modificare ed eliminare gli appelli (l'amministratore
non è soggetto a questo vincolo). Una finestra **può aprirsi anche prima**
dell'inizio della sessione — così le date si pubblicano in anticipo — ma deve
chiudersi entro la fine della sessione.

La data di un appello deve cadere nel periodo della sessione, con un'eccezione:
i **preappelli**. L'amministratore configura un margine (default **14 giorni**,
impostabile da *Configurazione*) entro cui un appello può precedere l'inizio
della sessione, utile a far organizzare gli studenti. Gli appelli con data
antecedente l'inizio sessione sono evidenziati con il badge «preappello».

## Struttura didattica

L'elenco degli insegnamenti si può filtrare per **corso di studio**, per **anno
di frequenza** e per **nome** (ricerca testuale); i filtri si applicano da soli,
senza pulsante di conferma.

Le eliminazioni sono **a cascata**: rimuovere un corso di studio, un
insegnamento o una sessione elimina anche gli appelli collegati. I messaggi di
conferma indicano quanti appelli verranno persi, così l'operazione non è mai una
sorpresa. Rimuovere un docente da un insegnamento, invece, non tocca gli appelli
ma gli revoca l'accesso ad essi.

## Avvio in locale

Requisiti: PHP 8.3 o superiore, Composer, Node.js, MySQL.

```bash
# 1. Dipendenze
composer install
npm install && npm run build

# 2. Ambiente
cp .env.example .env
php artisan key:generate
# configurare in .env: DB_DATABASE=esami_coordinamento, DB_USERNAME, DB_PASSWORD

# 3. Database e dati di esempio (il server MySQL dev'essere già avviato)
php artisan migrate --seed

# 4. Avvio
php artisan serve
```

L'applicazione è raggiungibile su `http://127.0.0.1:8000`.

> **Nota:** MySQL deve restare attivo anche durante l'uso. Le sessioni sono
> salvate sul database (`SESSION_DRIVER=database`), quindi con il server spento
> ogni pagina risponde con un errore 500.

## Utenti di esempio (creati dal seeder)

| Ruolo          | Email                 | Password   |
|----------------|-----------------------|------------|
| Amministratore | admin@esami.test      | `password` |
| Docente        | docente1@esami.test   | `password` |
| Docente        | docente2@esami.test   | `password` |

## Import CSV della struttura didattica

Dalla sezione **Importa CSV** l'amministratore può caricare un file con le
colonne `corso`, `insegnamento`, `anno_frequenza` e, facoltativa, `docenti`
(email separate da `|`). Il separatore (virgola o punto e virgola) è rilevato
automaticamente; in caso di errori non viene importato nulla. È disponibile un
file CSV di esempio da scaricare.

## Test

```bash
php artisan test
```

La suite copre autenticazione, gestione della struttura, import CSV, appelli,
verifica dei conflitti e calendario.
