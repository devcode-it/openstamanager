# OpenSTAManager – Project Overview
> Generato automaticamente – Fase 1 (Discovery & Architecture)

## Identità del progetto
- **Nome**: OpenSTAManager
- **Licenza**: GPL-3.0
- **Autore**: DevCode s.r.l. – info@openstamanager.com
- **Homepage**: https://www.openstamanager.com/
- **Scopo**: ERP/gestionale open-source per assistenza tecnica e fatturazione elettronica (fattura PA e B2B italiana)

---

## Stack tecnologico

### Backend
| Componente | Versione | Ruolo |
|---|---|---|
| PHP | ^8.1 (target 8.3.7) | Linguaggio principale |
| Laravel Framework | ^12.0 | Routing, DI, Eloquent ORM, Queue, Mail |
| API Platform (Laravel) | ^4.1 | REST API auto-generata |
| Laravel Sanctum | ^4.0 | Autenticazione API via token |
| Illuminate (standalone) | ^12.0 | Cache, DB, Filesystem |
| Symfony (componenti) | ^7.0 | Filesystem, Finder, Translation |

### Frontend
| Componente | Ruolo |
|---|---|
| AdminLTE | UI framework (Bootstrap-based) |
| Bootstrap 4 | Layout e componenti |
| jQuery / Select2 / FullCalendar | Interattività |
| Gulp + Webpack/Vite | Build degli asset (CSS/JS) |
| mPDF / html2pdf / spipu | Generazione PDF lato server |

### Database
- **MySQL/MariaDB** – charset utf8mb4, collation utf8mb4_general_ci
- Connessione via Eloquent Capsule (Laravel) wrappata nella classe `Database`

### Auth
- Sessione PHP nativa + classe `AuthOSM`
- OAuth2: Microsoft Azure, Google, Keycloak (`league/oauth2-*`)
- Token OTP per accesso condiviso (link temporanei)
- Brute-force protection integrata

---

## Dipendenze principali (composer.json)

### Operative
- `mpdf/mpdf` – generazione PDF
- `phpmailer/phpmailer` – invio email
- `league/csv` – import/export CSV
- `endroid/qr-code` – generazione QR
- `picqer/php-barcode-generator` – barcode
- `ifsnop/mysqldump-php` – backup DB
- `guzzlehttp/guzzle` – HTTP client
- `digitick/sepa-xml` – file SEPA/RiBa
- `davidepastore/codice-fiscale` – validazione CF italiano
- `ezyang/htmlpurifier` – sanitizzazione HTML
- `willdurand/geocoder` – geocoding

### Dev
- `phpunit/phpunit ^12`, `rector/rector`, `laravel/pint`, `friendsofphp/php-cs-fixer`

---

## Entry point principali
| File | Scopo |
|---|---|
| `index.php` | Login, redirect post-autenticazione, bootstrap |
| `controller.php` | Rendering dei moduli (lista record) |
| `editor.php` | Form di dettaglio/modifica record |
| `ajax.php` / `ajax_*.php` | Endpoint AJAX (azioni, ricerche, select, dataload) |
| `add.php` | Creazione nuovo record |
| `api/index.php` | API REST (Laravel + API Platform) |
| `core.php` | Bootstrap globale (sessione, DB, translator, autoload) |
| `artisan` | CLI Laravel |
