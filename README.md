# Sistema Web per il Coordinamento delle Date di Esame

Applicazione web per la gestione e il coordinamento degli appelli d'esame di un
dipartimento universitario. Permette all'amministratore di definire la struttura
didattica (corsi, insegnamenti, sessioni) e ai docenti di pianificare i propri
appelli, con verifica automatica dei conflitti (stesso corso/anno o stessa aula).

Progetto per il corso di **Programmazione Web**.

## Tecnologie

- **PHP 8.3** e **Laravel 13**
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
inoltre **evidenziati** nel calendario e nell'elenco, così l'amministratore può
individuarli e intervenire.

La visibilità è differenziata: il docente vede solo data, corso, anno e fascia
occupati degli appelli altrui, mentre l'amministratore vede tutti i dettagli.

## Monitoraggio delle scadenze

La dashboard segnala gli insegnamenti ancora **privi di appello** in base allo
stato della finestra di inserimento della sessione:

- l'**amministratore** vede gli insegnamenti senza appello nelle sessioni con
  finestra **in scadenza** (entro 7 giorni) o **già chiusa**;
- il **docente** vede i propri insegnamenti da pianificare finché la finestra è
  aperta, in scadenza o appena chiusa.

## Avvio in locale

Requisiti: PHP 8.3, Composer, Node.js, MySQL.

```bash
# 1. Dipendenze
composer install
npm install && npm run build

# 2. Ambiente
cp .env.example .env
php artisan key:generate
# configurare in .env: DB_DATABASE=esami_coordinamento, DB_USERNAME, DB_PASSWORD

# 3. Database e dati di esempio
php artisan migrate --seed

# 4. Avvio
php artisan serve
```

L'applicazione è raggiungibile su `http://127.0.0.1:8000`.

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
