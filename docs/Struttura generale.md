---
currentMenu: struttura
---

# Struttura generale

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Caratteristiche](#caratteristiche)
    - [Open source](#open-source)
    - [Modulare e personalizzabile](#modulare-e-personalizzabile)
    - [Multipiattaforma e user friendly](#multipiattaforma-e-user-friendly)
- [Struttura](#struttura)
- [Root](#root)
    - [add.php](#addphp)
    - [ajax_autocomplete.php](#ajax_autocompletephp)
    - [ajax_dataload.php](#ajax_dataloadphp)
    - [ajax_select.php](#ajax_selectphp)
    - [bug.php](#bugphp)
    - [core.php](#corephp)
    - [config.inc.php](#configincphp)
    - [controller.php](#controllerphp)
    - [editor.php](#editorphp)
    - [index.php](#indexphp)
    - [info.php](#infophp)
    - [log.php](#logphp)
    - [composer.json, gulpfile.js, package.json](#composerjson-gulpfilejs-packagejson)
- [Cartella api](#cartella-api)
- [Cartella assets](#cartella-assets)
- [Cartella backup](#cartella-backup)
- [Cartella docs](#cartella-docs)
- [Cartella files](#cartella-files)
- [Cartella include](#cartella-include)
    - [bottom.php (CUSTOMIZABLE)](#bottomphp-customizable)
    - [top.php (CUSTOMIZABLE)](#topphp-customizable)
- [Cartella lib](#cartella-lib)
    - [deprecated.php](#deprecatedphp)
    - [functions.php](#functionsphp)
    - [functionsjs.php (HTML)](#functionsjsphp-html)
    - [init.js (HTML)](#initjs-html)
    - [modulebuilder.php](#modulebuilderphp)
    - [permissions_check.php](#permissions_checkphp)
    - [user_check.php](#user_checkphp)
    - [classes/Database.php](#classesdatabasephp)
    - [classes/HTMLBuilder.php](#classeshtmlbuilderphp)
- [Cartella locale](#cartella-locale)
- [Cartella modules](#cartella-modules)
- [Cartella templates](#cartella-templates)
- [Cartella update](#cartella-update)
    - [create_updates.sql](#create_updatessql)
    - [update_VERSIONE.sql (OPEN)](#update_versionesql-open)
    - [update_VERSIONE.php](#update_versionephp)
- [Cartella vendor](#cartella-vendor)

<!-- /TOC -->

## Caratteristiche

### Open source
La natura open source (termine inglese che significa _sorgente aperta_) del progetto evidenzia lo spirito di collaborazione e condivisione che pervade l'attività di sviluppo del gestionale, di cui gli autori rendono pubblico il codice sorgente e ne favoriscono il libero studio, permettendo a programmatori indipendenti di apportarvi modifiche ed estensioni.

Particolarmente espressiva in questo senso risulta essere la documentazione ufficiale del progetto:
> Il progetto è un software open source perché permette agli utilizzatori di studiarne il funzionamento ed adattarlo alle proprie esigenze; inoltre, in ambito commerciale, non obbliga l'utilizzatore ad essere legato allo stesso fornitore di assistenza.

La licenza in utilizzo è la GNU General Public License 3.0 (GPL 3.0).

### Modulare e personalizzabile
OpenSTAManager possiede una struttura fortemente modulare, che ne permette la rapida espandibilità e, nello specifico,  la realizzazione di funzionalità _ad hoc_, personalizzate nel modo più completo secondo le richieste del cliente.

### Multipiattaforma e user friendly
Il progetto risulta compatibile con numerose piattaforme, necessitando esclusivamente un browser moderno da parte dei suoi utilizzatori per sfruttare appieno le sue potenzialità.
L'interfaccia di interazione con l'utente finale risulta inoltre estremamente semplificata e _user friendly_, oltre che _responsive_, presentando caratteristiche completamente compatibili con tutti i dispositivi mobili (in particolare, tablet e smartphone).

## Struttura

Scaricando la versione GIT del progetto dovreste trovare una struttura di base molto simile a quella seguente.

    .
    ├── api
    ├── assets
    ├── backup
    ├── doc
    ├── files
    ├── include
    ├── lib
    ├── locale
    ├── modules
    ├── src
    ├── templates
    ├── update
    └── vendor

Analizzeremo ora in dettaglio la funzione delle diverse cartelle e dei relativi contenuti.
Si avverte che il gestionale è fortemente basato sulla correttezza contemporanea di molti file: siete pertanto pregati di astenervi da modifiche o, se queste dovessero rivelarsi necessarie, procedere alla creazione di un relativo file custom nella cartella del file. E' comunque consigliabile richiedere l'assistenza ufficiale.

Per maggiori informazioni riguardanti il procedimento di personalizzazione, rivolgersi alla sezione [Personalizzazione].

## Root

I contenuti della cartella _root_, indicata nella struttura precedente dal punto inziale, sono estremamente importante per il progetto, in quanto sono generalmente dedicati a garantire il funzionamento dell'intero gestionale.
Questa centralizzazione permette al software di essere estremamente scalabile e personalizzabile, soprattutto in relazione ai moduli.
Per maggiori informazioni riguardanti lo sviluppo di un modulo, consultare la sezione [Moduli].

### add.php

Il file `add.php` è dedicato alla gestione dei form di creazione nuovi elementi all'interno dei vari moduli.
In particolare si occupa parallelamente della funzionalità di aggiunta al volo, visibile in azione nei modulo **Attività**, **Articoli** e in alcuni altri punti del software.

### ajax_autocomplete.php

Il file `ajax_dataload.php` gestisce il caricamento dinamico dei dati in varie sezioni del sito, relativamente alle operazioni di auto-completamento dei form.

**Attenzione**: questo sistema è ormai quasi deprecato e, tranne in rari casi, completamente sostituito dall'utilizzo del file `ajax_select.php` e dal plugin [Select2](https://select2.github.io/).

### ajax_dataload.php

Il file `ajax_dataload.php` gestisce il caricamento dinamico dei dati nelle tabelle fornite nella vista generale dei moduli (`controller.php`), filtrando i risultati in base alle richieste dell'utente.

### ajax_select.php

Il file `ajax_select.php` gestisce il caricamento dinamico dei dati nei diversi select abilitati, garantendo l'accesso a tutti i record senza provocare rallentamenti (persino per numeri più elevati).

### bug.php

Il file `bug.php` si occupa della segnalazione dei bug, fornendo un sistema integrato di invio email dopo la configurazione di pochi parametri iniziali.
Le opzioni relative alle informazioni da allegare sono:

- Allegare il file di log (fondamentale nel caso si stia effettuando una segnalazione)
- Allegare una copia del database
- Allegare le informazioni relative al PC utilizzato

### core.php

Il _core_, come si può discernere dal nome, è il nucleo dell'intero gestionale: si occupa delle operazioni di inizializzazione fondamentali, compresa l'inclusione del file di configurazione `config.inc.php` e la creazione dell'elenco degli assets da includere.
Si occupa inoltre del controllo dei permessi, tramite l'inclusione di `lib/permissions.php`, e dell'individuazione delle informazioni di base relative al modulo aperto.

### config.inc.php

Il file `config.inc.php` contiene tutte le informazioni per accedere correttamente al database e per determinare il tema utilizzato dal gestionale.

**Attenzione**: questo file non è presente di default poiché obbligatoriamente da personalizzare tramite la procedura di installazione.

### controller.php

Il file `controller.php` si occupa di gestire l'accesso generico ai moduli, caricando le informazioni e i widget del modulo in oggetto. Permette inoltre la visualizzazione, in base ai permessi accordati all'utente, del pulsante di creazione nuovi elementi.

### editor.php

Il file `editor.php` si occupa di gestire l'accesso specifico ai dati di un singolo elemento di un modulo, caricando al tempo stesso l'insieme di plugin (e, in casi più rari, di widget) legati alla visualizzazione dell'elemento in oggetto. Permette inoltre, in base ai permessi accordati all'utente, la modifica dei dati inseriti e l'interazione con altri moduli.

### index.php

Il file `index.php` si occupa delle operazioni di autenticazione (login e logout), oltre che effettuare un controllo sulla disponibilità di aggiornamenti (tramite l'inclusione di `update/update_checker.php`) e a garantire il redirect iniziale al primo modulo su cui l'utente possiede i permessi di accesso.

### info.php

Il file `info.php` contiene la sezione informativa relativa al progetto, indicante la community, la modalità di supporto e le offerte a pagamento.

### log.php

Il file `log.php` permette di visualizzare le informazioni relative agli ultimi 100 accessi, comprendenti eventuali tentativi falliti ed errori.

**Attenzione**: nel caso in cui l'utente sia un amministratore, le informazioni accessibili sono relative a _tutti_ i tentativi di accesso (al contrario, un utente normale può visualizzare esclusivamente i propri tentativi).

### composer.json, gulpfile.js, package.json

Per maggiori informazioni questi file, consultare le sezioni [Framework] e [Assets].

## Cartella api

Per maggiori informazioni riguardanti la cartella `api` e i suoi contenuti, rivolgersi alla sezione [API].

## Cartella assets

Per maggiori informazioni riguardanti la cartella `assets` e i suoi contenuti, rivolgersi alla sezione [Assets (CSS, JS e immagini)].

## Cartella backup

La cartella _backup_ è quella di default utilizzata dall'operazione omonima del progetto.

## Cartella docs

La cartella `docs`, come si può intuire, contiene la documentazione di sviluppo del progetto.

## Cartella files

Per maggiori informazioni riguardanti la cartella `files` e i suoi contenuti, rivolgersi alla sezione [Moduli].

## Cartella include

### bottom.php (CUSTOMIZABLE)

Il file `bottom.php` si occupa delle operazioni di chiusura della pagina HTML, garantendo inoltre l'eliminazione dei log temporanei della sessione.

### top.php (CUSTOMIZABLE)

Il file `top.php` gestisce la creazione del layout di base del gestionale, comprendente la barra di navigazione e la sidebar.

## Cartella lib

La cartella `lib` contiene le librerie personalizzate e le funzioni utilizzate dall'intero gestionale nei diversi moduli.

**Attenzione**: sono qui presenti solo i metodi generali e comunemente riutilizzati. Per maggiori informazioni riguardanti la locazione delle funzioni specifiche di un modulo, visitare la sezione [Moduli].

### deprecated.php

Il file `deprecated.php` contiente l'insieme di funzioni deprecate nella versione corrente del gestionale, che verranno successivamente rimosse nella futura release.

### functions.php

Il file `functions.php` contiene tutte le funzioni comunemente utilizzate nel progetto.

### functionsjs.php (HTML)

Il file `functionsjs.php` contiene tutte le funzioni JavaScript comunemente utilizzate nel progetto.

### init.js (HTML)

Il file `init.js` contiene le funzioni JavaScript comunemente richiamate al caricamento di parti indipendenti del progetto.

### modulebuilder.php

Il file `modulebuilder.php` contiene le funzioni (e le sezioni HTML) atte all'upload di file e all'individuazione degli stessi, oltre che alla loro gestione, nei vari moduli.

### permissions_check.php

Il file `permissions_check.php` si occupa dei controlli relativi ai permessi di accesso di un utente ad un determinato modulo.

**Attenzione**: questo file è ora sostituito dalla classe Permissions.

### user_check.php

Il file `user_check.php` si occupa di controllare se è stato effettuato l'accesso.

**Attenzione**: questo file è ora sostituito dalla classe Permissions.

### classes/Database.php

La classe Database gestisce la connessione con il database, rendendo disponibile un insieme di funzioni utili ad elaborare i risulti delle diverse query.

### classes/HTMLBuilder.php

La classe HTMLBuilder contiene i metodi responsabili della creazione di contenuto HTML partendo dal template interno del progetto.

Per maggiori informazioni le caratteristiche del template utilizzato dal gestionale, rivolgersi alla sezione [Template].

## Cartella locale

La cartella `locale` contiene tutte le traduzioni del progetto, nei formati tipici di Gettext (`.po` e `.mo`).

## Cartella modules

Per maggiori informazioni riguardanti la cartella `modules` e i suoi contenuti, rivolgersi alla sezione [Moduli].
Si ricorda che per tutti i contenuti del modulo è possibile creare una personalizzazione nella cartella `custom`.

## Cartella templates

La cartella `templates` contiene tutti i file relativi alla visualizzazione in PDF dei dati dei vari moduli.
Per maggiori informazioni riguardanti la cartella `templates` e i suoi contenuti, rivolgersi alla sezione [Stampe].

## Cartella update

### create_updates.sql

Il file `create_updates.sql` contiene la query SQL per la creazione della tabella di gestione degli aggiornamenti e delle installazioni del gestionale.

### update_VERSIONE.sql (OPEN)

I file `VERSIONE.sql` contengono l'insieme di query SQL necessarie per l'aggiornamento del gestionale alla versione _VERSIONE_.

### update_VERSIONE.php

I file `VERSIONE.php` contengono l'insieme di operazioni PHP (e, talvolta, SQL) necessarie per l'aggiornamento del gestionale alla versione _VERSIONE_.

## Cartella vendor

Per maggiori informazioni riguardanti la cartella `vendor` e i suoi contenuti, rivolgersi alla sezione [Framework].
