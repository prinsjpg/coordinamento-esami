# Sistema Web per il Coordinamento delle Date di Esame

Applicazione web per la gestione e il coordinamento degli appelli d'esame di un
dipartimento universitario. Permette all'amministratore di definire la struttura
didattica (corsi, insegnamenti, sessioni) e ai docenti di pianificare i propri
appelli, con verifica automatica dei conflitti tra esami dello stesso anno.

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
- **Docente**: crea, modifica ed elimina i propri appelli, scegliendo solo tra
  gli insegnamenti a lui assegnati e all'interno delle finestre di inserimento.

## Regola dei conflitti

Due appelli sono in **conflitto** quando gli insegnamenti hanno lo **stesso anno
di frequenza**, cadono nella **stessa data** e le **fasce orarie si
sovrappongono**. La modalità di gestione è configurabile dall'amministratore:

- **Blocco**: impedisce il salvataggio dell'appello in conflitto.
- **Avviso**: consente il salvataggio segnalando il conflitto.

La visibilità è differenziata: il docente vede solo data, anno e fascia occupati
degli appelli altrui, mentre l'amministratore vede tutti i dettagli.

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
