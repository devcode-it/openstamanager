# Modelli Eloquent e Traits – Analisi Approfondita
> Fase 2 – Component Analysis

## Struttura dei Model (src/Models/)

| Model | Tabella | Ruolo |
|---|---|---|
| `Module` | `zz_modules` | Moduli installati, permessi, gerarchia |
| `Plugin` | `zz_plugins` | Plugin collegati ai moduli |
| `User` | `zz_users` | Utenti del sistema |
| `Group` | `zz_groups` | Gruppi/ruoli utente |
| `Setting` | `zz_settings` | Impostazioni di sistema |
| `Locale` | `zz_locales` | Lingue disponibili |
| `View` | `zz_views` | Colonne/viste per le liste moduli |
| `PrintTemplate` | `zz_prints` | Template di stampa PDF |
| `Upload` | (varie) | File allegati |
| `Log` | `zz_logs` | Log accessi |
| `OperationLog` | `zz_operations` | Log operazioni utenti |
| `Hook` | `zz_hooks` | Hook/eventi di sistema |
| `Cache` | `zz_cache` | Cache applicativa |
| `ApiResource` | — | Risorse API Platform |

---

## Sistema di Traduzione dei Campi (`RecordTrait`)

**Pattern chiave del progetto**: i campi traducibili NON risiedono nella tabella principale ma in tabelle `_lang` separate.

```
Tabella principale:  zz_modules       (id, name, enabled, order, ...)
Tabella traduzioni:  zz_modules_lang  (id, id_record, id_lang, title)
```

Il `RecordTrait` (src/Traits/RecordTrait.php) fornisce:

```php
$model->setTranslation('title', 'Fatture', $id_lang)
// → INSERT o UPDATE su zz_modules_lang

$model->getTranslation('title', $id_lang)
// → SELECT title FROM zz_modules_lang WHERE id_record=? AND id_lang=?
```

**Comportamento su save()**: il trait sovrascrive `save()` per sincronizzare
automaticamente i campi traducibili nelle tabelle `_lang` per tutte le lingue
disponibili, usando `$this->name ?: $this->nome` come valore di fallback.

**Tabelle `_lang` note**: `zz_modules_lang`, `co_statidocumento_lang`,
`co_tipidocumento_lang`, `co_pagamenti_lang`, `dt_causalet_lang`, ecc.

**Pattern di JOIN nelle query raw dei moduli**:
```sql
LEFT JOIN `co_statidocumento_lang` ON (
    `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id`
    AND `co_statidocumento_lang`.`id_lang` = :id_lang
)
```

---

## `LocalPoolTrait` – Cache in memoria per Collections

Fornisce un pool statico (cache di sessione PHP) per evitare query ripetute:

```php
static::getAll()   // carica tutta la collection una volta sola
static::pool($id)  // cerca in cache, poi DB se non trovato
static::getCurrent() / setCurrent($id)  // modulo/plugin corrente
```

Usato da: `Module`, `Plugin`, `Setting`, `Locale`.

---

## `Module` Model – Dettagli

- **Global Scopes**: `enabled=true` (sempre) + `with('groups')` (carica permessi)
- **Gerarchia**: `parent` / `children` / `allParents` / `allChildren` → struttura ricorsiva ad albero
- **Permessi**: pivot su `zz_permissions` (idmodule, idgruppo, permessi: 'r'|'rw'|'-')
- **Flag**: tabella `zz_modules_flags` → `use_notes`, `use_checklists`, ecc.
- **$appends**: `permission`, `option`, `use_notes`, `use_checklists` calcolati automaticamente
- **Relazioni**: `plugins()`, `prints()`, `views()`, `groups()`, `clauses()`, `Templates()`

---

## Traits – Panoramica completa

| Trait | Scopo |
|---|---|
| `RecordTrait` | Traduzioni _lang, customField(), uploads(), save() override |
| `LocalPoolTrait` | Cache in memoria, getCurrent()/setCurrent() |
| `ManagerTrait` | Gestione filepath con sistema custom/ |
| `PathTrait` | Utility percorsi file |
| `HierarchyTrait` | Strutture ad albero parent/children |
| `ReferenceTrait` | Riferimenti tra documenti |
| `Components/NoteTrait` | Note associate al record |
| `Components/UploadTrait` | Allegati associati al record |
