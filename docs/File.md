---
currentMenu: file
---

# File

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [MyImpianti](#myimpianti)

<!-- /TOC -->

## MyImpianti

La cartella _files_ contiene file di vari tipologie, raggruppati in base al modulo di cui fanno parte:

- Impostazione (`.ini`), utilizzate dai moduli abilitati in tal senso per ampliare l'offerta naturale degli stessi.
- File di cui viene effettuato l'upload all'interno dei vari moduli (riconosciuti dal gestionale tramite le funzioni interne al file `lib/modulebuilder.php`).

I file \*.ini devono seguire il seguente standard. {} = facoltativo

```ini
[Nome]
tipo = tag_HTML
valore ={ "Valore di default"}
{opzioni = "Opzione 1", "Opzione 2", "Opzione 3"}

[Nome]
tipo = tag_HTML
valore ={ "Valore di default"}
{opzioni = "Opzione 1", "Opzione 2", "Opzione 3"}
```

Per tag_HTML si intendono tutti i tag HTML, con preferenza rivolata verso quelli di input (input, select, textarea, date, ...) sebbene il sistema accetti anche gli altri (span, p, ...).

Attualmente questi file vengono utilizzati esclusivamente dal modulo **MyImpianti** per la gestione delle varie tipologie di _Componenti_. Il file `my_impianti/componente.ini` è un esempio di base di questa funzionalità, e un'ulteriore personalizzazione può essere trovata [nel forum](http://www.openstamanager.com/forum/viewtopic.php?f=5&t=93).
