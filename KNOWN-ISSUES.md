# Problematiche Note - OpenSTAManager

Questo documento raccoglie e documenta le problematiche del gestionale OpenSTAManager che sono già state identificate e risolte dalla community di sviluppo.

## Struttura del documento

Le problematiche sono organizzate per versione di release in ordine cronologico decrescente (dalla più recente alla più datata). Per ogni problema identificato viene fornito:

- **Descrizione**: Una breve spiegazione del problema riscontrato
- **Commit di risoluzione**: Link diretto al commit GitHub che contiene la correzione del bug

---

#### 2.10.1 - 26/02/2026

##### Problemi noti
- Corretto Unauthenticated privilege escalation (vulnerabilità critica)
https://github.com/devcode-it/openstamanager/commit/3e3ea89a4

- Sanitizzato $_GET['firstuse'] in update.php per prevenire attacchi XSS
https://github.com/devcode-it/openstamanager/commit/44788f51d

- Corretta sanitizzazione per prevenire SQL injection
https://github.com/devcode-it/openstamanager/commit/aa5c4a305

- Corrette vulnerabilità minori
https://github.com/devcode-it/openstamanager/commit/6040c7f4e
https://github.com/devcode-it/openstamanager/commit/fc14a056c

- Corretto carattere speciale in generazione XML
https://github.com/devcode-it/openstamanager/commit/ba6dc3665

- Corretta generazione fattura elettronica per sedi committente paesi esteri
https://github.com/devcode-it/openstamanager/commit/b1a0f2cfe

- Corretto conteggio righe per CIG e CUP in calcolo righe per generazione fattura
https://github.com/devcode-it/openstamanager/commit/b3a4f4380

- Corretta stampa registro IVA note di credito
https://github.com/devcode-it/openstamanager/commit/93b5a2de1

- Corretto calcolo ore totali contratto che non venivano sommate
https://github.com/devcode-it/openstamanager/commit/3aaa39e21

- Corretto errore 500 se display non definito (#1765)
https://github.com/devcode-it/openstamanager/commit/15bececd5

- Corretto riferimento fattura di acquisto nel caso di autofatture
https://github.com/devcode-it/openstamanager/commit/1092859df

- Corretta corrispondenza anagrafica in controllo di integrità XML e documenti di vendita
https://github.com/devcode-it/openstamanager/commit/4af28973e

- Corretta gestione errori in fase di invio email
https://github.com/devcode-it/openstamanager/commit/4f94685fa

- Corretto percorso suggerito per la configurazione del cron, non veniva riportata la root dir
https://github.com/devcode-it/openstamanager/commit/163f88fef

- Corretta esclusione file .env dalla release
https://github.com/devcode-it/openstamanager/commit/38951c002

#### 2.10 - 05/02/2026

##### Problemi noti
- Corretta iva calcolata su ritenuta in tabella riepilogo iva della stampa fattura
https://github.com/devcode-it/openstamanager/commit/d0559c2fd

- Corretta stampa registro iva acquisti per fatture a cavallo dell'anno
https://github.com/devcode-it/openstamanager/commit/b4eaab860

- Corretto calcolo arrotondamento in importazione fattura di acquisto se indicato in maniera errata nell'xml
https://github.com/devcode-it/openstamanager/commit/a4aa0a3d0

- Corretta importazione fatture con tipo documento e data registrazione mancanti in xml
https://github.com/devcode-it/openstamanager/commit/7402d4371

- Corretta prevenzione sql injection
https://github.com/devcode-it/openstamanager/commit/bae00c059

#### 2.9.8 - 23/12/2025

##### Problemi noti
- Corretta la generazione di scadenze relative a fatture con marca da bollo
https://github.com/devcode-it/openstamanager/commit/ee1471442

- Corretto il calcolo della quantità evasa delle righe preventivo per problema in fase di eliminazione fattura creata da preventivo con quantità eccedenti la disponibilità a magazzino
https://github.com/devcode-it/openstamanager/commit/3ae4ff34d

- Corretta la gestione dei seriali in note di credito collegate a fatture
https://github.com/devcode-it/openstamanager/commit/b1636ce0f

- Corretta la gestione dei seriali rientrati a magazzino tramite nota di credito
https://github.com/devcode-it/openstamanager/commit/5deab378f

- Corretta la selezione del tipo documento in fase di generazione nota di credito
https://github.com/devcode-it/openstamanager/commit/0a32be473

#### 2.9.7 - 09/12/2025

##### Problemi noti
- Corretta rivalsa in FE
https://github.com/devcode-it/openstamanager/commit/589901c52

- Corretto Importazione fatture da zip
https://github.com/devcode-it/openstamanager/commit/6d69f9fa8

- Corretti allegati fatture elettroniche
https://github.com/devcode-it/openstamanager/commit/0ef3a71f5

#### 2.9.6 - 26/11/2025

##### Problemi noti
- Corretta gestione assenza file modules.json e views.json
https://github.com/devcode-it/openstamanager/commit/19483b4b5

- Corretta aggiunta record multilingua mancanti a database
https://github.com/devcode-it/openstamanager/commit/499e8e065

#### 2.9.5 - 12/11/2025

##### Problemi noti
- Corretta generazione autofattura per reverse charge
https://github.com/devcode-it/openstamanager/commit/ab87cf4bc

- Corretto carattere non supportato XML
https://github.com/devcode-it/openstamanager/commit/51cfe9606

#### 2.9.4 - 28/10/2025

##### Problemi noti
- Corretta generazione stampe contabili definitive
https://github.com/devcode-it/openstamanager/commit/96b9eac0b

- Corretta generazione query risoluzione problemi database
https://github.com/devcode-it/openstamanager/commit/e98d4b398

#### 2.9.3 - 14/10/2025

##### Problemi noti
- Corretta verifica esigibilità IVA non funzionante correttamente
https://github.com/devcode-it/openstamanager/commit/c72c4386d

- Corrette query installazione che causavano errori durante l'aggiornamento
https://github.com/devcode-it/openstamanager/commit/230d95e30

- Corretto calcolo arrotondamento per fatture elettroniche non corretto
https://github.com/devcode-it/openstamanager/commit/ecdd0fd9d

- Corretto import righe fatture elettroniche con quantità non definita ma prezzo unitario definito
https://github.com/devcode-it/openstamanager/commit/4e003a850

- Corretta gestione XML righe senza quantità che causava errori di importazione
https://github.com/devcode-it/openstamanager/commit/21f91c1c9

#### 2.9.2 - 25/09/2025

##### Problemi noti
- Rimozione record orfani dalle tabelle del database
https://github.com/devcode-it/openstamanager/commit/f115707f8

- Inizializzazione tabelle non corretta in fase di installazione
https://github.com/devcode-it/openstamanager/commit/5220c2693

- Query installazione causavano errori durante l'aggiornamento
https://github.com/devcode-it/openstamanager/commit/21c331268

- Importazione sequenziale fatture di acquisto non funzionava
https://github.com/devcode-it/openstamanager/commit/8633f354f

#### 2.9.1 - 08/09/2025

##### Problemi noti
- Inizializzazione stampa barcode causava errori
https://github.com/devcode-it/openstamanager/commit/f13db1cf8

- Import righe con quantità a 0 non gestito correttamente
https://github.com/devcode-it/openstamanager/commit/96f98c5d4

- Query di installazione causavano errori
https://github.com/devcode-it/openstamanager/commit/3e1b54c4f

- Caricamento altre operazioni stato dei servizi causava errori
https://github.com/devcode-it/openstamanager/commit/47c9bb18f

#### 2.9-beta - 08/08/2025

##### Problemi noti
- Gestione note di credito nell'esportazione bonifici XML, venivano sommate invece di sottratte
https://github.com/devcode-it/openstamanager/commit/e221a4736

#### 2.8.3 - 30/07/2025

##### Problemi noti
- Query installazione causavano errori durante l'aggiornamento
https://github.com/devcode-it/openstamanager/commit/d3d33c283

- Ricalcolo IVA su sconto righe non corretto
https://github.com/devcode-it/openstamanager/commit/43a9e9bae

- Creazione autofattura causava errori
https://github.com/devcode-it/openstamanager/commit/fb50c0482

- Query di installazione multiple con errori
https://github.com/devcode-it/openstamanager/commit/da5e4cf3e

- Importazione fatture da zip non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/802611044

- Creazione intervento non funzionava in alcuni casi
https://github.com/devcode-it/openstamanager/commit/7f5ce275f

#### 2.8.2 - 08/07/2025

##### Problemi noti
- Stampa fattura con sconto finale percentuale non corretta
https://github.com/devcode-it/openstamanager/commit/a90411ca7

- Stampa fattura con sconto in fattura non corretta
https://github.com/devcode-it/openstamanager/commit/7be63d2ee

- Importazione sequenziale fatture di acquisto non funzionava
https://github.com/devcode-it/openstamanager/commit/8633f354f

- Calcolo data prossima esecuzione task non corretto
https://github.com/devcode-it/openstamanager/commit/d8b4429bd

- Risolta inaggiornabilità/installabilità moduli con templates
https://github.com/devcode-it/openstamanager/commit/fd7f020d4

#### 2.8.1 - 10/06/2025

##### Problemi noti
- Sovrascrittura vendor in fase di installazione causava errori
https://github.com/devcode-it/openstamanager/commit/7a5b4d81b

- Retrofix query installazione per compatibilità
https://github.com/devcode-it/openstamanager/commit/873c9b813

- Compatibilità PHP8 non completa
https://github.com/devcode-it/openstamanager/commit/66e519670

#### 2.8-beta - 20/05/2025

##### Problemi noti
- La cache degli hook veniva considerata scaduta fino a quando non trascorreva un intero giorno, causando l'esecuzione continua di alcuni hook per aggiornare la cache.
Questa modifica corregge la logica di validazione della cache, riducendo le chiamate non necessarie e migliorando le prestazioni.
https://github.com/devcode-it/openstamanager/commit/254a00c9c265c990ee708141455d30b9080132ff

- Presenza di vecchi file nella cartella vendor che danno errore in fase di installazione
https://github.com/devcode-it/openstamanager/commit/9d7120319

#### 2.7.3 - 15/04/2025

##### Problemi noti
- Registrazione delle fatture di acquisto con split payment
https://github.com/devcode-it/openstamanager/commit/587b6c6b8958b426ebe7c762bea20aa46571cef7

- Generazione di autofatture in caso di IVA indetraibile
https://github.com/devcode-it/openstamanager/commit/c69bb1f40791a623278a477934f647dfb8e8ca66

- Inizializzazione di reverse charge e autofatture per le fatture
https://github.com/devcode-it/openstamanager/commit/e6dae1a8a1d954448b04eda286927a2dda9af1f4

#### 2.5.6 - 31/10/2024

##### Problemi noti
- Registrazioni contabili con conto impostato Riepilogativo clienti o Riepilogativo fornitori
https://github.com/devcode-it/openstamanager/commit/37d8c7c34fc4e81f3a4f2f79c180365b7d447891

#### 2.5.3 - 07/08/2024

##### Problemi noti
- Non è possibile modificare la descrizione di una riga articolo inserita in un contratto, ddt, attività, ordine e preventivo.
https://github.com/devcode-it/openstamanager/commit/b82efb339f8df5da4f2279e25d72904778d2a8d3

- La ricerca globale non funziona.
https://github.com/devcode-it/openstamanager/commit/5c86d3b7489431b2e8001841b07769cd26e4c24c

#### 2.4.54 - 03/02/2024

##### Problemi noti
- In fase di installazione non viene compilato il file config se assente

##### Soluzione
Modificare il file index.php sostituendo il blocco di codice che inizia alla riga 30 con

```php
if ($dbo->isConnected()) {
    try {
        $microsoft = $dbo->selectOne('zz_oauth2', '*', ['nome' => 'Microsoft', 'enabled' => 1, 'is_login' => 1]);
    } catch (QueryException $e) {
    }
}
```
oppure aggiornare alla **v.2.5** di OpenSTAManager.

#### 2.4.35 - 12/08/2022

##### Problemi noti
- Colonna **id_module_start** mancante per tabella **zz_groups**
- Icona non aggiornata per il modulo **Causali movimenti**

##### Soluzione
Eseguire a database le seguenti query di allineamento:
```bash
UPDATE `zz_modules` SET `icon` = 'fa fa-exchange'  WHERE `title` = 'Causali movimenti';
ALTER TABLE `zz_groups` ADD `id_module_start` INT NULL AFTER `editable`;
```

oppure aggiornare alla **v.2.5** di OpenSTAManager.
