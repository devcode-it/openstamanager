# Problematiche Note - OpenSTAManager

Questo documento raccoglie e documenta le problematiche del gestionale OpenSTAManager che sono già state identificate e risolte dalla community di sviluppo.

## Struttura del documento

Le problematiche sono organizzate per versione di release in ordine cronologico decrescente (dalla più recente alla più datata). Per ogni problema identificato viene fornito:

- **Descrizione**: Una breve spiegazione del problema riscontrato
- **Commit di risoluzione**: Link diretto al commit GitHub che contiene la correzione del bug

---


#### 2.7.3 - 15/04/2025

##### Problemi noti
- Associazione dell'articolo in importazione di una fattura elettronica
https://github.com/devcode-it/openstamanager/commit/95679e3dd96f0e87f3ec315e7f3cfa8e2e4ce05f

- Visualizzazione della tabella del plugin Serial in Articoli
https://github.com/devcode-it/openstamanager/commit/684783cc1219b573e2f3e38023f0dbd9458b19da

- Funzione di cambio stato della Newsletter
https://github.com/devcode-it/openstamanager/commit/b79aada8ed3c607d44cc3fd35001383fc99ba5e9

- Salvataggio del pagamento
https://github.com/devcode-it/openstamanager/commit/33e72b5f6aa205e0dafd483b10c598e485d0631d

- Spostamento degli allegati di fatture di acquisto e vendita
https://github.com/devcode-it/openstamanager/commit/9093ff7bd8d4ac56658523d532068a611d0e868f

- Duplicazione del preventivo
https://github.com/devcode-it/openstamanager/commit/84ebf0044b86c6bad6a7d7ea38cb9ef6306d7741

- Registrazione delle fatture di acquisto con split payment
https://github.com/devcode-it/openstamanager/commit/587b6c6b8958b426ebe7c762bea20aa46571cef7

- Avviso per fatture doppie per anno in fatture di acquisto
https://github.com/devcode-it/openstamanager/commit/61c50ea71f52d52b6553b0737afa87f6178ccce2

- Visualizzazione del nome articolo in Automezzi
https://github.com/devcode-it/openstamanager/commit/2e892e9b58096c2d4773adbde78dd2c2dbde3538

- Ricerca delle impostazioni
https://github.com/devcode-it/openstamanager/commit/89602045c9ccca55fb0fdeacd9fa73ae38ec00e7

- Generazione di presentazioni bancarie raggruppate per scadenza
https://github.com/devcode-it/openstamanager/commit/2f390067f7864c6cf98d210c4a497b6d64154cc5

- Generazione di autofatture in caso di IVA indetraibile
https://github.com/devcode-it/openstamanager/commit/c69bb1f40791a623278a477934f647dfb8e8ca66

- Blocco dei fornitori in base alla relazione
https://github.com/devcode-it/openstamanager/commit/b34667a7f0e59f83139492a4638755e316946619

- Inizializzazione di reverse charge e autofatture per le fatture
https://github.com/devcode-it/openstamanager/commit/e6dae1a8a1d954448b04eda286927a2dda9af1f4

#### 2.5.6 - 31/10/2024

##### Problemi noti
- registrazioni contabili con conto impostato Riepilogativo clienti o Riepilogativo fornitori
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
