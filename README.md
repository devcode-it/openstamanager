<p align="center">
  <a href="http://openstamanager.com">
    <img src="https://www.openstamanager.com/wp-content/uploads/2015/04/logo_full-2.png">
  </a>

  <p align="center">
    Il gestionale open source per l'assistenza tecnica e la fatturazione.
    <br>
    <br>
    <a href="https://devcode-it.github.io/openstamanager">Documentazione tecnica</a>
    &middot;
    <a href="http://openstamanager.com">Sito ufficiale</a>
  </p>
</p>

<br>

[![GitHub release](https://img.shields.io/github/release/devcode-it/openstamanager/all.svg)](https://github.com/devcode-it/openstamanager/releases)
[![Downloads](https://img.shields.io/github/downloads/devcode-it/openstamanager/total.svg)](https://github.com/devcode-it/openstamanager/releases)
[![SourceForge](https://img.shields.io/sourceforge/dt/openstamanager.svg?label=SourceForge)](https://sourceforge.net/projects/openstamanager/)
[![license](https://img.shields.io/github/license/devcode-it/openstamanager.svg)](https://github.com/devcode-it/openstamanager/blob/master/LICENSE)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](http://sourceforge.net/donate/index.php?group_id=236538)

Il gestionale OpenSTAManager è un software open-source e web based, sviluppato dall'azienda informatica DevCode di Este per gestire ed archiviare il servizio di assistenza tecnica e la relativa fatturazione.
Il nome del progetto deriva dalla parziale traduzione in inglese degli elementi principali che lo compongono: la natura open source e il suo obiettivo quale Gestore del Servizio Tecnico di Assistenza.

Un software gestionale, identificato nell'insieme degli applicativi che automatizzano i processi di gestione all'interno delle aziende, appartiene solitamente a una specifica categoria del settore, specializzata negli ambiti di:

- Gestione della contabilità;
- Gestione del magazzino;
- Gestione e ausilio della produzione;
- Gestione e previsione dei budget aziendali;
- Gestione ed analisi finanziaria.

Secondo questa definizione, OpenSTAManager riesce a generalizzare al proprio interno le funzionalità caratteristiche della contabilità e della gestione del magazzino, presentando inoltre moduli piuttosto avanzati e destinati a complementare l'attività aziendale in relazione agli interventi di assistenza della realtà lavorativa in oggetto.

La documentazione ufficiale è disponibile all'indirizzo <https://devcode-it.github.io/openstamanager>.

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Requisiti](#requisiti)
- [Installazione](#installazione)
    - [Versioni](#versioni)
    - [Github](#github)
- [Perché software open source](#perch%C3%A9-software-open-source)
- [Community](#community)
- [Contribuire](#contribuire)
- [Sviluppatori](#sviluppatori)
- [Licenza](#licenza)

<!-- /TOC -->

## Requisiti

L'installazione del gestionale richiede la presenza di un server web con abilitato il [DBMS MySQL](https://www.mysql.com)  e il linguaggio di programmazione [PHP](http://php.net).

- PHP >= 5.4
- MySQL >= 5.0

Per ulteriori informazioni sui pacchetti che forniscono questi elementi di default, visitare la sezione [Installazione](https://devcode-it.github.io/openstamanager/installazione.html) della documentazione.

## Installazione

Per procedere all'installazione è necessario seguire i seguenti punti:

1. [Scaricare una release ufficiale del progetto](https://github.com/devcode-it/openstamanager/releases).
2. Creare una cartella (ad esempio `openstamanager`) nella root del sever web installato ed estrarvi il contenuto della release scaricata. Il percorso della cartella root del server varia in base al software in utilizzo:

   - LAMP (`/var/www/html`)
   - XAMPP (`C:/xampp/htdocs` per Windows, `/opt/lampp/htdocs/` per Linux, `/Applications/XAMPP/htdocs/` per MAC)
   - WAMP (`C:\wamp\www`)
   - MAMP (`C:\MAMP\htdocs` per Windows, `/Applications/MAMP/htdocs` per MAC)

3. Creare un database vuoto (tramite [PHPMyAdmin](http://localhost/phpmyadmin/) o riga di comando).
4. Accedere a <http://localhost/openstamanager> dal vostro browser.
5. Inserire i dati di configurazione per collegarsi al database.
6. Procedere all'installazione del software, cliccando sul pulsante **Installa**.

**Attenzione**: è possibile che l'installazione richieda del tempo. Si consiglia pertanto di attendere almeno qualche minuto senza alcun cambiamento nella pagina di installazione (in particolare, della progress bar presente) prima di cercare una possibile soluzione nelle discussioni del forum o nella sezione dedicata.

### Versioni

Per mantenere un elevato grado di trasparenza riguardo al ciclo delle release, seguiamo le linee guida [Semantic Versioning (SemVer)](http://semver.org/) per definire le versioni del progetto.
Per vedere tutte le versioni disponibili al download, visitare la [pagina relativa](https://github.com/devcode-it/openstamanager/releases) su Github (per versioni precedenti alla 2.3, visitare [SourceForge](https://sourceforge.net/projects/openstamanager/files)).

Nel caso utilizziate il programma per uso commerciale, si consiglia di scaricare le release disponibili nel sito ufficiale del progetto (<http://www.openstamanager.com>), evitando di utilizzare direttamente il codice della repository.
Se siete inoltre interessati a supporto e assistenza professionali, li potete richiedere nella [sezione dedicata](http://www.openstamanager.com/per-le-aziende/).

### Github

Nel caso si stia utilizzando la versione direttamente ottenuta dalla repository di Github, è necessario eseguire i seguenti comandi da linea di comando per completare le dipendenze PHP (tramite [Composer](https://getcomposer.org)) e gli asssets (tramite [Yarn](https://yarnpkg.com)) del progetto.

```bash
php composer.phar install
yarn global add gulp
yarn install
gulp
```

In alternativa alla sequenza di comandi precedente, è possibile utilizzare il seguente comando (richiede l'installazione di GIT e Yarn, oltre che l'inserimento dell'archivio `composer.phar` nella cartella principale del progetto):

```bash
yarn run develop-OSM
```

Per ulteriori informazioni, visitare le sezioni [Assets](https://devcode-it.github.io/openstamanager/assets.html) e [Framework](https://devcode-it.github.io/openstamanager/framework.html) della documentazione.

## Perché software open source

Il progetto è un software open source perché permette agli utilizzatori di studiarne il funzionamento ed adattarlo alle proprie esigenze; inoltre, in ambito commerciale, non obbliga l'utilizzatore ad essere legato allo stesso fornitore di assistenza.

In questo modo è possibile ottenere un'ulteriore garanzia sul funzionamento del software, poiché chiunque ne abbia le capacità può verificarlo, escludendo mancanze in relazione alla sicurezza e alla privacy dei dati (caratteristica che il software proprietario non può offrire).

## Community

La community è una componente importante in un progetto open source, perché mette in contatto utenti e programmatori tra di loro e permette pertanto l'individuazione di soluzioni innovative e migliori.

Siamo presenti su [Facebook](https://www.facebook.com/openstamanager), e il nostro forum ufficiale è disponibile all'indirizzo <http://www.openstamanager.com/forum/>, dove potete segnalare i vostri problemi e soddisfare le vostre curiosità nelle sezioni più adeguate:

- [Idee, suggerimenti e consigli](http://www.openstamanager.com/forum/viewforum.php?f=1)
- [Problemi con la prima installazione](http://www.openstamanager.com/forum/viewforum.php?f=2)
- [Sicurezza](http://www.openstamanager.com/forum/viewforum.php?f=3)
- [Altro tipo di assistenza](http://www.openstamanager.com/forum/viewforum.php?f=4)
- [Tutorial](http://www.openstamanager.com/forum/viewforum.php?f=5)

**Attenzione**: vi ricordiamo che non vi è nessuna garanzia che qualcuno risponda in tempo alle vostre richieste o problemi.

## Contribuire

Per poter contribuire, si consiglia di seguire le indicazioni descritte all'interno della [documentazione ufficiale](https://devcode-it.github.io/openstamanager/contribuire.html).

Le impostazione di base per il codice sono disponibili attraverso [editor config](https://github.com/devcode-it/openstamanager/blob/master/.editorconfig) per l'utilizzo semplificato negli editor più comuni.
Maggiori informazioni sulla configurazione e sul plugin sono disponibili nel sito <http://editorconfig.org>.

## Sviluppatori

- **Fabio Lovato**, il fondatore ([loviuz](https://github.com/loviuz))
- **Fabio Piovan** ([fpsoftware](https://github.com/fpsoftware))
- **Luca Salvà** ([lucasalva87](https://github.com/lucasalva87))
- **Matteo Baccarin** ([Bacca1997](https://github.com/Bacca1997))
- **Thomas Zilio** ([Dasc3er](https://github.com/Dasc3er))

## Licenza

Questo progetto è tutelato dalla licenza [**GPL 3**](https://github.com/devcode-it/openstamanager/blob/master/LICENSE).
