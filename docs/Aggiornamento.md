---
currentMenu: aggiornamento
---

# Aggiornamento

**Attenzione**: Questa documentazione è esclusivamente relativa all'aggiornamento del software. Per maggiori informazioni sull'installazione consultare la documentazione relativa nella sezione [Installazione](Installazione.md).

Esistono due procedure ufficiale per effettuare l'aggiornamento di OpenSTAManager in modo corretto: una semplificata (_consigliata_) e una manuale.

In ogni caso, il corretto procedimento prevede di [scaricare una release ufficiale del progetto](https://github.com/devcode-it/openstamanager/releases) ed **effettuare un backup della versione corrente** (comprensivo di file e database).

- [Aggiornamento semplificato](#aggiornamento-semplificato)
- [Aggiornamento manuale](#aggiornamento-manuale)
- [Migrazione dalla versione 1.x](#migrazione-dalla-versione-1x)
- [Reimpostare la password di admin](#reimpostare-la-password-di-admin)

## Aggiornamento semplificato

La procedura di aggiornamento semplificato ha l'obiettivo di fornire un sistema di facile utilizzo per favorire l'aggiornamento, e migliorare in questo modo l'interazione con l'utente finale.

L'utilizzo di questa procedura è però sottoposto alla seguenti condizioni nelle impostazioni PHP:
- upload_max_filesize >= 16MB
- post_max_size >= 16MB

Di seguito la procedura:
1. Accedere con un account amministrativo
2. Entrare nel modulo **Aggiornamenti** (disponibile nel menu principale a sinistra, eventualmente sotto la dicitura **Strumenti**)
3. Selezionare il file _.zip_ della release attraverso l'apposita sezione "Carica un aggiornamento" e cliccare sul pulsante "Carica"

Dopo l'esecuzione di queste azioni, il gestionale effettuerà automaticamente il logout di tutti gli utenti connessi e renderà disponibile l'interfaccia di aggiornamento.

## Aggiornamento manuale

La procedura di aggiornamento manuale è resa disponibile per ovviare ai problemi relativi al caricamento del file _.zip_ (in alcuni casi il file non viene correttamente rilevato, non sono disponibili i permessi per caricare file oppure la dimensione del file eccede il limite di upload sul server).

Di seguito la procedura:
1. De-comprimere il contenuto del file _.zip_ in una cartella temporanea
2. Rinominare il file VERSION dell'installazione corrente in VERSION.old (rispettando minuscole e maiuscole) [facoltativo a partire dalla versione 2.3]
3. Copiare i file della nuova versione dalla cartella temporanea alla cartella del server, in modo che le cartelle principali (`files`, `modules`, `templates`, ...) vengano sovrascritte

Dopo l'esecuzione di queste azioni, il gestionale effettuerà automaticamente il logout di tutti gli utenti connessi e renderà disponibile l'interfaccia di aggiornamento.

## Migrazione dalla versione 1.x

E' possibile effettuare la migrazione da una qualsiasi versione 1.x alla nuova 2.0,  seguendo una procedura un po’ diversa dalle precedenti:

1. Scaricare la versione 2.0 per la migrazione da SourceForge ([openstamanager-2.0-migrazione.zip](https://sourceforge.net/projects/openstamanager/files/openstamanager/openstamanager-2.x/))
2. Creare un backup completo della versione in uso
1. De-comprimere il contenuto del file _.zip_ in una cartella temporanea
4. Effettuare le seguenti operazioni dal backup della precedente versione alla cartella della versione 2.0:
    - Copiare il file `VERSION`, rinominandolo in `VERSION.old`
    - Copiare il file `config.inc.php`
    - Copiare la cartella `files/`
    - Copiare i contenuti della cartella `/modules/magazzino/articoli/images/` in `/files/articoli/`
    - Copiare la cartella `templates/` (mantenendo però i file `pdfgen.php` e `pdfgen_variables.php` di della versione 2.0)

Dopo l'esecuzione di queste azioni, il gestionale renderà disponibile l'interfaccia di aggiornamento.

**Attenzione**: le stampe di _Interventi_, _Riepilogo interventi_, _Contratti_ e _Preventivi_ potrebbero non essere compatibili per via dell’aggiornamento degli orari di lavoro, perciò è possibile riscrivere solo la parte di calcolo ore o partire dal template nuovo e apportare le dovute modifiche.

## Reimpostare la password di admin

Non esiste una procedura semplificata per permettere la reimpostazione o il recupero della password dell'account di amministrazione di default (_admin_).

Può però essere necessario procedere alla sua reimpostazione, sia perché l'account _admin_ viene utilizzato da più persone o perché è stata dimenticata.
In questi casi, per procedere è necessario accedere al database ed eseguire la seguente query:
```sql
UPDATE `zz_utenti` SET password = MD5('nuova_password') WHERE username = 'admin';
```

Si ricorda che è comunque possibile cambiare la password in ogni momento, se è stato effettuato l'accesso, attraverso l'utilizzo del modulo **Utenti e permessi**.
