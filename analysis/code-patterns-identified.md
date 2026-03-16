# Pattern del Codice – Analisi Trasversale
> Fase 2 – Component Analysis

## Pattern rilevati nel codebase

---

### 1. Pattern "Dual Write" per campi tradotti
Ogni operazione su un'entità con campi traducibili richiede
una scrittura doppia: tabella principale + tabella `_lang`.

```php
// INSERT nuovo modulo → SEMPRE doppia scrittura
$dbo->insert('zz_modules', ['name' => 'MioModulo', 'enabled' => 1]);
$dbo->insert('zz_modules_lang', [
    'id_record' => $id,
    'id_lang'   => $id_lang,
    'title'     => 'Mio Modulo'
]);
```

Tabelle principali con corrispondente `_lang`:
`zz_modules`, `co_statidocumento`, `co_tipidocumento`, `co_pagamenti`,
`dt_causalet`, `zz_segments`, `zz_views`, e molte altre.

---

### 2. Pattern "Custom Override" (personalizzazioni sicure)
Il meccanismo `|custom|` in `App::filepath()` cerca prima `custom/` poi il file standard.
Permette override senza modificare il codice base (aggiornamenti sicuri).

```
modules/fatture/edit.php          → file standard
modules/fatture/custom/edit.php   → override personalizzato (ha precedenza)

modules/fatture/src/Fattura.php          → classe standard
modules/fatture/custom/src/Fattura.php   → override personalizzato
```

---

### 3. Pattern "Superselect" per filtri contestuali
Array `$superselect` passato alle select per filtrare dinamicamente le opzioni:

```php
$superselect['dir'] = 'entrata'; // filtra tipi documento per direzione
$superselect['idtipodocumento'] = $record['idtipodocumento'];
// HTMLBuilder SelectHandler usa questi valori come WHERE aggiuntivi
```

---

### 4. Pattern "Init + Edit + Actions" per ogni modulo
Ogni modulo segue questa struttura di file obbligatori:

```
init.php      → carica il record dal DB, inizializza variabili
edit.php      → HTML del form (usa tag HTMLBuilder)
actions.php   → gestisce le operazioni POST (op=save, op=delete, ecc.)
modutil.php   → funzioni utility specifiche del modulo
```

---

### 5. Pattern "op" per le azioni
Tutte le azioni POST usano il parametro `op`:

```php
$op = post('op'); // o filter('op')

switch ($op) {
    case 'save':    // salva record
    case 'delete':  // elimina record
    case 'upload':  // carica file
    // ...
}
```

---

### 6. Helper globali come alias
Il codebase usa molti helper PHP globali per accesso rapido a oggetti singleton:

```php
database()    // → Database::getInstance()
auth_osm()    // → AuthOSM::getInstance()
setting($key) // → Settings::get($key)
tr($string)   // → traduzione
filter($key)  // → input GET sanitizzato
post($key)    // → input POST sanitizzato
prepare($val) // → PDO::quote() per query
flash()       // → App::flash() per messaggi utente
```

---

### 7. Pattern Documenti – Direzione entrata/uscita
I moduli `Fatture di vendita` e `Fatture di acquisto` condividono la stessa
tabella `co_documenti` e la stessa logica, differenziati da `co_tipidocumento.dir`:

```php
if ($module->name == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}
```
Stesso pattern per DDT, Ordini, Preventivi.

---

### 8. Pattern Riferimenti Incrociati
I documenti sono collegati tra loro tramite campi `ref_documento`:
- Fattura → Note di credito (`reversed=1`, `ref_documento`)
- Fattura → Autofattura (`id_autofattura`)
- DDT → Fattura
- Ordine → DDT → Fattura

---

## Problemi tecnici identificati

### 1. Mixed query styles (debito tecnico)
Convivono tre stili di query: raw PDO, API imperativa `$dbo->fetchArray()`,
e Eloquent Model. Non c'è una linea guida unica.

### 2. Query SQL inline nei template (init.php)
Query SQL complesse con molte JOIN nei file `init.php` dei moduli.
Difficile testare e riutilizzare questa logica.

### 3. SelectHandler con query SQL nel template
```php
{[ "type": "select", "values": "query=SELECT id, nome FROM an_anagrafiche WHERE ..." ]}
```
Il parametro `values` può contenere SQL arbitrario proveniente dal file di template.
Non accetta input utente quindi sicuro, ma difficile da manutenere.

### 4. Global scope `enabled` su Module
Il global scope filtra sempre i moduli disabilitati.
Per accedere a moduli disabilitati serve `withoutGlobalScope('enabled')`.

### 5. ob_start() per template rendering
Il rendering usa output buffering PHP nativo invece di un template engine.
Rende il debug dei template più complesso.
