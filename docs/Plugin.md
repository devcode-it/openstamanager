---
currentMenu: plugin
---

# Plugin

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Installazione](#installazione)
    - [Archivio ZIP](#archivio-zip)
        - [update](#update)
        - [PLUGIN](#plugin)

<!-- /TOC -->

Pagina in costruzione.

## Installazione



### Archivio ZIP

L'archivio del modulo deve essere organizzato secondo la seguente struttura:

    modulo.zip
    ├── ... - File contententi il codice del modulo
    └── PLUGIN

Alcuni esempi sulla struttura dei moduli personalizzati sono disponibili nella repository https://github.com/devcode-it/example (download effettuabile da [qui](http://openstamanager.com/download/plugin_di_esempio.zip)).

#### update

Contrariamente ai moduli, i plugin non supportano la modifica del database in fase di installazione e aggiornamento.

#### PLUGIN

Il file `PLUGIN` è infine il diretto responsabile dell'installazione del modulo poiché definisce tutti i valori caratteristici dello stesso; in caso di sua assenza la cartella compressa viene considerata non corretta.

```ini
name = "Nome del plugin"
version = "Versione"
directory = "Cartella di installazione"
options = "Operazione da eseguire all'apertura"
icon = "Icona (Font-Awesome)"
compatibility = "Versioni di compatibilità"
module_from = "Nome del modulo di origine"
module_to = "Nome del modulo di destinazione e visualizzazione"
position = "Tipo di modulo (valori disponibili: tab)"
```
