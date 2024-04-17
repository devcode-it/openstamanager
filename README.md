<p align="center">
  <a href="https://openstamanager.com">
    <img src="https://shop.openstamanager.com/wp-content/uploads/2015/04/logo_full-2.png">
  </a>

  <p align="center">
    Il software gestionale open-source per l'assistenza tecnica e la fatturazione.
    <br>
    <br>
    <a href="https://www.openstamanager.com">Sito web</a>
    &middot;
    <a href="https://docs.openstamanager.com/">Documentazione</a>
    &middot;
    <a href="https://forum.openstamanager.com">Forum</a>
  </p>
</p>

<br>

[![GitHub release](https://img.shields.io/github/release/devcode-it/openstamanager/all.svg)](https://github.com/devcode-it/openstamanager/releases)
[![Downloads](https://img.shields.io/github/downloads/devcode-it/openstamanager/total.svg)](https://github.com/devcode-it/openstamanager/releases)
[![SourceForge](https://img.shields.io/sourceforge/dt/openstamanager.svg?label=SourceForge)](https://sourceforge.net/projects/openstamanager/)
[![license](https://img.shields.io/github/license/devcode-it/openstamanager.svg)](https://github.com/devcode-it/openstamanager/blob/master/LICENSE)
[![huntr](https://cdn.huntr.dev/huntr_security_badge_mono.svg)](https://huntr.dev)

Il gestionale OpenSTAManager è un software open-source e web based, sviluppato dall'azienda informatica [DevCode](https://www.devcode.it/) di Este per gestire ed archiviare il servizio di assistenza tecnica e la relativa fatturazione.
Il nome del progetto deriva dalla parziale traduzione in inglese degli elementi principali che lo compongono: la natura open-source e il suo obiettivo quale Gestore del Servizio Tecnico di Assistenza.

Un software gestionale, identificato nell'insieme degli applicativi che automatizzano i processi di gestione all'interno delle aziende, appartiene solitamente a una specifica categoria del settore, specializzata negli ambiti di:

- Gestione della contabilità;
- Gestione del magazzino;
- Gestione e ausilio della produzione;
- Gestione e previsione dei budget aziendali;
- Gestione ed analisi finanziaria.

Secondo questa definizione, OpenSTAManager riesce a generalizzare al proprio interno le funzionalità caratteristiche della contabilità e della gestione del magazzino, presentando inoltre moduli piuttosto avanzati e destinati a complementare l'attività aziendale in relazione agli interventi di assistenza della realtà lavorativa in oggetto.

La documentazione ufficiale è disponibile all'indirizzo <https://docs.openstamanager.com/>.

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Requisiti](#requisiti)
- [Installazione](#installazione)
    - [Versioni](#versioni)
    - [Build](#build)
    - [Strumenti di sviluppo e debug](#strumenti-di-sviluppo-e-debug)
- [Perché software open-source](#perché-software-open-source)
- [Community](#community)
- [Contribuire](#contribuire)
- [Sviluppatori](#sviluppatori)
- [Licenza](#licenza)

<!-- /TOC -->

## Requisiti software

L'installazione del gestionale richiede la presenza di un server web con abilitato il [DBMS MySQL](https://www.mysql.com)  e il linguaggio di programmazione [PHP](https://php.net).

<table>
<tr>
<td valign="top">
    
| PHP | EOL | Supportato |
|-----|-----|:----------:|
| 8.1 | 25/11/2024 | ✔️ |
| 8.0 | 26/11/2023 | ✔️ |
| 7.4 | 28/11/2022 | ✔️ |
| 7.3 | 06/12/2021 | ✔️ |
| 7.2 | 30/11/2020 | ❌ |
    
</td>
<td valign="top">
    
| MYSQL | EOL | Supportato |
|-----|-----|:----------:|
| 8.3 | 30/04/2024 | ✔️ |
| 8.2 | 31/01/2024 | ✔️ |
| 8.1 | 25/10/2023 | ✔️ |
| 8.0 | 30/04/2026 | ✔️ |
| 5.7 | 21/10/2023 | ✔️ |
| 5.6 | 05/02/2021 | ❌ |

    
</td>
</tr>
</table>

Per ulteriori informazioni sui pacchetti che forniscono questi elementi di default, visitare la sezione [Installazione](https://docs.openstamanager.com/configurazione/installazione) della documentazione.

### Requisiti hardware

Minimi:
- 1 CPU
- 2GB di ram
- 200MB di spazio per il gestionale

Consigliati:
- 2 CPU
- 4GB di ram
- 2GB di spazio per il gestionale

## Installazione rapida
```bash
git clone https://github.com/devcode-it/openstamanager.git
cd openstamanager

# Download di composer da https://getcomposer.org/download/

yarn develop-OSM
```


## Installazione

Per procedere all'installazione è necessario seguire i seguenti punti:

1. [Scaricare una release ufficiale del progetto](https://github.com/devcode-it/openstamanager/releases).
2. Creare una cartella (ad esempio `openstamanager`) nella root del server web installato ed estrarvi il contenuto della release scaricata. Il percorso della cartella root del server varia in base al software in utilizzo:

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

Per mantenere un elevato grado di trasparenza riguardo al ciclo delle release, seguiamo le linee guida [Semantic Versioning (SemVer)](https://semver.org/) per definire le versioni del progetto.
Per vedere tutte le versioni disponibili al download, visitare la [pagina relativa](https://github.com/devcode-it/openstamanager/releases) su GitHub (per versioni precedenti alla 2.3, visitare [SourceForge](https://sourceforge.net/projects/openstamanager/files)).

Nel caso utilizziate il programma per uso commerciale, si consiglia di scaricare le release disponibili nel sito ufficiale del progetto (<https://www.openstamanager.com>), evitando di utilizzare direttamente il codice della repository.
Se siete inoltre interessati a supporto e assistenza professionali, li potete richiedere nella [sezione dedicata](https://www.openstamanager.com/per-le-aziende/).

### Build

Nel caso si stia utilizzando la versione direttamente ottenuta dalla repository di GitHub, è necessario eseguire i seguenti comandi da linea di comando per completare le dipendenze PHP (tramite [Composer](https://getcomposer.org)) e gli assets (tramite [Yarn](https://yarnpkg.com)) del progetto.

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

Per ulteriori informazioni, visitare le sezioni [Assets](https://docs.openstamanager.com/docs/base/assets) e [Framework](https://docs.openstamanager.com/docs/base/framework) della documentazione.

## Strumenti di sviluppo e debug

Consigliamo di installare [psalm](https://github.com/vimeo/psalm) e configurarlo nel proprio IDE se supportato, in modo che vengano eseguiti ulteriori controlli automatici sul codice scritto.

E' già configurato su **composer** l'inclusione di [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer), uno strumento che permette di formattare in modo uniforme il codice scritto. Si può configurare nel proprio IDE se supportato. Il percorso dell'eseguibile è `vendor/bin/php-cs-fixer`.

## Perché software open-source

Il progetto è un software open-source perché permette agli utilizzatori di studiarne il funzionamento ed adattarlo alle proprie esigenze; inoltre, in ambito commerciale, non obbliga l'utilizzatore ad essere legato allo stesso fornitore di assistenza.

In questo modo è possibile ottenere un'ulteriore garanzia sul funzionamento del software, poiché chiunque ne abbia le capacità può verificarlo, escludendo mancanze in relazione alla sicurezza e alla privacy dei dati (caratteristica che il software proprietario non può offrire).

## Community

La community è una componente importante in un progetto open-source, perché mette in contatto utenti e programmatori tra di loro e permette pertanto l'individuazione di soluzioni innovative e migliori.

Siamo presenti su [Facebook](https://www.facebook.com/openstamanager), [Twitter](https://twitter.com/openstamanager/), [YouTube](https://www.youtube.com/channel/UCoToaK4dhDXmcQXi1AnqQ4Q) e [Mastodon](https://mastodon.uno/@openstamanager) e il nostro forum ufficiale è disponibile all'indirizzo <https://forum.openstamanager.com>, dove potete segnalare i vostri problemi e soddisfare le vostre curiosità nelle sezioni più adeguate.

## Contribuire

Per poter contribuire ed eseguire i test automatici, si consiglia di seguire le indicazioni descritte all'interno della [documentazione ufficiale](https://github.com/devcode-it/openstamanager/blob/master/.github/CONTRIBUTING.md).

Se volete contribuire attivamente con semplici migliorie o correzioni potete cercare tra le [issue per i nuovi contributori](https://github.com/devcode-it/openstamanager/issues?q=is%3Aissue+is%3Aopen+label%3A%22nuovi+contributori%22).

## Bounty Program

Potresti trovare degli [issue contrassegnati con il label **bountysource**](https://github.com/devcode-it/openstamanager/issues?q=is%3Aopen+is%3Aissue+label%3A%22bountysource%22). Questo significa che è disponibile una ricompensa per chi risolverà l'issue secondo i criteri descritti all'interno. Devi creare innanzitutto un account su [BountySource.com](https://bountysource.com). Successivamente, il funzionamento sarà il seguente:
- esegui un fork del repository
- risolvi l'issue
- invia una pull request

Verificheremo se la pull request soddisferà i requisiti e, una volta approvata, provvederemo ad accreditare la ricompensa nel tuo account di BountySource.

## Licenza

Questo progetto è tutelato dalla licenza [**GPL 3**](https://github.com/devcode-it/openstamanager/blob/master/LICENSE).
