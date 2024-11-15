In questo file verranno riassunte le problematiche del gestionale che sono già note alla community.
Le problematiche saranno raggruppate per release e le relative correzioni (se applicabili) saranno riportate sotto la sezione **Soluzione**.

#### 2.5.6 - 31/10/2024

##### Problemi noti
- registrazioni contabili con conto impostato Riepilogativo clienti o Riepilogativo fornitori

##### Soluzione
Aggiornare alla versione 2.6 o eseguire lo script: https://github.com/devcode-it/openstamanager/commit/37d8c7c34fc4e81f3a4f2f79c180365b7d447891


#### 2.5.3 - 07/08/2024

##### Problemi noti
- Non è possibile modificare la descrizione di una riga articolo inserita in un contratto, ddt, attività, ordine e preventivo.

##### Soluzione
https://github.com/devcode-it/openstamanager/commit/b82efb339f8df5da4f2279e25d72904778d2a8d3


- La ricerca globale non funziona.

##### Soluzione
https://github.com/devcode-it/openstamanager/commit/5c86d3b7489431b2e8001841b07769cd26e4c24c

Per applicare le modifiche è necessario ricompilare gli assets

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
