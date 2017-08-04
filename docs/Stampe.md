---
currentMenu: stampe
---

# Stampe

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Struttura](#struttura)
    - [pdfgen.php](#pdfgenphp)
    - [pdfgen_variables.php (INCLUDE)](#pdfgen_variablesphp-include)
    - [Struttura interna](#struttura-interna)

<!-- /TOC -->

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
        ├── contratto_body.html (OPEN) - Struttura di base del PDF
        ├── contratto.html (OPEN) - Contenitore personalizzato della struttura del PDF
        ├── logo_azienda.jpg (HTML) - Logo dell'azienda specifico per il PDF
        └── pdfgen.contratti.php (INCLUDE) - Individuazione delle informazioni da visualizzare e generazione della loro struttura
