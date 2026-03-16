# Guida Onboarding Sviluppatori – OpenSTAManager
> Fase 3 – Documentation & Recommendations

## Setup ambiente di sviluppo

### Prerequisiti
- PHP 8.1+ (target 8.3.7)
- MySQL 5.7+ / MariaDB 10.3+
- Node.js + Yarn
- Composer
- WAMP / XAMPP / Laravel Herd / Docker

### Installazione
```bash
composer install
cp .env.example .env
php artisan key:generate
# Configura config.inc.php (db_host, db_username, db_password, db_name)
yarn install
yarn build        # oppure: npx gulp
```

### File di configurazione principale
`config.inc.php` (root) – sovrascrive i default di `config.example.php`:
```php
$db_host     = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name     = 'openstamanager';
$lang        = 'it_IT';
$debug       = false;
```

---

## Struttura mentale da acquisire subito

### 1. Due mondi coesistono
| Layer | Dove | Usato per |
|---|---|---|
| Legacy PHP front-controller | `controller.php`, `editor.php`, `modules/` | UI, form, azioni record |
| Laravel moderno | `src/Controllers/`, `routes/`, `bootstrap/` | API REST, queue, mail |

### 2. Il ciclo di vita di una pagina
```
URL: /controller.php?id_module=12&id_record=45
  → core.php         (sessione, DB, auth, translator)
  → AuthOSM::check() (se non loggato → redirect login)
  → Modules::getCurrent()
  → include modules/<nome>/init.php   (carica record, variabili)
  → include modules/<nome>/edit.php   (HTML con tag {[ ]})
  → translateTemplate()               (HTMLBuilder converte i tag)
  → include top.php + bottom.php      (layout)
```

### 3. Come leggere un modulo
Ogni modulo in `modules/<nome>/` ha questi file chiave:
- `init.php` → query SQL che carica il record e popola `$record`
- `edit.php` → HTML del form con tag `{[ "type": "text", ... ]}`
- `actions.php` → `switch($op)` per le azioni POST (save, delete, ecc.)
- `src/` → classi Eloquent specifiche del modulo

---

## Creare o modificare un modulo

### Aggiungere un campo a un modulo esistente

**Passo 1** – Aggiungere la colonna al DB (script di migrazione):
```sql
-- update/2_x_y.sql
ALTER TABLE `co_documenti` ADD COLUMN `mio_campo` VARCHAR(255) NULL;
```

**Passo 2** – Mostrare il campo nel form (`edit.php` del modulo):
```php
{[ "type": "text", "name": "mio_campo", "label": "Mio Campo", "value": "$mio_campo$" ]}
```

**Passo 3** – Salvare il valore (`actions.php`):
```php
case 'save':
    $dbo->update('co_documenti', ['mio_campo' => post('mio_campo')], ['id' => $id_record]);
    break;
```

### Override personalizzato (custom/)
Per personalizzare senza rompere gli aggiornamenti:
```
modules/fatture/custom/edit.php        → sovrascrive edit.php
modules/fatture/custom/src/Fattura.php → sovrascrive src/Fattura.php
```
Il sistema `App::filepath()` cerca sempre prima `custom/` poi il file standard.

---

## Sistema di traduzione – come usarlo correttamente

### Regola fondamentale: tabelle `_lang`
I campi traducibili NON vanno nella tabella principale ma nella tabella `_lang`:

```php
// SBAGLIATO – non funziona per campi traducibili
$dbo->insert('zz_modules', ['title' => 'Il mio modulo']);

// CORRETTO – doppia scrittura obbligatoria
$id = $dbo->insert('zz_modules', ['name' => 'il_mio_modulo', 'enabled' => 1]);
$dbo->insert('zz_modules_lang', [
    'id_record' => $id,
    'id_lang'   => Models\Locale::getDefault()->id,
    'title'     => 'Il mio modulo',
]);
```

### Recuperare la traduzione nelle query raw
```php
LEFT JOIN `co_statidocumento_lang` ON (
    `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id`
    AND `co_statidocumento_lang`.`id_lang` = ' . prepare(Models\Locale::getDefault()->id) . '
)
```

### Tramite RecordTrait (nei Model Eloquent)
```php
$model->setTranslation('title', 'Nuovo titolo', $id_lang);
$model->getTranslation('title', $id_lang);
```

---

## Lavorare con il Database

### Tre stili – usa il più moderno disponibile

```php
// Stile 1 – legacy (ancora diffuso nei moduli):
$record = $dbo->fetchOne('SELECT * FROM co_documenti WHERE id=' . prepare($id));

// Stile 2 – Fluent Builder (preferibile per nuove query):
$record = database()->table('co_documenti')->where('id', $id)->first();

// Stile 3 – Eloquent Model (preferibile per nuove classi src/):
$fattura = \Modules\Fatture\Fattura::with('tipo', 'stato')->find($id);
```

### Transazioni
```php
$dbo->beginTransaction();
try {
    $dbo->insert(...);
    $dbo->update(...);
    $dbo->commitTransaction();
} catch (Exception $e) {
    $dbo->rollbackTransaction();
    throw $e;
}
```

### Helper `sync()` per relazioni M:N
```php
// Sincronizza la lista dei permessi – aggiunge mancanti, rimuove extra
$dbo->sync('zz_permissions', ['idmodule' => $id_module], ['idgruppo' => [1, 2, 3]]);
```

---

## Sistema di aggiornamento

### Come funziona
- Ogni release ha file `update/X_Y_Z.sql` e/o `update/X_Y_Z.php`
- La tabella `updates` traccia cosa è stato eseguito (`done=1` = completato)
- Al login, se ci sono aggiornamenti pendenti → redirect alla procedura di aggiornamento
- I moduli hanno la loro cartella `modules/<nome>/update/` con script propri

### Aggiungere uno script di migrazione
```
update/2_5_1.sql    → query SQL (ALTER TABLE, INSERT, UPDATE, ecc.)
update/2_5_1.php    → logica PHP (opzionale, eseguita dopo il .sql)
```
Naming: underscore al posto dei punti (`2.5.1` → `2_5_1`).

### Normalizzazione automatica post-aggiornamento
Dopo ogni aggiornamento, `Update::normalizeDatabase()` converte automaticamente:
- charset → utf8mb4
- collation → utf8mb4_general_ci
- engine → InnoDB

---

## HTMLBuilder – scrivere form correttamente

### Tag di input `{[ ]}`
```php
// Testo semplice
{[ "type": "text", "name": "nome", "label": "Nome", "required": 1, "value": "$nome$" ]}

// Select da query
{[ "type": "select", "name": "idanagrafica", "label": "Cliente",
   "values": "query=SELECT `idanagrafica` AS id, `ragione_sociale` AS descrizione FROM `an_anagrafiche`",
   "value": "$idanagrafica$" ]}

// Data
{[ "type": "date", "name": "data", "label": "Data", "value": "$data$" ]}

// Checkbox
{[ "type": "checkbox", "name": "attivo", "label": "Attivo",
   "placeholder": "Sì", "value": "$attivo$" ]}
```

### Tag widget `{( )}`
```php
// Allegati
{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

// Campi personalizzati configurabili
{( "name": "custom_fields", "id_module": "$id_module$", "id_record": "$id_record$" )}
```

### Variabili `$nome$`
Vengono sostituite con i valori di `$record` caricato in `init.php`.
Se la variabile non esiste in `$record`, viene lasciata vuota silenziosamente.

---

## Impostazioni di sistema

```php
// Leggere un'impostazione
$valore = setting('Nome Impostazione');

// Scrivere un'impostazione
Settings::setValue('Nome Impostazione', $nuovo_valore);
```

Le impostazioni sono in tabella `zz_settings` con campi:
`nome`, `valore`, `tipo` (text, boolean, integer, list[a,b,c], query=..., ckeditor), `sezione`, `editable`.

Le impostazioni possono essere personalizzate per utente via `zz_users.options` (JSON).

---

## Debug e troubleshooting

### Abilitare debug mode
In `config.inc.php`:
```php
$debug = true;
```
Con debug attivo, `filp/whoops` mostra stack trace completo a schermo.

### Log
- `logs/error.log` – errori PHP
- `logs/cron-*.log` – log dei job schedulati
- `storage/logs/` – log Laravel

### Query lente / problemi DB
```php
// Stampare la query generata da Eloquent
$query = database()->table('co_documenti')->where('id', $id)->toSql();
dd($query);
```

### Errore "modulo non trovato"
Il global scope `enabled=true` su `Module` filtra i moduli disabilitati.
Per vederli tutti: `Module::withoutGlobalScope('enabled')->get()`

---

## Checklist per una nuova feature

- [ ] Script SQL in `update/X_Y_Z.sql` per le modifiche al DB
- [ ] Doppia scrittura su `_lang` se si aggiunge un campo traducibile
- [ ] Modifiche UI in `custom/` se si tratta di personalizzazione
- [ ] `actions.php` aggiornato per gestire il salvataggio
- [ ] Test manuale del form e dell'azione POST
- [ ] Verifica che l'aggiornamento sia in `updates` dopo l'applicazione
