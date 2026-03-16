# HTMLBuilder – Analisi Approfondita
> Fase 2 – Component Analysis

## Cos'è HTMLBuilder

Un **motore di template DSL** che converte tag personalizzati inline (nei file PHP dei moduli)
in HTML completo. È il sistema di generazione form dell'intero gestionale.

---

## Due tipi di tag

### 1. Handler tags `{[ ... ]}`  → Input HTML
Generano singoli campi form (input, select, date, checkbox, ecc.)

```php
// Sintassi nei template PHP dei moduli:
{[ "type": "text", "name": "numero", "label": "Numero", "required": 1, "value": "$numero$" ]}
{[ "type": "select", "name": "idanagrafica", "values": "query=SELECT id, nome FROM an_anagrafiche" ]}
{[ "type": "date", "name": "data", "value": "$data$" ]}
{[ "type": "checkbox", "name": "split_payment", "value": "$split_payment$" ]}
{[ "type": "ckeditor", "name": "note" ]}
```

### 2. Manager tags `{( ... )}` → Widget/strutture composite
Generano blocchi HTML complessi (upload list, bottoni, custom fields, widget, ecc.)

```php
{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}
{( "name": "custom_fields", "id_module": "$id_module$", "id_record": "$id_record$" )}
{( "name": "widgets", "id_module": "$id_module$", "id_record": "$id_record$" )}
{( "name": "button", "action": "save" )}
```

---

## Pipeline di rendering

```
Template PHP
    ↓ ob_start() + include file modulo
    ↓ translateTemplate() in lib/functions.php
    ↓ HTMLBuilder::replace($html)
        ├── regex match {( )} → Manager::manage($json)
        └── regex match {[ ]} → Handler::handle($json)
                                 Wrapper::before() + HTML + Wrapper::after()
    ↓ process() → sostituisce |attr|, |name|, ecc.
    ↓ Output HTML finale
```

### Sostituzione variabili `$nome$`
Prima del rendering, i valori `$campo$` vengono sostituiti con i valori del record:
```php
// In elaborate():
preg_match_all('/\$([a-z0-9\_]+)\$/i', $value, $m)
// → sostituisce $idanagrafica$ col valore da $record['idanagrafica']
```

---

## Handler disponibili

| Type | Classe | Output |
|---|---|---|
| `text`, `number`, ecc. | `DefaultHandler` | `<input>` standard |
| `select` | `SelectHandler` | `<select>` + Select2, può eseguire query SQL |
| `checkbox`, `radio`, `bootswitch` | `ChoicesHandler` | toggle/radio |
| `date`, `time`, `timestamp` | `DateHandler` | date picker |
| `ckeditor` | `CKEditorHandler` | WYSIWYG editor |
| `image` | `MediaHandler` | upload immagine |

---

## Manager disponibili

| Name | Classe | Output |
|---|---|---|
| `filelist_and_upload` | `FileManager` | lista file + uploader |
| `button` | `ButtonManager` | bottoni azione (save, delete, ecc.) |
| `custom_fields` | `FieldManager` | campi personalizzati configurabili |
| `widgets` | `WidgetManager` | widget dashboard del modulo |
| `log_email` | `EmailManager` | log email inviate |
| `log_sms` | `SMSManager` | log SMS inviati |

---

## Estensibilità
Il sistema è completamente sostituibile a runtime:
```php
HTMLBuilder::setHandler('mio_tipo', MyCustomHandler::class)
HTMLBuilder::setManager('mio_widget', MyCustomManager::class)
HTMLBuilder::setWrapper(MyCustomWrapper::class)
```
Questo permette ai moduli `custom/` di registrare handler personalizzati.

---

## Note importanti
- Ricorsione controllata: `$max_recursion = 10` per evitare loop infiniti
- I tag sono decodificati come JSON → errori di sintassi nel template causano silenzioso fallimento
- `SelectHandler` può eseguire query SQL direttamente dal template: potenziale SQL injection
  se il valore `values="query=..."` viene popolato da input utente non sanitizzato
