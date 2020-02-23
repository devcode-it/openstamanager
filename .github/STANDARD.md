# Standard del codice

Lo standard prevede l'utilizzo di nomi in italiano per la maggior parte dei contenuti, esclusi i sistemi di gestione interna del gestionale (tabelle `zz_*` e codici particolarmente rilevanti).

I nomi delle variabili devono seguire uno standard comune, che prevede la sostituzione degli spazi con `_` (*underscore*) e la rimozione delle lettere accentate a favore di quelle semplici.

Le variabili devono possedere nomi completi e chiari.
Esempio:
 - Partita IVA -> `partita_iva` nel database, `$partita_iva` in PHP

## Database

Gli identificatori devono iniziare per `id_*` e i flag per `is_*`.
E' fondamentale ricordarsi di impostare correttamente le **FOREIGN KEYS** delle relative tabelle.

Ci sono inoltre alcuni campi utilizzati in modo riccorrente all'interno del gestionale:
 - `default boolean NOT NULL DEFAULT 0` per i valori di default, non cancellabili e con modificabilità limitata
 - `predefined boolean NOT NULL DEFAULT 0` per i valori predefiniti in selezioni o gruppi
 - `visible boolean NOT NULL DEFAULT 1` per nascondere gli elementi
 - `deleted_at timestamp NULL DEFAULT NULL,` per segnare un elemento come eliminato

Per tabelle non presenti all'interno della lista ufficiale di OpenSTAManager (file **update/tables.php**), è necessario inoltre provvedere all'aggiunta dei seguenti campi:
 - `updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
 - `created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP`

## Note

Malgrado una buona parte del codice ufficiale non segua completamente queste buone pratiche, è consigliato l'implementazione di queste linee guida per nuove funzioni e strutture mentre il sistema di base viene lentamente standardizzato.
