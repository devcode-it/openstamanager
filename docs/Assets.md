---
currentMenu: assets
---

# Assets

> Web assets are things like CSS, JavaScript and image files that make the frontend of your site look and work great.
>
> \-- <cite>[Symfony](http://symfony.com/doc/current/best_practices/web-assets.html)</cite>

Gli assets sono, all’interno dei moderni ambienti di sviluppo web, il cuore pulsante del software in relazione al layout e al livello di accessibilità; in particolare,il termine assets fa solitamente riferimento ai componenti di natura grafica di un software, quali immagini, fonts e icone, linguaggi di scripting client-side (JavaScript) e fogli di stile a cascata (_Cascading Style Sheets_).

Il progetto utilizza [Yarn](https://yarnpkg.com/) per gestire l'installazione e l'aggiornamento degli assets e [Gulp](http://gulpjs.com/) per compilarli e associarli con le personalizzazioni. Viene inoltre richiesta la presenza di [Git](https://git-scm.com/) asll'interno del sistema operativo.

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Struttura](#struttura)
- [Personalizzazione](#personalizzazione)
    - [Tema grafico](#tema-grafico)
    - [Aggiornamento e installazione pacchetti](#aggiornamento-e-installazione-pacchetti)
    - [Compilazione](#compilazione)
- [Assets predefiniti](#assets-predefiniti)

<!-- /TOC -->

## Struttura

Yarn salva automaticamente gli assets da lui gestiti all'interno della cartella `node_modules`, non presente nella repository e nelle release del progetto per la sua natura estremamente variabile e facilmente riproducibile (tramite l'utilizzo dello strumento, come si vedrà in [Personalizzazione](#personalizzazione)).

Gli assets personalizzati del progetto sono al contrario contenuti all'interno della cartella `assets/src`; parallelamente, gli assets utilizzati direttamente dal progetto sono infine contenuti all'interno della cartella `assets/dist`, generata in automatico tramite l'utilizzo di [Gulp](http://gulpjs.com/).


## Personalizzazione

L'introduzione di una gestione automatizzata rende, di fatto, maggiromente semplificata la gestione delle dipendeze grafiche del progetto, portando però al tempo stesso alla necessità di utilizzare una specifica procedura per aggiornare e personalizzare gli assets.
Si ricorda che è altamente sconsigliato modificare i contenuti delle cartelle `node_modules` o `assets/dist` in modo manuale, poiché tali modifiche andrebbero perse a seguito di ogni aggiornamento.


### Tema grafico

La personalizzazione dello stile del gestionale può essere effettuata a partire dal foglio di stile `assets/src/css/themes/default.css`, contentente le principali impoistazioni grafiche del progetto.
L'aggiunta di un nuovo tema richieda la creazione di un file indipendente nella stessa cartella, rinominando la classe CSS generica con il nome della skin inserita (da `.skin-default` a `.skin-nome`).

Una volta eseguita la task automatica di compilazione, il nuovo file varrà aggiunto in `themes.min.css` di `assets/css`.

Per modificare lo stile utilizzato dal gestionale, vedere la variabile `$theme` in `config.inc.php`.

### Aggiornamento e installazione pacchetti

Nel caso si rivelasse necessario installare o aggiornare i pacchetti predisposti dal gestionale, è necessario procedere all'esecuzione dei comdani caratteristici di Yarn e successivamente eseguire una compliazione degli assets.

L'installazione di nuovi pacchetti viene eseguita attraverso il seguente comando:

```bash
yarn add <package>
```

L'aggiornamento degli assets gestiti è effettuabile tramite il seguente comando:

```bash
yarn upgrade
```

Per ulteriori informazioni, consultare la [documentazione ufficiale di Yarn](https://yarnpkg.com/en/docs).

### Compilazione

Per compilare gli assets, sia quelli gestiti da Yarn che quelli personalizzati, è necessario eseguire il seguente comando:

```bash
yarn run build-OSM
```

**Attenzione**: la compilazione è fondamentale a seguito di ogni modifica degli assets, poiché altrimenti i file utilizzati dal progetto non saranno aggiornati.

## Assets predefiniti

- admin-lte
- autosize
- bootstrap
- bootstrap-colorpicker
- bootstrap-daterangepicker
- bootstrap-switch
- ckeditor
- components-jqueryui
- datatables.net-bs
- datatables.net-scroller-bs
- eonasdan-bootstrap-datetimepicker
- font-awesome
- fullcalendar
- html5shiv
- inputmask
- jquery
- jquery-form
- jquery-slimscroll
- jquery-ui-touch-punch
- moment
- parsleyjs
- respond.js
- select2
- select2-bootstrap-theme
- signature_pad
- smartwizard
- sweetalert2
- tooltipster

_I nomi sono indicati secondo la notazione tipica dei pacchetti NPM._
