In questo file verranno riassunte le problematiche del gestionale che sono già note alla community.
Le problematiche saranno raggruppate per release e le relative correzioni (se applicabili) saranno riportate sotto la sezione **Soluzione**.


#### 2.4.35 - 12/08/2022

##### Problemi noti
- Colonna **id_module_start** mancante per tabella **zz_groups**
- Icona non aggiornata per il modulo **Causali movimenti**

##### Soluzione 
Eseguire a database le seguenti query di allineamento:
- UPDATE `zz_modules` SET `icon` = 'fa fa-exchange'  WHERE `id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Causali movimenti'); 
- ALTER TABLE `zz_groups` ADD `id_module_start` INT NULL AFTER `editable`;

oppure aggiornare alla **v.2.4.55** di OpenSTAManager.