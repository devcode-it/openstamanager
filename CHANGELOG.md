# Changelog

Tutti i maggiori cambiamenti di questo progetto saranno documentati in questo file. Per informazioni più dettagliate, consultare il log SVN della repository su SourceForge.

Il formato utilizzato è basato sulle linee guida di [Keep a Changelog](http://keepachangelog.com/), e il progetto segue il [Semantic Versioning](http://semver.org/) per definire le versioni delle release.

## Tabella dei contenuti

<!-- TOC depthFrom:2 depthTo:2 orderedList:false updateOnSave:true withLinks:true -->

- [Tabella dei contenuti](#tabella-dei-contenuti)
- [2.3 (In sviluppo)](#23-in-sviluppo)
- [2.2 (2016-11-10)](#22-2016-11-10)
- [2.1 (2015-04-02)](#21-2015-04-02)

<!-- /TOC -->

## 2.3 (In sviluppo)

### Aggiunto (Added)

- Creazione della documentazione ufficiale per sviluppatori (disponibile nel Wiki e in `docs/`)
- Creazione di un sistema API ufficiale
- Creazione di un sistema per controllare gli accessi degli utenti
- Nuovi moduli _Viste_, _Utenti e permessi_, _Opzioni_, con ulteriori moduli per la gestione di tabelle secondarie (_IVA_, _Pagamenti_, ...)
- Nuova struttura per permettere il richiamo via AJAX delle procedure per la creazione di nuovi elementi all'esterno del modulo specifico (tramite il file `add.php`)
- Possibilità di vedere se ci sono altri utenti che stanno visualizzando lo stesso record (opzione "Sessione avanzata" nel modulo _Opzioni_)
- Nuove funzioni PHP (con commenti) in `lib/functions.php`
  - getRevision
  - str\_replace\_once
  - filter
  - post
  - get
  - readSQLFile
  - array\_pluck
  - starts_with
  - ends_with
  - slashes
  - prepare
  - tr (con aggiunta della funzione di gettext nel caso questi non sia abilitato - `_`)
  - safe\_truncate (in sostituzione a cut\_text)
  - secure\_random\_string
  - random\_string
  - safe\_truncate
  - force\_download
  - isHTTPS
- Nuovi oggetti per la gestione delle operazioni di base (posizionati in `lib/classes/`)
  - Auth
  - Database
  - Filter (in sostituzione a HTMLHelper, ora deprecato ma ancora presente)
  - HTMLBuilder
  - Modules
  - Options
  - Permissions
  - Translator (per la futura internazionalizzazione e traduzione del progetto, inoltre disponibile in `locale/it/`)
  - Update
  - Widgtes (modificati per lavorare secondo una metodologia statica)
- Nuova gestione delle operazioni di debugging e logging
- Nuovo file `lib/init.js` per permettere una rapida inizializzazione dei componenti JS
- Creazione di cartelle di default per i backup (`backup/`) e i log (`logs/`)
- Nuovo pulsante per resettare i filtri di ricerca (nella sezione generica dei moduli)
- Nuovo modulo per gestire i file `.ini` dei componenti degli impianti
- Nuovi plugins e widgets
- Nuova gestione generalizzata degli upload
- Nuove funzioni relative ai diversi moduli
  - Possibilità di inserire in fattura un range di serial number
  - Possibilità di individuare i componenti dell'impianto su cui l'intervento viene effettuato
  - Possibilità di gestire le ritenute d'acconto
  - Firma degli interventi
  - Selezione della tipologia di attività per ogni sessione di lavoro
  - Tabella riepilogativa più completa dei costi
  - Sconto incondizionato in _Interventi_

### Modificato (Changed)

- Gestione delle librerie e dipendenze PHP tramite _Composer_
- Gestione degli assets tramite Yarn e Gulp
- Miglioramenti grafici
- Miglioramento della procedura di installazione
- Miglioramenti delle informazioni disponibili sul progetto e della procedura di segnalazione dei bug
- Impianti ora identificati tramite numerazione univoca (non più tramite matricola)
- Sostituzione di Chosen con Select2
- Miglioramento dell'interpretazione del template per la generazione degli input (`lib/htmlbuilder.php`), ora inoltre disponibile ovunque all'interno del progetto
- Miglioramento generale sull'identificazione del modulo attualmente in uso e sull'inclusione dei file necessari per il funzionamento
- Miglioramento della gestione dei permessi
- Gestione della connessione al database tramite Medoo (possibile futuro ampliamento dei DMBS supportati)
- Gestione delle tabelle ora completamente basata su Datatables
- Ottimizzazione della schermata per aggiunta dell'intervento
- Miglioramento dei riquadri delle spese aggiuntive e degli articoli
- La prima anagrafica di tipo Azienda caricata viene impostata come Azienda predefinita
- Passaggio completo all'estensione `.php` per tutti i file dei moduli
- Miglioramento dei permessi di visione per il modulo _MyImpianti_, per cui ora ogni cliente vede solo i propri impianti
- Miglioramento della procedura di aggiornamento del gestionale
  - Aggiunto sistema di ripresa dell'aggiornamento (se questi è stato bloccato in una fase intermedia tra i singoli aggiornamenti)
  - Aggiunto sistema di bloccaggio dell'aggiornamento, per evitare problemi nel caso molteplici richieste di update
  - Semplificazione della procedura manuale, che ora non richiede nessuna modifica dei file VERSION da parte dell'utente (la versione dell'aggiornamento viene memorizzata nel file VERSION.new)
  - Modificata la struttura della tabella `updates`

### Deprecato (Deprecated)

- Classe HTMLHelper, a favore della nuova classe Filter
- Funzioni PHP
  - readDateTime
  - readDateTimePrint
  - get\_permessi
  - saveDateTime
  - saveDate
  - fix\_str
  - clean
  - makeid
  - read
  - readTime
  - readDate
  - build\_html\_element


### Rimosso (Removed)

- Funzioni PHP non utilizzate (`lib/functions.php`)
  - is\_id\_ok
  - write\_error
  - write\_ok
  - getAvailableModules
  - read\_file
  - dateadd
  - show\_info\_messages
  - show\_error\_messages
  - get\_module\_name
  - mytruncate
  - get\_user\_browser
  - RemoveNonASCIICharacters
  - full\_html\_entity\_decode
  - data\_italiana
  - gestione\_sessioni
  - get\_text\_around
  - coolDate
  - get\_module\_name\_by\_id
  - cut\_text
  - getLastPathSegment
  - cut\_text
- Funzioni JS non utilizzate (`lib/functionsjs.php`)
- Cartelle non più utilizzate (`lib/jscripts/`, `lib/html2pdf/`, `widgets`, `share`, ...)
- File non più utilizzati (`lib/dbo.class.php`, `lib/widgets.class.php`, ...)

### Fixed

- Risoluzione di numerosi bug e malfunzionamenti

### Sicurezza (Security)

- Aggiunta protezione contro l'XSS
- Aggiunta base per contrastare l'SQL Injection
- Passaggio della codifica della password con algoritmo di hashing BCrypt

## 2.2 (2016-11-10)

### Aggiunto (Added)

- Aggiunto ordinamento righe in fattura e stampa con ordine impostato
- Creazione automatica del conto cliente e fornitore nel piano dei conti
- Aggiunte stampe dei mastrini nel piano dei conti
- Aumentata performance caricamento record sulle viste principali dei moduli
- Aggiunta funzionalità di rinnovo contratto con collegamento a contratti precedenti
- Migliorata gestione dei backup (1 backup al giorno)
- Aggiunta tipologia di attività di default nel cliente per pre-caricarla durante la creazione attività
- Aggiunta funzionalità di firma rapportino e stampa del rapportino con firma inserita
- Modifica raggruppamento voci di menu, principalmente "Vendite" e "Acquisti"
- Aggiunta funzionalità di duplicazione fattura
- Migliorata la procedura di installazione
- Aggiunta richiesta di salvataggio prima di uscire da una schermata
- Aggiunta possibilità di collegare più agenti ad un cliente, e specificarne uno principale
- Aggiunta schermata di visualizzazione accessi
- Aggiunte rivalsa inps e ritenuta d'acconto nelle singole righe in fattura
- Aggiunti widget "Valore magazzino" e "Articoli in magazzino"
- Aggiunta stampa viste principali da browser con buona grafica minimale
- Aggiunta gestione componenti
- Aggiunta possibilità di generare lotti e serial number dalla fattura e ddt di acquisto
- Aggiunta possibilità di impostare dei costi unitari per ogni tipo di attività collegata al contratto, per utilizzare prezzi concordati nel contratto durante le attività

### Fixed

- Bugfix vari sui permessi
- Bugfix minori

## 2.1 (2015-04-02)

### Aggiunto (Added)

- Aggiunto stato “Parzialmente pagato” sulle fatture
- Aggiunta stampa scadenzario
- Aggiunta possibilità di includere più ddt in fattura
- Aggiunto blocco sulla modifica campi di testo per gli utenti in sola lettura
- Aggiunta scelta rivalsa inps e ritenuta d’acconto per ogni riga della fattura

### Modificato (Changed)

- Allargate le cifre decimali a 4 sugli importi

### Fixed

- Alcune migliorie su vari moduli
- Aumentata performance schermate
