---
currentMenu: moduli
---

# Moduli

> Un modulo (software) è un componente software autonomo e ben identificato, e quindi facilmente riusabile.
>
> \-- <cite>[Wikipedia](https://it.wikipedia.org/wiki/Modulo#Informatica)</cite>

All'interno del progetto, i moduli vengono genericamente definiti quali sistemi di gestione delle funzionalità del gestionale; proprio per questo, la loro struttura e composizione risulta spesso variabile e differenziata.

Ogni modulo è composto da diverse sezioni, generalmente suddivise in:

- Nucleo;
- [Stampe](Stampe.md);
- [Widget](Widget.md);
- [Plugin](Plugin.md).

OpenSTAManager presenta inoltre una struttura nativamente predisposta alla personalizzazione delle funzioni principali, il che rende il progetto ancora più complicato da comprendere a prima vista.

Di seguito viene presentate le strutture principali più comuni supportate dal gestionale; per ulteriori approfondimenti, si consiglia di controllare il codice sorgente dei moduli ufficiali.

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Struttura](#struttura)
    - [actions.php](#actionsphp)
    - [add.php e edit.php](#addphp-e-editphp)
    - [init.php](#initphp)
    - [controller_after.php e controller_before.php](#controllerafterphp-e-controllerbeforephp)
    - [modutil.php](#modutilphp)
- [Database](#database)
    - [zz_modules](#zzmodules)
    - [zz_permissions e zz_group_module](#zzpermissions-e-zzgroupmodule)
    - [zz_views e zz_group_view](#zzviews-e-zzgroupview)
    - [zz_plugins e zz_widgets](#zzplugins-e-zzwidgets)
- [Consigli per lo sviluppo](#consigli-per-lo-sviluppo)
    - [Progettazione](#progettazione)
    - [Sviluppo](#sviluppo)
    - [Test](#test)
- [Installazione](#installazione)
    - [Archivio ZIP](#archivio-zip)
        - [update/VERSIONE.sql](#updateversionesql)
        - [update/unistall.php](#updateunistallphp)
        - [MODULE](#module)
- [Moduli di base](#moduli-di-base)

<!-- /TOC -->

## Struttura

Il codice sorgente di ogni modulo di OpenSTAManager è all'interno di un percorso univoco all'interno della cartella **modules**.

    .
    └── modules
        └── modulo
           ├── actions.php
           ├── add.php
           ├── controller_after.php
           ├── controller_before.php
           ├── edit.php
           ├── init.php
           └── modutil.php

Il gestionale supporta in modo nativo questa struttura, che può essere ampliata e personalizzata secondo le proprie necessità: si consiglia pertanto di analizzare i moduli **Iva**, **Dashboard** e **Contratti** per esempi di diversa complessità.

> **Attenzione**: la presenza dei file sopra indicati è necessaria esclusivamente per i *moduli fisici*, cioè moduli che presentano la necessità di interagire con il codice sorgente e modificare i dati del gestionale.
>
> Per moduli presenti esclusivamente a livello di database (per sempio, **Movimenti**), si veda la sezione [Database](#database).

### actions.php

Il file `actions.php` gestisce tutte le operazioni supportate dal modulo.

In generale, le diverse operazioni vengono gestite attraverso attraverso una logica basata su casi (solitamente, il parametro `op` permette di identificare quale azione viene richiesta); il funzionamento a livello di programmazione può essere comunque sottoposto a scelte personali.

L'unico requisito effettivo risulta relativo alle operazioni di creazione dei nuovi record, per cui deve essere definito all'interno della variabile `$id_record` l'identificativo del nuovo elemento.
Per osservare questo sistema, si consiglia di analizzare il relativo file del modulo **Iva**.

### add.php e edit.php

Il file `add.php` contiene il template HTML dedicato all'inserimento di nuovi elementi per il modulo, mentre `edit.php` contiene il template HTML dedicato alla modifica degli stessi.

In base alla configurazione del modulo nel database, il file `edit.php` può assumere il ruolo di gestore della sezione principale dell'interno modulo.
Esempi di questa gestione si possono osservare nei moduli **Dashboard** e  **Gestione componenti** (si veda la sezione [zz_modules](#zzmodules)).

> **Attenzione**: il progetto individua in automatico la presenza del file `add.php` e agisce di conseguenza per permettere o meno l'inserimento di nuovi record.

### init.php

Il file `init.php` si occupa di individuare le informazioni principali utili all'identificazione e alla modifica dei singoli elementi del modulo.

In particolare, questo file è solitamente composto da una query dedicata ad ottenere tutti i dati dell'elemento nella variabile `$record` (`$records` per versioni <= 2.4.1), successivamente utilizzata dal gestore dei template per completare le informazioni degli input.

### controller_after.php e controller_before.php

Il file `controller_before.php` contiene il template HTML da aggiungere all'inizio della pagina principale del modulo se questo è strutturato in modo tabellare.

Similmente, il file `controller_after.php` contiene il template HTML da aggiungere alla fine della pagina principale nelle stesse condizioni.

### modutil.php

Il file `modutil.php` viene utilizzato per definire le funzioni PHP specifiche del modulo, e permettere in questo modo una gestione semplificata delle operazioni più comuni.

Si noti che un modulo non è necessariamente limitato all'utilizzo del proprio file `modutil.php`: come avviene per esempio in **Fatture** e **Interventi**, risulta possibile richiamare file di questa tipologia da altri moduli (in questo caso, da **Articoli** per la gestione delle movimentazioni di magazzino).

## Database

All'interno del database del progetto, le tabelle con il suffisso `zz` sono generalmente dedicate alla gestione delle funzioni di base del gestionale.

La gestione dei moduli avviene in questo senso grazie alle seguenti tabelle:

- `zz_modules`;
- `zz_permissions`;
- `zz_views`;
- `zz_plugins`;
- `zz_widgets`.

### zz_modules

La tabella `zz_modules` contiene tutte le informazioni dei diversi moduli installati nel gestionale in uso, con particolare riferimento a:

- Nome (utilizzato a livello di codice) [`name`]
- Titolo (nome visibile e personalizzabile) [`title`]
- Percorso nel file system (partendo da `modules/`) [`directory`]
- Icona [`icon`]
- Posizione nella sidebar [`order`]
- Compatibilità [`compatibility`]
- Query di default [`options`]
- Query personalizzata [`options2`]

Gli ultimi due attributi si rivelano di fondamentale importanza per garantire il corretto funzionamento del modulo, poiché descrivono il comportamento dello stesso per la generazione della schermata principale nativa in OpenSTAManager.

Sono permessi i seguenti valori:

- custom [Modulo con schermata principale personalizzata e definita nel file `edit.php`]
- {VUOTO} [Menu non navigabile]
- menu [Menu non navigabile]
- Oggetto JSON

```json
    { "main_query": [ { "type": "table", "fields": "Nome, Descrizione", "query": "SELECT `id`, `nome` AS `Nome`, `descrizione` AS `Descrizione` FROM `tabella` WHERE 2=2 HAVING 1=1 ORDER BY `nome`"} ]}
```

- Query SQL \[vedasi la tabella [zz_views](#zz_views-e-zz_group_view)]

```sql
    SELECT |select| FROM `tabella` WHERE 2=2 HAVING 1=1
```

### zz_permissions e zz_group_module

La tabella `zz_permissions` contiene i permessi di accesso dei vari gruppi ai diversi moduli, mentre la tabella `zz_group_module` contiene le clausole SQL per eventualmente restringere questo accesso.

### zz_views e zz_group_view

Le tabelle `zz_views` e `zz_group_view` vengono utilizzate dal gestionale per la visualizzazione delle informazioni secondo i permessi accordati, oltre che dal modulo **Viste** per la gestione dinamica delle query.

### zz_plugins e zz_widgets

La tabella `zz_plugins` contiene l'elenco di plugins relativi ai diversi moduli, mentre la tabella `zz_widgets` contiene l'elenco di widgets dei vari moduli.

## Consigli per lo sviluppo

### Progettazione

Alla base dello sviluppo di ogni modulo vi è una fase di analisi indirizzata all'individuazione dettagliata delle funzionalità dello stesso e della struttura interna al database atta a sostenere queste funzioni.

Siete dunque pregati di identificare chiaramente tutte le caratteristiche del Vostro nuovo modulo o delle Vostre modifiche prima di iniziare lo sviluppo vero e proprio (comunemente identificato con la scrittura del codice).

> E' bene trascurare le fasi di analisi e di progetto e precipitarsi all'implementazione allo scopo di guadagnare il tempo necessario per rimediare agli errori commessi per aver trascurato la fase di analisi e di progetto.
>
> \-- <cite>Legge di Mayers</cite>

### Sviluppo

Lo sviluppo del codice deve seguire alcune direttive generali per la corretta interpretazione del codice all'interno del gestionale: ciò comporta una struttura di base fondata sui file precedentemente indicati nella sezione [Struttura](#struttura) ma ampliabile liberamente.

### Test

Prima di pubblicare un modulo si consiglia di effettuare svariati test in varie installazioni.
Siete inoltre pregati di indicare i bug noti.

> Se c’è una remota possibilità che qualcosa vada male, sicuramente ciò accadrà e produrrà il massimo danno.
>
> \-- <cite>Legge di Murphy</cite>

## Installazione

L'installazione di un modulo è completabile in modo automatico seguendo la seguente procedura:

- Scaricare l'archivio `.zip` del modulo da installare;
- Accedere al proprio gestionale con un account abilita all'accesso del modulo **Aggiornamenti**;
- Selezionare l'archivio scaricato nella selezione file della sezione "Carica un nuovo modulo";
- Cliccare il pulsante "Carica".

Si ricorda che per effettuare l'installazione è necessaria la presenza dell'estensione `php_zip` (per ulteriori informazioni guardare [qui](http://php.net/manual/it/zip.installation.php)).

> **Attenzione**: la procedura può essere completata anche a livello manuale, ma si consiglia di evitare tale sistema a meno che non si conosca approfonditamente il procedimento di installazione gestito da OpenSTAManager.

### Archivio ZIP

L'archivio del modulo deve essere organizzato secondo la seguente struttura:

    modulo.zip
    ├── update
    |   ├── VERSIONE.sql
    |   └── unistall.php
    ├── ... - File contententi il codice del modulo
    └── MODULE

Alcuni esempi sulla struttura dei moduli personalizzati sono disponibili nella repository https://github.com/devcode-it/example (download effettuabile da [qui](http://openstamanager.com/download/plugin_di_esempio.zip)).

#### update/VERSIONE.sql

Il file `VERSIONE.sql` (dove VERSIONE sta per la versione del modulo con `_`[underscore] al posto di `.`[punto]) contiene le operazioni di installazione e aggiornamento del modulo a livello del database, comprendenti la creazione delle tabelle di base del modulo e l'inserimento di ulteriori dati nelle altre tabelle.

#### update/unistall.php

Il file `unistall.php` contiene le operazioni di disinstallazione del modulo a livello del database, e deve prevedere l'eliminazione delle tabelle non più necessarie e dei dati inutilizzati.

```php
<?php

include_once __DIR__.'/../../core.php';

$dbo->query("DROP TABLE `tabella`");

```

#### MODULE

Il file `MODULE` è infine il diretto responsabile dell'installazione del modulo poiché definisce tutti i valori caratteristici dello stesso; in caso di sua assenza la cartella compressa viene considerata non corretta.

```ini
name = "Nome del modulo"
version = "Versione"
directory = "Cartella di installazione"
options = "Operazione da eseguire all'apertura"
compatibility = "Versioni di compatibilità"
compatibility = "Compatibilità del modulo"
parent = "Genitore del modulo"
```

## Moduli di base

Nella versione base del gestionale sono presenti, all'interno della cartella **modules**, i seguenti moduli.

    .
    ├── aggiornamenti
    ├── anagrafiche
    ├── articoli
    ├── automezzi
    ├── backup
    ├── beni
    ├── categorie
    ├── causali
    ├── contratti
    ├── dashboard
    ├── ddt
    ├── fatture
    ├── gestione_componenti
    ├── interventi
    ├── impostazioni
    ├── iva
    ├── listini
    ├── misure
    ├── my_impianti
    ├── ordini
    ├── pagamenti
    ├── partitario
    ├── porti
    ├── preventivi
    ├── primanota
    ├── scadenzario
    ├── stati_intervento
    ├── tecnici_tariffe
    ├── tipi_anagrafiche
    ├── tipi_intervento
    ├── utenti
    ├── viste
    ├── voci_servizio
    └── zone
