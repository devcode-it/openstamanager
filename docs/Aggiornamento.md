---
currentMenu: aggiornamento
---

# Aggiornamento

**Attenzione**: Questa documentazione è esclusivamente relativa all'aggiornamento del software. Per maggiori informazioni sull'installazione consultare la documentazione relativa nella sezione [Installazione](Installazione.md).

Esistono due procedure ufficiale per effettuare l'aggiornamento di OpenSTAManager in modo corretto: una semplificata (_consigliata_) e una manuale.

In ogni caso, il corretto procedimento prevede di [scaricare una release ufficiale del progetto](https://github.com/devcode-it/openstamanager/releases) ed **effettuare un backup della versione corrente** (comprensivo di file e database).

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Aggiornamento semplificato](#aggiornamento-semplificato)
- [Aggiornamento manuale](#aggiornamento-manuale)
- [Migrazione dalla versione 1.x](#migrazione-dalla-versione-1x)
- [Recupero della password](#recupero-della-password)
    - [Account comune](#account-comune)
    - [Account amministrativo](#account-amministrativo)

<!-- /TOC -->

## Aggiornamento semplificato

La procedura di aggiornamento semplificato ha l'obiettivo di fornire un sistema di facile utilizzo per favorire l'aggiornamento, e migliorare in questo modo l'interazione con l'utente finale.

L'utilizzo di questa procedura è però sottoposto alla seguenti condizioni nelle impostazioni PHP:
- `upload_max_filesize` >= 16MB
- `post_max_size` >= 16MB

Di seguito la procedura:
1. Accedere con un account amministrativo
2. Entrare nel modulo **Aggiornamenti** (disponibile nel menu principale a sinistra, eventualmente sotto la dicitura **Strumenti**)
3. Selezionare il file _.zip_ della release attraverso l'apposita sezione "Carica un aggiornamento" e cliccare sul pulsante "Carica"

Dopo l'esecuzione di queste azioni, il gestionale effettuerà automaticamente il logout di tutti gli utenti connessi e renderà disponibile l'interfaccia di aggiornamento.

## Aggiornamento manuale

La procedura di aggiornamento manuale è resa disponibile per ovviare ai problemi relativi al caricamento del file _.zip_ (in alcuni casi il file non viene correttamente rilevato, non sono disponibili i permessi per caricare file oppure la dimensione del file eccede il limite di upload sul server).

Di seguito la procedura:
1. De-comprimere il contenuto del file _.zip_ in una cartella temporanea
2. Rinominare il file `VERSION` dell'installazione corrente in `VERSION.old` (rispettando minuscole e maiuscole) [facoltativo a partire dalla versione 2.3]
3. Copiare i file della nuova versione dalla cartella temporanea alla cartella del server, in modo che le cartelle principali (`files`, `modules`, `templates`, ...) vengano sovrascritte

Dopo l'esecuzione di queste azioni, il gestionale effettuerà automaticamente il logout di tutti gli utenti connessi e renderà disponibile l'interfaccia di aggiornamento.

## Migrazione dalla versione 1.x

E' possibile effettuare la migrazione da una qualsiasi versione 1.x alla nuova 2.0, seguendo una procedura un po’ diversa dalle precedenti:

1. Scaricare la versione 2.0 per la migrazione da SourceForge ([openstamanager-2.0-migrazione.zip](https://sourceforge.net/projects/openstamanager/files/openstamanager/openstamanager-2.x/))
2. Creare un backup completo della versione in uso
1. De-comprimere il contenuto del file _.zip_ in una cartella temporanea
4. Effettuare le seguenti operazioni dal backup della precedente versione alla cartella della versione 2.0:
    - Copiare il file `VERSION`, rinominandolo in `VERSION.old`
    - Copiare il file `config.inc.php`
    - Copiare la cartella `files/`
    - Copiare i contenuti della cartella `/modules/magazzino/articoli/images/` in `/files/articoli/`
    - Copiare la cartella `templates/` (mantenendo però i file `pdfgen.php` e `pdfgen_variables.php` della versione 2.0)

Dopo l'esecuzione di queste azioni, il gestionale renderà disponibile l'interfaccia di aggiornamento.

**Attenzione**: le stampe di _Interventi_, _Riepilogo interventi_, _Contratti_ e _Preventivi_ potrebbero non essere compatibili per via dell’aggiornamento degli orari di lavoro, perciò è possibile riscrivere solo la parte di calcolo ore o partire dal template nuovo e apportare le dovute modifiche.

## Recupero della password

Non esiste una procedura semplificata per permettere il recupero della password degli account di amministrazione (di default, _admin_) o di quelli comuni.
Si ricorda che è comunque possibile **cambiare** la password in ogni momento, se è stato effettuato l'accesso, attraverso l'utilizzo del modulo **Utenti e permessi** (**Gestione permessi** per versioni precedenti alla 2.3) disponibile sotto la dicitura **Strumenti**.

Può però essere necessario **reimpostare** la password, in particolare se è stata dimenticata, per ripristinare l'accesso ad OpenSTAManager.

### Account comune

Per procedere alla reimpostazione della password di un account comune (non amministrativo) è necessario accedere con un account amministrativo e utilizzare il modulo **Utenti e permessi** (**Gestione permessi** per versioni precedenti alla 2.3), disponibile sotto la dicitura **Strumenti**.
In particolare, una volta entrati nella corretta categoria di accesso (_Agenti_, _Amministratori_, _Clienti_, ...) dell'account da modificare, è possibile utilizzare la procedura semplificata di cambio password attraverso l'_icona del lucchetto aperto_.

Nel caso non sia possibile accedere con un account amministrativo, contattare l'amministratore.

### Account amministrativo

Per reimpostare la password di un account amministrativo è possibile procedere in due modi:
- Se esiste un altro account amministrativo, seguire la procedura precedente per gli account comuni;
- Accedere al database ed eseguire la seguente query:
    ```sql
    UPDATE `zz_users` SET `password` = MD5('nuova_password') WHERE `username` = 'admin';
    ```
