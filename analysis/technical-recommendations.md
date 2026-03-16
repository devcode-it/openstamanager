# Raccomandazioni Tecniche – OpenSTAManager
> Fase 3 – Documentation & Recommendations
> Priorità: Alta / Media / Bassa

---

## ALTA PRIORITÀ

### 1. Standardizzare lo stile di query verso Eloquent/Fluent Builder

**Problema**: convivono tre stili di accesso al DB. Le query raw con interpolazione
via `prepare()` sono sicure ma difficili da testare, refactorare e leggere.

**Raccomandazione**: nei file `src/` dei moduli, migrare sistematicamente
da `$dbo->fetchArray('SELECT ... WHERE id='.prepare($id))` al Fluent Builder
o agli Eloquent Model. Priorità ai moduli più complessi (fatture, interventi).

```php
// Da:
$dbo->fetchOne('SELECT * FROM co_documenti WHERE id='.prepare($id_record));

// A:
database()->table('co_documenti')->where('id', $id_record)->first();
// Oppure:
Fattura::find($id_record);
```

---

### 2. Estrarre la logica SQL da init.php nei Model Eloquent

**Problema**: `modules/fatture/init.php` contiene query SQL con 15+ JOIN inline.
Questo codice è impossibile da testare con PHPUnit e difficile da riusare.

**Raccomandazione**: spostare le query complesse in scope Eloquent o metodi statici
del Model corrispondente.

```php
// Da (in init.php):
$record = $dbo->fetchOne('SELECT co_documenti.*, co_tipidocumento_lang.title AS ... FROM co_documenti JOIN ...');

// A (in Fattura.php):
public static function withFullDetails(int $id): ?array
{
    return static::with(['tipo', 'stato', 'pagamento', 'causale'])->find($id)?->toArray();
}
```

---

### 3. Aggiungere test unitari per i componenti core

**Problema**: phpunit è già in `require-dev` ma la copertura è minima.
I componenti core (Database, AuthOSM, HTMLBuilder, Update) non hanno test automatici.

**Raccomandazione**: iniziare con test per:
- `Update::isVersion()`, `Update::getDatabaseVersion()`
- `AuthOSM::hashPassword()`, `AuthOSM::validateOTP()`
- `HTMLBuilder::decode()`, `HTMLBuilder::elaborate()`
- `Database::sync()`, `Database::attach()`

```bash
php artisan test
# oppure: composer unit-tests
```

---

## MEDIA PRIORITÀ

### 4. Gestire gli errori JSON silenti in HTMLBuilder

**Problema**: se un tag `{[ ]}` contiene JSON malformato, `json_decode()` restituisce
`null` silenziosamente e il campo non viene renderizzato. Non c'è log dell'errore.

**Raccomandazione**: aggiungere logging esplicito in `HTMLBuilder::decode()`:
```php
$json = (array) json_decode($string, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    logger_osm()->warning('HTMLBuilder: JSON malformato', ['string' => $string]);
}
```

---

### 5. Uniformare la gestione dei permessi modulo

**Problema**: la verifica dei permessi è sparsa tra `AuthOSM`, `Modules`,
`Permissions` e i singoli file dei moduli. Non esiste un unico punto d'ingresso.

**Raccomandazione**: centralizzare in un middleware Laravel o in un metodo
`Permissions::assertCan($action, $module)` usato sistematicamente.

---

### 6. Revisione del sistema Settings per impostazioni utente

**Problema**: le impostazioni personalizzate per utente sono salvate come JSON
in `zz_users.options` senza una struttura validata.

**Raccomandazione**: introdurre una tabella `zz_user_settings` dedicata
(id, id_utente, id_setting, valore) per query strutturate e validazione.

---

### 7. Documentare il contratto delle tabelle `_lang`

**Problema**: la regola "i campi traducibili vanno in `_lang`" non è documentata
nel codice e causa errori frequenti nei contributi esterni.

**Raccomandazione**: aggiungere un commento PHPDoc standard ai Model con campi
traducibili, e un test che verifichi la presenza della riga `_lang` dopo un INSERT.

---

## BASSA PRIORITÀ

### 8. Sostituire ob_start() con un template engine leggero

**Problema**: il rendering dei template usa `ob_start()` + `include` PHP nativo.
Difficile da debuggare, nessuna separazione vera tra logica e presentazione.

**Considerazione**: data la profondità dell'architettura legacy, una migrazione
a Blade (già disponibile con Laravel) richiederebbe un refactoring massiccio.
Preferibile come obiettivo di lungo periodo, modulo per modulo.

---

### 9. Aggiungere rate limiting alle API

**Problema**: le API REST (API Platform + Sanctum) non hanno rate limiting configurato.

**Raccomandazione**: aggiungere throttle middleware in `routes/api.php`:
```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () { ... });
```

---

### 10. Consolidare i log applicativi

**Problema**: i log sono in due posti distinti (`logs/error.log` legacy e `storage/logs/` Laravel).

**Raccomandazione**: configurare Monolog in `config/logging.php` per
unificare tutti i log in `storage/logs/` con rotazione giornaliera.

---

## Riepilogo priorità

| # | Titolo | Priorità | Impatto | Sforzo |
|---|---|---|---|---|
| 1 | Standardizzare query verso Eloquent | Alta | Alto | Alto |
| 2 | Estrarre SQL da init.php nei Model | Alta | Alto | Medio |
| 3 | Test unitari componenti core | Alta | Alto | Medio |
| 4 | Gestire errori JSON in HTMLBuilder | Media | Medio | Basso |
| 5 | Uniformare verifica permessi | Media | Medio | Medio |
| 6 | Tabella dedicata user settings | Media | Basso | Medio |
| 7 | Documentare contratto `_lang` | Media | Medio | Basso |
| 8 | Template engine (Blade) | Bassa | Alto | Molto alto |
| 9 | Rate limiting API | Bassa | Medio | Basso |
| 10 | Consolidare log | Bassa | Basso | Basso |
