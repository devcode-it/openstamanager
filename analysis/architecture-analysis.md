# OpenSTAManager – Architecture Analysis
> Generato automaticamente – Fase 1 (Discovery & Architecture)

## Pattern architetturale generale

OpenSTAManager adotta un'architettura **ibrida** che combina:

1. **Legacy MVC PHP** – Il nucleo storico: `core.php` inizializza la sessione, il database e il translator; `controller.php` / `editor.php` fungono da dispatcher front-controller che includono dinamicamente i file PHP dei moduli.
2. **Laravel moderno** – Laravel 12 è usato come layer di infrastruttura (Eloquent ORM, Routing, Queue, Mail, API Platform). Il file `bootstrap/app.php` crea l'Application Laravel con routing in `routes/`.
3. **Moduli plug-in** – La logica di business è organizzata in **moduli** (`modules/`) e **plugin** (`plugins/`), ognuno con la propria struttura `src/`, `custom/src/`, viste PHP e risorse.

---

## Mappa dei componenti principali

```
┌─────────────────────────────────────────────────────────────┐
│                      Browser / Client                       │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP
          ┌────────────▼────────────────┐
          │      Entry Points           │
          │  index.php / controller.php │
          │  editor.php / ajax*.php     │
          │  api/index.php              │
          └────────────┬────────────────┘
                       │
          ┌────────────▼────────────────┐
          │         core.php            │  Bootstrap globale
          │  - Sessione PHP             │
          │  - App::getConfig()         │
          │  - Database (singleton)     │
          │  - Translator               │
          │  - AuthOSM                  │
          └────────────┬────────────────┘
                       │
       ┌───────────────┼───────────────────┐
       │               │                   │
┌──────▼──────┐ ┌──────▼──────┐ ┌─────────▼────────┐
│  src/App.php │ │src/Modules  │ │  src/Database.php │
│  Config      │ │ .php        │ │  (Eloquent        │
│  Assets      │ │ Module list │ │   Capsule)        │
│  File paths  │ │ Hierarchy   │ │                   │
└──────┬───────┘ └──────┬──────┘ └─────────┬─────────┘
       │                │                   │
       │         ┌──────▼──────┐     ┌──────▼──────┐
       │         │ modules/    │     │ src/Models/ │
       │         │ ~70 moduli  │     │ Eloquent    │
       │         │ plugins/    │     │ Models      │
       │         │ ~30 plugin  │     └─────────────┘
       │         └─────────────┘
       │
┌──────▼───────────────────────────────┐
│           assets/ (frontend)         │
│  Bootstrap, AdminLTE, jQuery,        │
│  FullCalendar, Chart.js – build Gulp │
└──────────────────────────────────────┘
```

---

## Struttura directory – ruolo di ogni cartella

| Directory | Contenuto |
|---|---|
| `src/` | Core PHP: App, Auth, Database, Modules, Models, Controllers Laravel, API, Util |
| `modules/` | ~70 moduli applicativi (ogni modulo = directory autonoma con PHP + src/) |
| `plugins/` | ~30 plugin (logica add-on attaccata ai moduli) |
| `templates/` | Template di stampa PDF (mPDF/html2pdf) |
| `include/` | Partials HTML: header/footer, form comuni, init scripts |
| `config/` | Configurazioni Laravel (database, mail, cache, osm, sanctum…) |
| `routes/` | Routing Laravel (web.php, api.php, console.php) |
| `bootstrap/` | Bootstrap Laravel (app.php, providers.php) |
| `assets/src` | Sorgenti frontend (SCSS, JS) da compilare con Gulp |
| `assets/dist` | Asset compilati serviti al browser |
| `locale/` | File .po/.mo per internazionalizzazione (it_IT, en_GB, de_DE) |
| `update/` | Script di migrazione SQL/PHP per ogni versione |
| `lib/` | Funzioni helper globali (functions.php, common.php, util.php) |
| `files/` | Allegati e file upload organizzati per modulo |
| `vendor/` | Dipendenze Composer |
| `node_modules/` | Dipendenze npm/yarn |
| `docker/` | Configurazione Docker per sviluppo |
| `logs/` | Log applicativi (error.log, cron-*.log, setup.log) |
| `storage/` | Storage Laravel (framework cache, logs) |

---

## Sistema dei Moduli

Ogni modulo in `modules/<nome>/` ha questa struttura tipica:

```
modules/fatture/
├── src/                  # Classi PHP del modulo (namespace Modules\Fatture\)
├── custom/src/           # Override personalizzati (non sovrascritti dagli aggiornamenti)
├── init.php              # Inizializzazione modulo
├── modutil.php           # Utility specifiche
├── edit.php              # Vista di dettaglio/modifica
├── actions.php           # Azioni POST del modulo
└── bulk.php              # Operazioni bulk
```

Il sistema `custom/` è il meccanismo ufficiale di personalizzazione: i file in `custom/src/` e `custom/` sovrascrivono quelli standard senza toccare il codice base, sopravvivendo agli aggiornamenti.

---

## Flusso di una richiesta tipica

```
1. Browser → controller.php?id_module=X&id_record=Y
2. core.php  → inizializza sessione, DB, auth, translator
3. AuthOSM::check() → verifica login
4. Permissions::check() → verifica permessi modulo
5. Modules::getCurrent() → carica metadati modulo da DB
6. include modules/<nome>/init.php → inizializza modulo
7. App::load('edit.php', ...) → cerca custom/ poi standard
8. Output HTML con include top.php / bottom.php
```

---

## Sistema di autenticazione

- **Classe**: `AuthOSM` (src/AuthOSM.php)
- **Sessione PHP** nativa per la sessione utente
- **OAuth2** via `zz_oauth2` table: supporto Microsoft, Google, Keycloak
- **Token OTP**: accesso temporaneo per link condivisi (`zz_tokens`)
- **Brute-force**: lockout automatico dopo N tentativi falliti
- **Intended URL**: redirect post-login all'URL originale richiesto

---

## API REST

- **Path**: `api/index.php` + `routes/api.php`
- **Framework**: Laravel + API Platform ^4.1
- **Auth**: Laravel Sanctum (Bearer token)
- **Risorse**: auto-generate da `src/Models/ApiResource.php`

---

## Internazionalizzazione

- File `.po`/`.mo` in `locale/`
- Lingue: `it_IT` (primaria), `en_GB`, `de_DE`
- Classe: `src/Translator.php` + `symfony/translation`
- Helper globale: `tr()` (alias di traduzione)

---

## Pattern "Custom Override"

Il sistema riconosce percorsi con `|custom|` e cerca prima `custom/` poi il file standard:

```php
// App::filepath('include|custom|/common/', 'form.php')
// cerca: include/common/custom/form.php  → se esiste usa questo
// altrimenti: include/common/form.php
```

Stesso meccanismo per i moduli: `modules/<nome>/custom/src/` sovrascrive `modules/<nome>/src/`.
