# OpenSTAManager – Guida Completa al Codebase
> Fase 3 – Documento di riferimento unificato

## Identità del progetto
ERP/gestionale open-source PHP per assistenza tecnica e fatturazione elettronica italiana.
Licenza GPL-3.0 | DevCode s.r.l. | https://www.openstamanager.com/

---

## Stack tecnologico (sintesi)
- **Runtime**: PHP 8.3 + MySQL/MariaDB
- **Framework**: Laravel 12 (infrastruttura) + legacy front-controller PHP (UI)
- **ORM**: Eloquent (Laravel) + API imperativa custom (`Database` singleton)
- **API REST**: API Platform ^4.1 + Laravel Sanctum
- **Frontend**: AdminLTE + Bootstrap 4 + jQuery, build con Gulp/Vite
- **Auth**: Sessione PHP + OAuth2 (Azure/Google/Keycloak) + OTP + brute-force protection
- **PDF**: mPDF + html2pdf/spipu
- **i18n**: Symfony Translation + file .po/.mo (it_IT, en_GB, de_DE)

---

## Mappa dell'applicazione

### Entry point principali
| File | Scopo |
|---|---|
| `index.php` | Login, logout, redirect post-auth |
| `controller.php` | Lista record di un modulo |
| `editor.php` | Form dettaglio/modifica record |
| `ajax.php` / `ajax_*.php` | Azioni AJAX, ricerche, select dinamiche |
| `add.php` | Creazione nuovo record |
| `api/index.php` | API REST (Laravel + API Platform) |
| `core.php` | Bootstrap globale (sessione, DB, auth, i18n) |

### Directory principali
| Directory | Contenuto |
|---|---|
| `src/` | Classi core: App, Database, AuthOSM, Modules, Models, HTMLBuilder, API |
| `modules/` | ~70 moduli applicativi |
| `plugins/` | ~30 plugin add-on |
| `templates/` | Template stampa PDF |
| `update/` | Script migrazione SQL/PHP per ogni versione |
| `lib/` | Helper globali (functions.php, common.php, util.php) |
| `config/` | Configurazioni Laravel |
| `routes/` | Routing Laravel (web, api, console) |
| `locale/` | File traduzioni .po/.mo |
| `assets/` | Frontend (src → compilato in dist) |

---

## Componenti core – riferimento rapido

### Database (`src/Database.php`)
Singleton. Wrappa Eloquent Capsule. Espone tre livelli:
1. API imperativa (`fetchArray`, `fetchOne`, `select`, `insert`, `update`, `delete`)
2. Fluent Builder (`database()->table('...')`)
3. Eloquent Models (nei `src/` dei moduli)

Helper globale: `database()` = `Database::getInstance()`

### AuthOSM (`src/AuthOSM.php`)
Singleton. Gestisce: login classico, OAuth2, OTP, token diretto, API token.
Protezioni: brute-force (3 tentativi/180s), session token univoco in DB,
single session control opzionale, intended URL post-login.

Helper globale: `auth_osm()` = `AuthOSM::getInstance()`

### HTMLBuilder (`src/HTMLBuilder/HTMLBuilder.php`)
Converte tag DSL in HTML. Due tipi:
- `{[ "type": "...", ... ]}` → input HTML (text, select, date, checkbox, ckeditor…)
- `{( "name": "...", ... )}` → widget compositi (filelist, custom_fields, widgets…)

Chiamato da `translateTemplate()` (lib/functions.php) al termine di ogni template.

### Settings (`src/Settings.php`)
Accesso alle impostazioni in `zz_settings`.
Helper globale: `setting('Nome')` = `Settings::getValue('Nome')`

Tipi supportati: `text`, `boolean`, `integer`, `decimal`, `textarea`,
`date`, `timestamp`, `list[a,b,c]`, `multiple[a,b,c]`, `query=...`, `ckeditor`

### Update (`src/Update.php`)
Gestisce le migrazioni DB. Tabella `updates` traccia lo stato.
Script in `update/X_Y_Z.sql` + `update/X_Y_Z.php`.
Post-aggiornamento: normalizza charset/collation/engine automaticamente.

---

## Schema tabelle principali (DB)

### Tabelle sistema (`zz_*`)
| Tabella | Contenuto |
|---|---|
| `zz_modules` / `zz_modules_lang` | Moduli installati |
| `zz_plugins` | Plugin |
| `zz_users` | Utenti (username, password bcrypt, session_token, idgruppo) |
| `zz_groups` | Gruppi/ruoli (nome, id_module_start) |
| `zz_permissions` | Permessi modulo per gruppo (permessi: r/rw/-) |
| `zz_settings` / `zz_settings_lang` | Impostazioni di sistema |
| `zz_logs` | Log accessi (ip, username, stato, created_at) |
| `zz_tokens` | Token API Sanctum |
| `zz_otp_tokens` | Token OTP/condivisi |
| `zz_oauth2` | Configurazioni provider OAuth2 |
| `zz_views` / `zz_views_lang` | Colonne visibili nelle liste moduli |
| `zz_segments` | Segmenti documenti (è_fiscale) |
| `zz_operations` | Log operazioni utenti |
| `zz_fields` / `zz_field_record` | Custom fields configurabili |
| `zz_widgets` | Widget dashboard moduli |
| `zz_cache` | Cache applicativa |
| `updates` | Stato migrazioni DB |

### Tabelle applicative principali
| Tabella | Modulo |
|---|---|
| `an_anagrafiche` | Anagrafiche (clienti/fornitori) |
| `co_documenti` | Fatture, DDT, Ordini, Preventivi, Contratti |
| `co_righe_documenti` | Righe dei documenti |
| `co_tipidocumento` | Tipi documento (dir: entrata/uscita) |
| `co_statidocumento` | Stati documento |
| `co_pagamenti` | Modalità di pagamento |
| `in_interventi` | Interventi di assistenza |
| `in_righe_interventi` | Righe interventi |
| `mg_articoli` | Articoli/prodotti |
| `mg_movimenti` | Movimenti di magazzino |

### Pattern tabelle `_lang`
Ogni tabella con campi traducibili ha una corrispondente `_lang`:
```
tabella:      id | name | ... (campi non traducibili)
tabella_lang: id | id_record | id_lang | title | ... (campi traducibili)
```
I campi `title`, `descrizione`, `help`, `filename`, `meta_title` sono tipicamente in `_lang`.

---

## Pattern di sviluppo – riferimento rapido

### Pattern "op" (azioni POST)
```php
// In actions.php di ogni modulo:
$op = post('op');
switch ($op) {
    case 'save':   /* salva */ break;
    case 'delete': /* elimina */ break;
    case 'upload': /* carica file */ break;
}
```

### Pattern "dir" (documenti bidirezionali)
```php
// Fatture/DDT/Ordini usano la stessa tabella, dir discrimina:
if ($module->name == 'Fatture di vendita') { $dir = 'entrata'; }
else { $dir = 'uscita'; }
```

### Pattern "custom/" (override personalizzati)
```php
// App::filepath cerca prima custom/ poi il file standard:
App::filepath('modules/fatture|custom|/', 'edit.php')
// → cerca: modules/fatture/custom/edit.php, poi modules/fatture/edit.php
```

### Pattern "superselect" (filtri contestuali per select)
```php
// In init.php – popola filtri per le select del form:
$superselect['dir'] = $dir;
$superselect['idtipodocumento'] = $record['idtipodocumento'];
```

### Pattern "Dual Write" (campi traducibili)
```php
// Ogni INSERT su entità con campi traducibili richiede doppia scrittura:
$id = $dbo->insert('zz_modules', ['name' => 'modulo', 'enabled' => 1]);
$dbo->insert('zz_modules_lang', ['id_record' => $id, 'id_lang' => $id_lang, 'title' => 'Modulo']);
```

---

## Helper globali – reference sheet

| Helper | Equivalente | Scopo |
|---|---|---|
| `database()` | `Database::getInstance()` | Accesso DB |
| `auth_osm()` | `AuthOSM::getInstance()` | Auth |
| `setting('Nome')` | `Settings::getValue('Nome')` | Impostazioni |
| `tr('stringa')` | `Translator::translate()` | Traduzione |
| `filter('key')` | input GET sanitizzato | Input |
| `post('key')` | input POST sanitizzato | Input |
| `prepare($val)` | `PDO::quote($val)` | Escape SQL |
| `flash()` | `App::flash()` | Messaggi UI |
| `base_dir()` | `App::$docroot` | Path root |
| `base_path_osm()` | `App::$rootdir` | URL base |

---

## Dove cercare le cose

| Se cerchi... | Guarda in... |
|---|---|
| Logica di business di un modulo | `modules/<nome>/src/` |
| Form/UI di un modulo | `modules/<nome>/edit.php` |
| Azioni POST di un modulo | `modules/<nome>/actions.php` |
| Query di caricamento record | `modules/<nome>/init.php` |
| Template stampa PDF | `templates/<nome>/` |
| Configurazioni sistema | `modules/impostazioni/` |
| Script migrazioni DB | `update/*.sql` e `update/*.php` |
| Classi core condivise | `src/` |
| Helper funzionali globali | `lib/functions.php`, `lib/common.php` |
| Stili/script frontend | `assets/src/` (compilati in `assets/dist/`) |
