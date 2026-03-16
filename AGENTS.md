# OpenSTAManager - Development Guide for Agents

This document provides a comprehensive overview of OpenSTAManager's architecture, conventions, and structure for AI agents working on the codebase.

---

## Versioning and Database Migrations

### Semantic Versioning

- **MINOR** (e.g., 2.11.0): New features + bugfixes - first release of a minor version
- **PATCH** (e.g., 2.11.1, 2.11.2, ...): Bugfixes only - cannot contain features
- **MAJOR** changes happen between minor versions (2.11.0 → 2.12.0)

### Migration Files Convention (`update/`)

#### Feature Files (MINOR.0 release)

- **Naming**: `2_11.sql`, `2_11.php` (for version 2.11.0 only)
- **Content**: First and only release of this minor version
  - New columns and tables
  - New indexes
  - New configuration/settings records
  - New modules/plugins
  - Schema structure changes
  - **Also includes fixes** if needed

#### Fix Files (PATCH releases)

- **Naming**: `2_11_1.sql`, `2_11_1.php` (for version 2.11.1)
  - And: `2_11_2.sql`, `2_11_2.php` (for version 2.11.2), etc.
- **Content**:
  - Data corrections only
  - Corrupted record updates
  - Query optimizations
  - PHP code bugfixes
  - **Never** add new columns, tables, or schema changes
  - **Never** introduce new features

### Fundamental Rules

> - **2.11.0** (MINOR.0) contains FEATURES + FIXES
> - **2.11.1, 2.11.2, ...** (PATCH) contain ONLY FIXES
> - **New FEATURES only appear in a new MINOR release** (2.12.0, 2.13.0, etc.)
> - **Once 2.11.1 is released, 2.11.0 is closed - no more changes to** `2_11.sql`

---

## Entry Points and Routing

### Main Entry Points

- `index.php` - Login page and main application entry point (web interface)
- `controller.php` - MVC controller dispatcher for module list views (`?id_module=X`)
- `editor.php` - **Core record editor** — renders the edit/view page for a single record
- `shared_editor.php` - Editor accessible via token-based authentication
- `core.php` - Core initialization and bootstrapping
- `api/` - REST API endpoints (returns JSON)
- `ajax.php` - Main AJAX handler
- `actions.php` - Action handler for POST requests and bulk operations
- `add.php` - Handler for new record creation
- `cron.php` - Scheduled task runner (cron jobs)

---

## Directory Structure

```
/                          ← Application root
├── api/                   ← REST API endpoints
├── assets/src/            ← Frontend source files (JS, CSS, images)
├── bootstrap/             ← Framework bootstrap files
├── config/                ← Laravel-style modular config files
├── docker/                ← Docker configuration
├── files/                 ← User-uploaded files storage
├── include/               ← Shared PHP include files
├── lib/                   ← Helper functions and libraries
├── locale/                ← Internationalisation files
├── logs/                  ← Application log files
├── modules/               ← All application modules
├── plugins/               ← Standalone root-level plugins
├── public/                ← Public web directory
├── routes/                ← Route definitions
├── src/                   ← Core application source code
├── storage/               ← Framework storage
├── templates/             ← Print templates (centralised)
├── tests/                 ← Automated tests
└── update/                ← Database migration files
```

---

## Database Structure

### Table Naming Conventions

- Module prefix: `an_` (Anagrafiche), `co_` (Contabilità), `in_` (Interventi), `mg_` (Warehouse), etc.
- Core tables: `zz_` (settings, modules, plugins, hooks, groups, etc.)
- Localized tables: suffix `_lang` (e.g., `zz_modules_lang`, `co_pagamenti_lang`)

### Core Tables (zz\_*)

| Table | Description |
|-------|-------------|
| `zz_users` | System users |
| `zz_groups` | User groups |
| `zz_modules` | Installed modules registry |
| `zz_plugins` | Installed plugins |
| `zz_settings` | Global system settings |
| `zz_views` | Custom list column views per module |
| `zz_hooks` | Hook registry |
| `zz_tasks` | Scheduled task registry |
| `zz_files` | Uploaded file metadata |
| `zz_prints` | Print template registry |

---

## ORM and Classes Architecture

### ORM: Laravel Eloquent

OpenSTAManager uses the full `laravel/framework: ^12.0` package. The Eloquent Capsule connection is bootstrapped in `src/Database.php`.

### Base Model Pattern

Models extend `Illuminate\Database\Eloquent\Model` directly and incorporate the `Common\SimpleModelTrait` trait.

```php
namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use SimpleModelTrait;
}
```

### Core Model Classes (`src/Models/`)

```
src/Models/
├── Setting.php          # System settings
├── User.php             # Users
├── Module.php           # Installed modules
├── Plugin.php           # Plugins
├── Hook.php             # Hooks for extensions
├── Group.php            # User groups
├── View.php             # Custom views
├── PrintTemplate.php    # Print templates
├── Upload.php           # Uploaded files
├── Note.php             # Notes on records
├── Cache.php            # Application cache
├── Log.php              # Generic logs
├── OperationLog.php     # CRUD operation logs
├── OAuth2.php           # OAuth2 config
└── ...
```

---

## Hooks & Scheduled Tasks (Cron)

### Hooks (`zz_hooks`)

A Hook is a PHP class that responds to an external event. Hooks are polled and executed by their corresponding scheduled Task.

```sql
INSERT INTO `zz_hooks` (`name`, `class`, `enabled`, `id_module`)
VALUES ('Mio Hook', 'Modules\\MioModulo\\MioHook', 1, NULL);
```

### Scheduled Tasks / Cron (`zz_tasks`)

A Task is a PHP class executed by `cron.php` according to a cron expression.

```sql
INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`)
VALUES ('Mio Task', 'Modules\\MioModulo\\MioTask', '0 */6 * * *', 1);
```

---

## Module File Structure

Each module in `modules/module_name/` contains:

```
module/
├── edit.php           # Edit/view form for a record
├── add.php            # Add record form/modal
├── row-list.php       # List view row rendering
├── buttons.php        # Action buttons rendered in the editor toolbar
├── actions.php        # POST/AJAX action handlers
├── ajax/              # AJAX endpoints directory
├── src/               # Module source code and models
├── custom/src/        # Customisation overrides (takes precedence)
├── modals/            # Modal dialog components
├── plugins/           # Module-specific plugins
└── init.php           # Module initialization
```

### Standard Module Pattern

**edit.php**: Form and record display (included by `editor.php`)

**row-list.php**: List row rendering (included by `controller.php`)

**buttons.php**: Toolbar action buttons

**actions.php**: POST handler (save, delete, custom actions)

```php
switch (post('op')) {
    case 'insert':
    case 'update':
    case 'delete':
    case 'custom_action':
}
```

---

## Module & Plugin Registration

### Registering a Module (`zz_modules`)

```sql
INSERT INTO `zz_modules`
  (`name`, `directory`, `attachments_directory`, `options`, `icon`,
   `version`, `compatibility`, `order`, `parent`, `default`, `enabled`)
VALUES (
  'MioModulo',
  'mio_modulo',
  'mio_modulo',
  'SELECT |select| FROM `mia_tabella` WHERE 1=1 HAVING 2=2',
  'fa fa-cogs',
  '2.12',
  '2.*',
  1,
  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tabelle'),
  1,
  1
);

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
VALUES (1, LAST_INSERT_ID(), 'Mio Modulo', 'Mio Modulo - {campo}');
```

### Registering List Columns (`zz_views`)

```sql
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MioModulo'), 'id',              'mia_tabella.id',              1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MioModulo'), 'Nome',            'mia_tabella.nome',            2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MioModulo'), 'Data',            'mia_tabella.data',            3, 1, 0, 1, 1);
```

### Registering a Plugin (`zz_plugins`)

```sql
INSERT INTO `zz_plugins`
  (`name`, `idmodule_from`, `idmodule_to`, `position`, `enabled`, `directory`)
VALUES (
  'MioPlugin',
  (SELECT id FROM zz_modules WHERE name = 'ModuloHost'),
  (SELECT id FROM zz_modules WHERE name = 'ModuloTarget'),
  'tab',
  1,
  'mio_plugin'
);

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES (1, LAST_INSERT_ID(), 'Mio Plugin');
```

### Registering Settings (`zz_settings`)

```sql
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
(NULL, 'Abilita funzionalità X', '0', 'boolean', 1, 'Generali', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`)
VALUES (1, LAST_INSERT_ID(), 'Enable Feature X', 'Help text');
```

---

## HTMLBuilder — Form Field System

OpenSTAManager uses a proprietary template engine called **HTMLBuilder** to render all form fields and UI widgets.

### Two Syntaxes

| Delimiter | Purpose |
|-----------|---------|
| `{[ ... ]}` | **Form field** — renders an input component |
| `{( ... )}` | **Widget/component** — renders a UI element |

### Available Field Types

Based on the handlers defined in `src/HTMLBuilder/HTMLBuilder.php`:

| Type | Handler | Description |
|------|---------|-------------|
| `text` (default) | DefaultHandler | Standard text input |
| `select` | SelectHandler | Select2-powered dropdown |
| `checkbox` | ChoicesHandler | Checkbox input |
| `radio` | ChoicesHandler | Radio button |
| `bootswitch` | ChoicesHandler | Toggle switch |
| `date` | DateHandler | Date picker |
| `time` | DateHandler | Time picker |
| `timestamp` | DateHandler | Date + time picker |
| `image` | MediaHandler | Image upload |
| `ckeditor` | CKEditorHandler | Rich text editor |

### Form Fields Examples

```php
// Text input
{[ "type": "text", "name": "ragione_sociale", "label": "Business Name", "value": "$ragione_sociale$" ]}

// Select dropdown
{[ "type": "select", "label": "Payment", "name": "idpagamento",
   "values": "query=SELECT id, title AS descrizione FROM co_pagamenti ORDER BY title",
   "value": "$idpagamento$" ]}

// Checkbox
{[ "type": "checkbox", "label": "Active", "name": "active", "value": "$active$" ]}

// Date picker
{[ "type": "date", "label": "Date", "name": "data", "value": "$data$", "required": 1 ]}

// Number input (uses default handler)
{[ "type": "number", "label": "Amount", "name": "amount", "value": "$amount$" ]}
```

### Widgets and Buttons

```php
{( "name": "button", "type": "print", "id_module": "<?php echo $id_module; ?>", "id_record": "<?php echo $id_record; ?>" )}

{( "name": "widgets", "id_module": "<?php echo $id_module; ?>", "id_record": "<?php echo $id_record; ?>", "position": "top", "place": "editor" )}
```

---

## Helper Functions & Global Context

### Input Functions — Read Request Data

> ⚠️ **Critical rule**: Never read `$_POST` or `$_GET` directly. Always use `post()`, `get()`, or `filter()`.

```php
$op = post('op');
$id_record = (int) get('id_record');

// For system parameters
$id_module = filter('id_module');
$id_record = filter('id_record');
```

### Query Safety Functions

```php
// Escape value for safe SQL use (helper function from lib/helpers.php)
prepare($value)

// Escape value for safe SQL use (method from src/Database.php)
database()->prepare($value)

// Escape value for HTMLBuilder tag
prepareToField($value)
```

### Translation

```php
tr('Business Name')  // Returns localized string
```

### Settings

```php
$value = setting('Setting Name');
$value = setting('Setting Name') ?: 'default';
```

### Database Methods (from `src/Database.php`)

```php
$db = database();

// Query execution
$db->query($sql, $params);
$db->fetchOne($sql);
$db->fetchArray($sql);
$db->fetchRow($sql);

// Table operations
$db->tableExists($table);
$db->columnExists($table, $column);

// Transactions
$db->beginTransaction();
$db->commitTransaction();
$db->rollbackTransaction();

// Helpers
$db->lastInsertedID();
$db->prepare($parameter);
```

### Flash Messages

```php
flash()->success(tr('Record saved successfully.'));
flash()->error(tr('Error during save.'));
flash()->warning(tr('Warning: document already sent.'));
```

### Path Helpers

```php
base_path()        // Root-relative URL for links
base_dir()         // Absolute filesystem path
redirect_url($url) // HTTP redirect
```

### Date & Number Formatting

```php
// Using Translator
Translator::dateToLocale($record['data'])           // 'Y-m-d' → 'd/m/Y'
Translator::timestampToLocale($record['created_at']) // 'Y-m-d H:i:s' → 'd/m/Y H:i'

// Using helper functions (from lib/helpers.php)
dateFormat($date)
timestampFormat($timestamp)
numberFormat($number, $decimals)
moneyFormat($number, $decimals)
```

### Authentication

```php
AuthOSM::check()          // Is user authenticated?
Auth::user()              // Get current user array
auth_osm()->attempt($user, $pass)  // Perform login
AuthOSM::logout()         // Logout
```

### Permissions

```php
$structure->permission   // 'r' or 'rw' for current module/user
Permissions::check('rw') // Enforce write permission - exits if denied
```

---

## How to Find Things

### Find Module Table

1. Lookup `modules/module_name/edit.php` → see which table it uses
2. Naming convention: `PREFIX_name` (e.g., `in_interventi`, `co_documenti`)
3. Search `update/` files for current schema

### Find Model Class

```php
// For core models
Models\Setting::where('nome', 'X')->first();
Models\User::find(1);

// For module-specific models
Modules\Anagrafiche\Anagrafica::find(1);
```

---

## Golden Rules for Development

### Feature vs Fix

- **Feature**: Introduces new functionality, modifies schema → **New MINOR release (2.12.0)**
- **Fix**: Corrects bugs, improves existing code → **PATCH release (2.11.1, 2.11.2, etc.)**

### Query Best Practices

#### Use Eloquent ORM (Preferred)

```php
$setting = Models\Setting::where('nome', 'Setting Name')->first();
$user = Models\User::find(1);
$users = Models\User::where('idgruppo', 2)->get();

// Create/Update
$setting = Models\Setting::create(['nome' => 'X', 'valore' => 'Y']);
$user->update(['name' => 'New Name']);

// Delete
$user->delete();
```

#### Use Raw Queries When Necessary

```php
// Always use prepared statements - NEVER string interpolation
$dbo->query("SELECT * FROM table WHERE id = ?", [$id]);
$dbo->query("UPDATE table SET name = :name WHERE id = :id", [':name' => $name, ':id' => $id]);
```

### Security Rule

All user input must be parameterised:

```php
// NEVER - SQL Injection vulnerability
$dbo->query("SELECT * WHERE name = '$name'");

// ALWAYS - Safe with parameters
$dbo->query("SELECT * WHERE name = ?", [$name]);
Model::where('name', $name)->get();
```

---

## Quick Reference

### Important Paths

- **Migrations**: `update/`
- **Core Models**: `src/Models/`
- **Module Models**: `modules/{name}/src/`
- **Database Helper**: `src/Database.php`
- **Helper Functions**: `lib/helpers.php`
- **Modules**: `modules/`
- **Plugins**: `plugins/`
- **Configuration**: `config/`
- **Locales**: `locale/`
- **Templates**: `templates/`

### Common Functions

```php
// Input
post('field_name')
get('field_name')
filter('id_module')

// Query safety
prepare($value)
prepareToField($value)

// Translation & settings
tr('Text')
setting('Setting Name')

// Database
database()
$dbo->query($sql, $params)
$dbo->fetchOne($sql)
$dbo->fetchArray($sql)

// Flash feedback
flash()->success(tr('...'))
flash()->error(tr('...'))

// Path helpers
base_path()
base_dir()
redirect_url($url)

// Date/Number formatting
dateFormat($date)
numberFormat($number)
moneyFormat($number)

// Auth
AuthOSM::check()
Auth::user()

// Permissions
$structure->permission
Permissions::check('rw')
```

---

## Technology Stack

- **PHP**: 8.1+ required (recommended 8.3)
- **Database**: MySQL 8.0+
- **Framework**: Laravel `laravel/framework: ^12.0`
- **ORM**: Laravel 12 Eloquent via `illuminate/database: ^12.0`
- **Frontend**: JavaScript, CSS, Bootstrap, jQuery
- **Build Tools**: Composer, Yarn, Gulp
- **PDF Generation**: mPDF
- **HTTP Client**: Guzzle
- **Logging**: Monolog
- **API**: RESTful API via `api-platform/laravel: ^4.1`

---

**Document Version**: 1.1
**Last Updated**: March 2026
**Note**: This guide is the reference for maintaining consistency with OSM's architecture and conventions.
