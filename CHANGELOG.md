# Changelog

Tutti i maggiori cambiamenti di questo progetto saranno documentati in questo file. Per informazioni più dettagliate, consultare il log GIT della repository su GitHub.

Il formato utilizzato è basato sulle linee guida di [Keep a Changelog](http://keepachangelog.com/), e il progetto segue il [Semantic Versioning](http://semver.org/) per definire le versioni delle release.

- [2.4.5 (2019-01-10)](#245-2019-01-10)
  - [Aggiunto (Added)](#aggiunto-added)
  - [Modificato (Changed)](#modificato-changed)
  - [Fixed](#fixed)
- [2.4.4 (2018-12-12)](#244-2018-12-12)
  - [Aggiunto (Added)](#aggiunto-added)
  - [Fixed](#fixed)
- [2.4.3 (2018-12-07)](#243-2018-12-07)
  - [Aggiunto (Added)](#aggiunto-added-1)
  - [Fixed](#fixed-1)
- [2.4.2 (2018-11-14)](#242-2018-11-14)
  - [Aggiunto (Added)](#aggiunto-added-2)
  - [Modificato (Changed)](#modificato-changed)
  - [Deprecato (Deprecated)](#deprecato-deprecated)
  - [Rimosso (Removed)](#rimosso-removed)
  - [Sicurezza (Security)](#sicurezza-security)
- [2.4.1 (2018-08-01)](#241-2018-08-01)
  - [Aggiunto (Added)](#aggiunto-added-3)
  - [Modificato (Changed)](#modificato-changed-1)
  - [Fixed](#fixed-2)
- [2.4 (2018-03-30)](#24-2018-03-30)
  - [Aggiunto (Added)](#aggiunto-added-4)
  - [Modificato (Changed)](#modificato-changed-2)
  - [Fixed](#fixed-3)
- [2.3.1 (2018-02-19)](#231-2018-02-19)
  - [Aggiunto (Added)](#aggiunto-added-5)
  - [Modificato (Changed)](#modificato-changed-3)
  - [Fixed](#fixed-4)
- [2.3 (2018-02-16)](#23-2018-02-16)
  - [Aggiunto (Added)](#aggiunto-added-6)
  - [Modificato (Changed)](#modificato-changed-4)
  - [Deprecato (Deprecated)](#deprecato-deprecated-1)
  - [Rimosso (Removed)](#rimosso-removed-1)
  - [Fixed](#fixed-5)
  - [Sicurezza (Security)](#sicurezza-security-1)
- [2.2 (2016-11-10)](#22-2016-11-10)
  - [Aggiunto (Added)](#aggiunto-added-7)
  - [Fixed](#fixed-6)
- [2.1 (2015-04-02)](#21-2015-04-02)
  - [Aggiunto (Added)](#aggiunto-added-8)
  - [Modificato (Changed)](#modificato-changed-5)
  - [Fixed](#fixed-7)


## 2.4.5 (2019-01-10)

### Aggiunto (Added)

 - Introduzione dello split payment
 - Introduzione dei campi Nome e Cognome per le anagrafiche
 - Introduzione della possibilità di non verificare il certificato SSL per gli account email
 - Introduzione calcolo del guadagno in fase di aggiunta righe nei documenti

### Modificato (Changed)

 - Miglioramento della generazione xml per le Fatture Elettroniche
 - Miglioramento procedura importazione xml per le Fatture Elettroniche
 - Gestite righe di tipo descrizione nelle Fatture Elettroniche
 
### Fixed

 - Fix calcolo codice intervento
 - Fix dei filtri per la stampa del riepilogo interventi
 - Risolti altri bug minori

## 2.4.4 (2018-12-12)

### Aggiunto (Added)

 - Controllo sulla presenza di personalizzazioni nel modulo **Aggiornamenti**
 - Stati multipli per le Fatture Elettroniche (per ampliamenti futuri)

### Fixed

 - Risolti malfunzionamenti negli import degli allegati della Fattura Elettronica
 - Risolti diversi bug

## 2.4.3 (2018-12-07)

### Aggiunto (Added)

 - Nodi secondari per la Fatturazione Elettronica
 - Importazione di Fatture Elettroniche in formato P7M
 - Messaggi informativi in vari campi

### Fixed

 - Risolti alcuni problemi di compatibilità
 - Risolti malfunzionamenti delle righe dei documenti
 - Fix dei calcoli

## 2.4.2 (2018-11-14)

### Aggiunto (Added)

 - Plugin per generazione della Fatturazione Elettronica (modulo **Fatture di vendita**) e l'importazione relativa (modulo **Fatture di acquisto**)
 - Libreria autonoma per i messaggi da mostrare all'utente
 - Logging completo delle azioni degli utente (accessibile agli Amministratori)
 - Supporto a [Prepared Statements PDO](http://php.net/manual/it/pdo.prepared-statements.php)
 - Impostazioni da definire durante l'installazione e l'aggiornamento del software
 - Helper per semplificare lo sviluppo di codice indipendente (file `lib/helpers.php`)
 - Funzioni generiche per moduli e plugin (file `lib/common.php`)
 - API per la gestione dell'applicazione
 - Classe `Util\Zip` per la gestione dei file ZIP
 - Controllo automatico degli aggiornamenti da GitHub (modulo **Aggiornamenti**)
 - Ripristino semplificato dei backup (modulo **Backup**)
 - Impostazioni per impostare un orario lavorativo personalizzato nel modulo **Dashboard**
 - Possibilità di impostare un elemento predefinito per i moduli **Porti**, **Causali** e **Tipi di spedizioni**
 - Impostazione *Stampa per anteprima e firma* per selezionare la stampa da mostrare nella sezione **Anteprima e firma** di **Attività**
 - Ritenuta d'acconto predefinita per le **Anagrafiche**
 - Sistema automatizzato per l'importazione delle classi di moduli e plugin (file `config/namespaces.php`)
 - Sistema di notifiche predefinito
    - Notifica di chiusura delle **Attività** (impostabile dal modulo **Stati attività**)
    - Notifica di aggiunta e rimozione del tecnico dalle **Attività**
 - Gestione revisione preventivi
 - Categorizzazione impianti
 - Modulo per gestione documentale
 - Categorizzazione allegati

### Modificato (Changed)

 - Normalizzazione delle nazioni registrate dal gestionale (https://github.com/umpirsky/country-list)
 - Gestione delle strutture principali attraverso modelli (**Eloquent**)[https://laravel.com/docs/5.6/eloquent]
 - Miglioramenti nella gestione dei record (variabile `$record` al posto di `$records[0]`)
 - Ottimizzazione delle query di conteggio (metodo `fetchNum`)
 - Miglioramento del sistema di aggiornamento e installazione, con supporto completo ai plugin
 - Drag&drop nella **Dashboard** permette di impostare le attività senza sessioni di lavoro
 - Aggiungere un tecnico in una **Attività** salva le modifiche apportate in precedenza
 - Rinominat moduli ddt in "Ddt in uscita" e "Ddt in ingresso"
 - Miglioramenti grafici vari

### Deprecato (Deprecated)

 - Variabili globali $post e $get, da sostituire con le funzioni `post()` e `get()`
 - Funzione `get_var()`, da sostituire con la funzione `setting()`
 - Funzioni PHP inutilizzate: `datediff()`, `unique_filename()`, `create_thumbnails()`

### Rimosso (Removed)

 - Funzioni PHP deprecate nella versione 2.3.*

### Sicurezza (Security)

 - Abilitata protezione contro attacchi CSRF (opzione `$disableCSRF` nella configurazione per disattivarla in caso si verifichino problemi)

## 2.4.1 (2018-08-01)

### Aggiunto (Added)
 - Supporto alla generazione PDF/A
 - Gestione di Note di accredito e di addebito per le Fatture
 - Salvataggio AJAX delle righe in Fatture
 - Cambio automatico dello stato dei documenti
 - Nomi per i filtri di accesso ai moduli
 - Anteprime degli upload (per immagini e PDF)
 - Validazione di indirizzi email e codici fiscali
 - Test della connessione al server email
 - Widget *Attività da pianificare* per individuare le attività senza tecnici
 - Esportazione tabelle in PDF ed Excel (impostazione *Abilita esportazione Excel e PDF*)
 - Stampa dedicata al calendario attività in **Dashboard**
 - Operazioni rapide su **Anagrafiche** di tipo *Cliente*
 - Campi aggiuntivi nella creazione di nuove **Anagrafiche**
 - Possibilità di specificare tempi standard per *Tipologia di attività*
 - Seriali nella stampa delle **Attività**
 - Quantità calcolata tramite movimenti in data attuale per **Articoli**
 - Movimenti manuali con causale degli **Articoli**

### Modificato (Changed)
 - Miglioramento della gestione di installazione/aggiornamento
    - Migliorata la procedura per i moduli (esempi: https://github.com/devcode-it/example)
    - Aggiunto supporto all'installazione dei plugin (esempio: https://github.com/devcode-it/example/tree/master/sedi)
    - Aggiunto supporto a file ZIP con vari moduli/plugin (installazione in ordine alfabetico)
 - Miglioramento dei pre-requisiti di installazione
 - Gestione degli upload tramite AJAX
 - Gestione del logo per le stampe come un allegato
 - Gestione delle immagini di **Articoli** e **Impianti** come allegati
 - Miglioramento del plugin *Pianificazione attività* in **Contratti**
 - Miglioramento della ritenuta d'acconto (calcolo impostabile su Imponibile o Rivalsa INPS)
 - Ripristinati plugin *Pianificazione fatturazione* e widget *Rate contrattuali*
 - Miglioramento della tabella dei *Costi Totali* in **Attività**
 - Collegamento ad un'anagrafica obbligatorio per i nuovi utenti
 - Ridenominazione delle tabelle `co_righe_contratti` e `co_righe2_contratti` in `co_contratti_promemoria` e `co_righe_contratti`
 - I movimenti articoli utilizzano la data del documento relativo
 - I chilometri del cliente vengono riportati nell'attività
 - I tecnici possono aggiungere **Attività** solo a loro nome

### Fixed
 - Correzione dei link alle stampe sulle tabelle dei moduli
 - Correzione della scontistica per la stampa **Attività**
 - Correzione degli arrotondamenti su IVA e imponibili nei documenti
 - Correzione del budget dei **Contratti**
 - Correzione della scadenza "Data fattura fine mese"
 - Correzione del plugin *Statistiche* in **Anagrafiche**
 - Correzione del widget *Debiti verso fornitori*
 - Correzioni minori

## 2.4 (2018-03-30)

### Aggiunto (Added)

 - Modelli di stampa su database, con possibilità di creare più stampe per singolo modulo e raggrupparle in unica voce di menu
 - Possibilità di inviare le email dai vari moduli e gestione degli account SMTP
 - Introduzione dei segmenti: filtri aggiuntivi definibili per ogni modulo
 - Aggiunti sezionali per fatture acquisto/vendita
 - Nuovo modulo archivio banche per definire poi in ogni anagrafica (cliente o fornitore) la banca predefinita
 - Nuova pagina dedicata all'utente dove è possibile:
   - Cambiare la propria password
   - Visualizzare il proprio token di accesso all'API
   - Visualizzare il link e le informazioni per importare il calendario eventi all'esterno del gestionale
 - Introduzione della possibilità di poter impostare dei campi personalizzati per ogni modulo
 - Aggiunta possibilità di inserire un articolo in contratti e preventivi
 - Aggiunta di una variabile $baseurl globale
 - Aggiunta nei documenti la possibilità di inserire una riga descrittiva senza importi
 - Aggiunta creazione fattura da contratto
 - Aggiunta scelta iva su attività per spese aggiuntive e materiale
 - Aggiunta gestione allegati anche per contratti, anagrafiche, preventivi, articoli, impianti
 - Modulo per import CSV (anagrafiche)

### Modificato (Changed)

  - Modificati pulsanti principali dei moduli e fissati in alto durante lo scorrimento
  - Resi i pulsanti principali dei moduli dinamici e personalizzabili
  - Migliorati attività da pianificare
  - Migliorato il calcolo della numerazione per i documenti
  - Modificato il numero per le fatture di acquisto utilizzabile per numeri di protocollo
  - Migliorata gestione dei menu a tendina dinamici
  - Modificata aggiunta attività in fatturazione, con raggruppamento per costi orari e diritti di chiamata
  - Modificato calcolo ritenuta d'acconto, con scelta se calcolare su imponibile o imponibile + rivalsa inps

### Fixed

 - Corretto calcolo IVA con sconto globale unitario
 - Corretto calcolo numerazione dei ddt
 - Correzione visualizzazione di attività a calendario a cavallo di periodi diversi
 - Correzioni minori

## 2.3.1 (2018-02-19)

### Aggiunto (Added)

 - Aggiunti i seriali in stampa
 - Aggiunta la zona nelle attività (in sola lettura dall'anagrafica)
 - Aggiunta tramite flag la possibilità di inserire la descrizione dell'attività in fattura
 - Aggiunta esportazione bulk in zip dei pdf delle attività selezionate
 - Aggiunte informazioni del cliente e fornitore nelle relative stampe ordini

### Modificato (Changed)

 - Migliorati i widget di "Crediti da clienti" e "Debiti verso fornitori", con calcolo parziale del rimanente
 - Disabilitato di default il modulo "Viste"
 - Migliorata la gestione della pianificazione attività sui contratti, con la possibilità di eliminare tutte le pianificazioni o di creare direttamente una attività collegata
 - Modificato l'inserimento di attività in fattura raggruppando per costo orario nel caso ci siano più costi orari
 - Spostato il conto "Perdite e profitti" nello stato patrimoniale

### Fixed

 - Corretti diversi problemi in fase di installazione
 - Modifica e miglioramento dell'arrotondamento iva in fattura, sia a video che in stampa
 - Corretto il caricamento di menu a tendina per gli utenti con permessi limitati
 - Corretti i permessi per la stampa fattura per utenti con permessi limitati
 - Corretto e migliorato il funzionamento delle viste
 - Corretto il calcolo dello sconto incondizionato in percentuale nei principali moduli
 - Corretta la stampa consuntivo del preventivo
 - Corrette alcune funzioni dello scadenzario, in quanto sparivano delle scadenze in fase di modifica prima nota
 - Corretto il cambio di stato automatico di ddt dopo la fatturazione
 - Migliorato il caricamento dinamico del calendario via ajax in quanto a volte si bloccava
 - Correzioni varie sulla gestione viste
 - Corretto il piano dei conti per arrotondare gli importi come negli altri moduli
 - Corretto il calcolo iva nei contratti
 - Corretto il salvataggio delle sessioni tecnico nelle proprie attività
 - Corretto un problema nel salvataggio firma attività su alcuni tablet
 - Corretto ordinamento voci di menu laterale
 - Altre correzioni minori e strutturali


## 2.3 (2018-02-16)

### Aggiunto (Added)

- Creazione di sistemi centralizzati per la gestione della funzioni principali del progetto (secondo una logica ad oggetti)
  - Connessione al database (tramite PDO, con possibile ampliamento dei DMBS supportati)
  - Autenticazione degli utenti
  - Gestione e controllo dei permessi
  - Gestione degli input degli utenti
  - Personalizzazione delle impostazioni
  - Traduzione e conversione dei formati (date e numeri)
  - Gestione degli aggiornamenti
- Creazione della documentazione ufficiale per sviluppatori (disponibile nel Wiki e in `docs/`)
- Creazione di un sistema API ufficiale
- Creazione di cartelle di default per i backup (`backup/`) e i log (`logs/`)
- Completo supporto alla traduzione del progetto
- Possibilità di vedere se ci sono altri utenti che stanno visualizzando lo stesso record (opzione "Attiva notifica di presenza utenti sul record" nel modulo **Impostazioni**)
- Possibilità di creare nuovi elementi dei moduli all'interno del record (oltre che dalla visualizzazione generale del modulo)
- Nuova struttura per permettere il richiamo via AJAX delle procedure per la creazione di nuovi elementi all'esterno del modulo specifico (tramite il file `add.php`)
- Nuovo sistema di gestione delle operazioni di debugging e logging
- Nuovi plugins e widgets
- Introduzione di nuovi moduli primari e secondari
  - **Viste**
  - **Utenti e permessi** (convertito)
  - **Impostazioni** (convertito)
  - **IVA**
  - **Pagamenti**
  - **Porto**
  - **Unità di misura**
  - **Aspetto beni**
  - **Causali**
  - **Categorie**
  - **Ritenute acconto**
  - **Movimenti**
- Nuovo modulo per gestire i file `.ini` dei componenti degli impianti (**Gestione componenti**)
- Nuovo pulsante per resettare i filtri di ricerca (nella sezione generica dei moduli)
- Nuova gestione generalizzata degli upload
- Nuova sistema di gestione delle operazioni _bulk_ degli upload
- Nuova documentazione integrata delle funzioni PHP in `lib/functions.php`
- Nuovo file `lib/init.js` per permettere una rapida inizializzazione dei componenti JS
- Nuove funzioni relative ai diversi moduli
  - Introduzione della numerazione univoca per gli impianti (**MyImpianti**)
  - Possibilità di individuare i componenti dell'impianto su cui l'attività viene effettuato (**Attività**)
  - Possibilità di firmare le attività (**Attività**)
  - Possibilità di selezionare della tipologia di attività per ogni sessione di lavoro (**Attività**)
  - Introduzione di una tabella riepilogativa più completa dei costi (**Attività**)
  - Introduzione di sconti globali e specifici (unitari e percentuali) in **Contratti**, **DDT**, **Fatture**, **Attività**, **Preventivi**, **Ordini**

### Modificato (Changed)

- Gestione delle librerie e dipendenze PHP tramite _Composer_
- Gestione degli assets tramite _Yarn_ e _Gulp_
  - Miglioramenti grafici
  - Sostituzione di _Chosen_ con _Select2_
  - Gestione delle tabelle ora completamente basata su _Datatables_
- Miglioramento della procedura di installazione e aggiornamento del gestionale
  - Aggiunto sistema di ripresa dell'aggiornamento (se questi è stato bloccato in una fase intermedia tra i singoli aggiornamenti)
  - Aggiunto sistema di bloccaggio dell'aggiornamento, per evitare problemi nel caso molteplici richieste di update
  - Semplificazione della procedura manuale, che ora non richiede nessuna modifica dei file VERSION da parte dell'utente
  - Modificata la struttura della tabella `updates`
- Passaggio completo all'estensione `.php` per tutti i file dei moduli
- Miglioramento dell'interpretazione del template per la generazione degli input, ora disponibile ovunque all'interno del progetto
- Miglioramento della gestione dei permessi
- Miglioramento delle stampe principali
- Miglioramento delle informazioni disponibili sul progetto e della procedura di segnalazione dei bug
- Miglioramento generale sull'identificazione del modulo attualmente in uso e sull'inclusione dei file necessari per il funzionamento
- La prima anagrafica di tipo Azienda caricata viene impostata come "Azienda predefinita"
- Ottimizzazione della schermata per aggiunta dell'attività
- Miglioramento dei riquadri delle spese aggiuntive e degli articoli
- Miglioramento dei permessi di visione per il modulo **MyImpianti** (ogni cliente vede solo i propri impianti)

### Deprecato (Deprecated)

- Classe HTMLHelper, a favore della nuova classe Filter
- Funzioni PHP (`lib/deprecated.php`)

### Rimosso (Removed)

- Funzioni PHP non utilizzate (`lib/functions.php`)
  - coolDate
  - cut_text
  - data_italiana
  - dateadd
  - full_html_entity_decode
  - gestione_sessioni
  - get_module_name
  - get_module_name_by_id
  - get_text_around
  - get_user_browser
  - getAvailableModules
  - getLastPathSegment
  - getSistemaOperativo
  - getVersion
  - is_id_ok
  - mytruncate
  - read_file
  - RemoveNonASCIICharacters
  - show_error_messages
  - show_info_messages
  - write_error
  - write_ok
- Funzioni JS non utilizzate (`lib/functionsjs.php`)
- Cartelle e file non più utilizzati (`lib/jscripts`, `widgets`, `share`, `lib/dbo.class.php`, `lib/widgets.class.php`, ...)

### Fixed

- Risoluzione di numerosi bug e malfunzionamenti

### Sicurezza (Security)

- Aggiunta protezione contro attacchi di tipo XSS
- Aggiunta base per contrastare l'SQL Injection
- Aggiunta protezione (temporaneamente disabilitata) contro attacchi CSRF
- Aggiunto sistema basilare contro attacchi brute-force all'accesso
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
