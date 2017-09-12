---
currentMenu: file
---

# File

La cartella `files` viene utilizzata dal progetto per gestire in modo unificato contenuti di vario tipo per i moduli installati.
In generale, questa cartella è dedicata alla memorizzazione dei file di cui viene fatto l'upload attraverso la funzione fornita in automatico dal getionale, ma sono presenti delle specifiche personalizzazioni necessarie per l'adeguato funzionamento di alcuni moduli.

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Modulo MyImpianti](#modulo-myimpianti)

<!-- /TOC -->

## Modulo MyImpianti

Il modulo **MyImpianti** sfrutta la propria cartella all'interno di `files` per gestire, oltre alle proprie immagini, le impostazioni (`.ini`) dei componenti disponibili.

I file `*.ini` devono seguire il seguente standard. {} = facoltativo

```ini
[Nome del campo]
tipo = tag_HTML
valore = {"Valore di default"}
{opzioni = "Opzione 1", "Opzione 2", "Opzione 3"}

[Nome del campo]
tipo = tag_HTML
valore = {"Valore di default"}
{opzioni = "Opzione 1", "Opzione 2", "Opzione 3"}
```

La dicitura "tag_HTML" indica la possibilità di inserire all'interno del campo il nome di un qualsiasi tag HTML per l'utilizzo durante la modifica.
In particolare, il gestionale supporta la maggior parte dei campi HTML di input (input, select, textarea, date, ...); se necessario, è inoltre possibile  (span, p, ...).

Il file `my_impianti/componente.ini` è un esempio di base di questa funzionalità, e un'ulteriore personalizzazione può essere trovata [nel forum](http://www.openstamanager.com/forum/viewtopic.php?f=5&t=93).
