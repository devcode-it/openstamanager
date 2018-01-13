---
currentMenu: stampe
---

# Stampe

Pagina in costruzione.

- [MPDF](#mpdf)
- [HTML2PDF](#html2pdf)
    - [Struttura](#struttura)
        - [pdfgen.php](#pdfgenphp)
        - [pdfgen_variables.php (INCLUDE)](#pdfgenvariablesphp-include)
        - [Struttura interna](#struttura-interna)


## MPDF

**Attenzione**: come indicato nel secondo punto in http://mpdf.github.io/tables/auto-layout-algorithm.html, MPDF effettua un resizing del font nel caso il contenuto di una cella superi l'altezza totale di una pagina.
Fino a quel punto, il rendering funziona perfettamente.

Nel caso fosse per esempio aumentare le dimensioni del font, si consiglia di effettuare alcuni test per controllare se le tabelle vengono renderizzate nel modo corretto e previsto.


## HTML2PDF

### Struttura

La cartella _templates_ contiene tutti i template per la creazione dei PDF, raggruppati in base al nome del modulo. QUesti vengono utilizzati da `pdfgen.php` e da `pdfgen_variables.php` per la generazione vera e propria del PDF tramite il framework [HTML2PDF](https://github.com/spipu/html2pdf).

#### pdfgen.php

Il file `pdfgen.php` si occupa della formattazione dei contenuti dei template per la visualizzazione vera e propria del PDF, inizializzando l'oggetto relativo ed eseguendone l'output.

#### pdfgen_variables.php (INCLUDE)

Il file `pdfgen_variables.php` si occupa della sostituzione delle variabili comuni a tutti i template, e viene richiamata dal file `pdfgen.MODULO.php` descritto di seguito.

#### Struttura interna

La cartella _templates_ contiene tutti i template per la creazione dei PDF relativi al modulo specifico, in una struttura interna simile alla seguente (modulo **Contratti** utilizzato come esempio).

    .
    └── contratti
        ├── contratto_body.html - Struttura di base del PDF
        ├── contratto.html - Contenitore personalizzato della struttura del PDF
        ├── logo_azienda.jpg - Logo dell'azienda specifico per il PDF
        └── pdfgen.contratti.php - Individuazione delle informazioni da visualizzare e generazione della loro struttura
