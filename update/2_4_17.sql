-- Aggiunta data competenza nel filtro temporale per le fatture di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN (
        SELECT `iddocumento`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
WHERE 1=1 AND `dir` = \'uscita\' |segment(`co_documenti`.`id_segment`)||date_period(custom, \'|period_start|\' <= `co_documenti`.`data` AND \'|period_end|\' >= `co_documenti`.`data`, \'|period_start|\' <= `co_documenti`.`data_competenza` AND \'|period_end|\' >= `co_documenti`.`data_competenza`  )|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero` = \'\', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC' WHERE `name` = 'Fatture di acquisto';


-- Allineo per i movimenti relativi alle fatture di vendita, la data del movimento con la data del documento
UPDATE `co_movimenti` SET `co_movimenti`.`data` = `co_movimenti`.`data_documento` WHERE `iddocumento` IN (SELECT `co_documenti`.`id` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE `co_tipidocumento`.`dir` = 'entrata' );

-- Allineo per le fatture di vendita, la data_competenza con data emissione del documento
UPDATE `co_documenti` SET `co_documenti`.`data_competenza` = `co_documenti`.`data`  WHERE `co_documenti`.`idtipodocumento` IN (SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir` = 'entrata');

-- Elimino data_documento per co_documenti
ALTER TABLE `co_movimenti` DROP `data_documento`;

-- Allineamento idarticolo nelle tabelle delle righe
ALTER TABLE `co_righe_documenti` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_documenti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_preventivi` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_preventivi` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_contratti` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_contratti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `dt_righe_ddt` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `dt_righe_ddt` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `or_righe_ordini` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `or_righe_ordini` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `or_righe_ordini` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `or_righe_ordini` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_promemoria` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_promemoria` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

-- Fix link del plugin "Ddt del cliente"
UPDATE `zz_plugins` SET `options` = '{ "main_query": [ { "type": "table", "fields": "Numero, Data, Descrizione, Qtà", "query": "SELECT dt_ddt.id, IF(dt_tipiddt.dir = \'entrata\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di vendita\'), (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di acquisto\')) AS _link_module_, dt_ddt.id AS _link_record_, IF(dt_ddt.numero_esterno = \'\', dt_ddt.numero, dt_ddt.numero_esterno) AS Numero, DATE_FORMAT(dt_ddt.data, \'%d/%m/%Y\') AS Data, dt_righe_ddt.descrizione AS `Descrizione`, REPLACE(REPLACE(REPLACE(FORMAT(dt_righe_ddt.qta, 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\') AS `Qtà` FROM dt_ddt LEFT JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt JOIN dt_tipiddt ON dt_ddt.idtipoddt = dt_tipiddt.id WHERE dt_ddt.idanagrafica=|id_parent| GROUP BY dt_ddt.id HAVING 2=2 ORDER BY dt_ddt.id DESC"} ]}' WHERE `zz_plugins`.`name` = 'Ddt del cliente';

-- Aggiunta risorse API dedicate all'applicazione
DELETE FROM `zz_api_resources` WHERE `version` = 'app-v1';
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
-- Login
(NULL, 'app-v1', 'create', 'login', 'API\\App\\v1\\Login', '1'),
-- Clienti
(NULL, 'app-v1', 'retrieve', 'clienti', 'API\\App\\v1\\Clienti', '1'),
(NULL, 'app-v1', 'retrieve', 'clienti-cleanup', 'API\\App\\v1\\Clienti', '1'),
(NULL, 'app-v1', 'retrieve', 'cliente', 'API\\App\\v1\\Clienti', '1'),
-- Tecnici
(NULL, 'app-v1', 'retrieve', 'tecnici', 'API\\App\\v1\\Tecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tecnici-cleanup', 'API\\App\\v1\\Tecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tecnico', 'API\\App\\v1\\Tecnici', '1'),
-- Sedi
(NULL, 'app-v1', 'retrieve', 'sedi', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'sedi-cleanup', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'sede', 'API\\App\\v1\\Sedi', '1'),
-- Referenti
(NULL, 'app-v1', 'retrieve', 'referenti', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'referenti-cleanup', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'referente', 'API\\App\\v1\\Referenti', '1'),
-- Impianti
(NULL, 'app-v1', 'retrieve', 'impianti', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianti-cleanup', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianto', 'API\\App\\v1\\Impianti', '1'),
-- Stati degli interventi
(NULL, 'app-v1', 'retrieve', 'stati-intervento', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'stati-intervento-cleanup', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'stato-intervento', 'API\\App\\v1\\StatiIntervento', '1'),
-- Tipi degli interventi
(NULL, 'app-v1', 'retrieve', 'tipi-intervento', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipi-intervento-cleanup', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipo-intervento', 'API\\App\\v1\\TipiIntervento', '1'),
-- Articoli
(NULL, 'app-v1', 'retrieve', 'articoli', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'articoli-cleanup', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'articolo', 'API\\App\\v1\\Articoli', '1'),
-- Interventi
(NULL, 'app-v1', 'retrieve', 'interventi', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'interventi-cleanup', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'create', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'update', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'delete', 'intervento', 'API\\App\\v1\\Interventi', '1'),
-- Sessioni degli interventi
(NULL, 'app-v1', 'retrieve', 'sessioni', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'sessioni-cleanup', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'delete', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'create', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'update', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
-- Righe degli interventi
(NULL, 'app-v1', 'retrieve', 'righe-interventi', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'righe-interventi-cleanup', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'create', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'update', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'delete', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
-- Aliquote IVA
(NULL, 'app-v1', 'retrieve', 'aliquote-iva', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquote-iva-cleanup', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquota-iva', 'API\\App\\v1\\AliquoteIva', '1'),
-- Impostazioni (non modificabili)
(NULL, 'app-v1', 'retrieve', 'impostazioni', 'API\\App\\v1\\Impostazioni', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazioni-cleanup', 'API\\App\\v1\\Impostazioni', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazione', 'API\\App\\v1\\Impostazioni', '1'),
-- Contratti
(NULL, 'app-v1', 'retrieve', 'contratti', 'API\\App\\v1\\Contratti', '1'),
(NULL, 'app-v1', 'retrieve', 'contratti-cleanup', 'API\\App\\v1\\Contratti', '1'),
(NULL, 'app-v1', 'retrieve', 'contratto', 'API\\App\\v1\\Contratti', '1'),
-- Preventivi
(NULL, 'app-v1', 'retrieve', 'preventivi', 'API\\App\\v1\\Preventivi', '1'),
(NULL, 'app-v1', 'retrieve', 'preventivi-cleanup', 'API\\App\\v1\\Preventivi', '1'),
(NULL, 'app-v1', 'retrieve', 'preventivo', 'API\\App\\v1\\Preventivi', '1'),
-- Sedi
(NULL, 'app-v1', 'retrieve', 'preventivi', 'API\\App\\v1\\Preventivi', '1'),
(NULL, 'app-v1', 'retrieve', 'preventivi-cleanup', 'API\\App\\v1\\Preventivi', '1'),
(NULL, 'app-v1', 'retrieve', 'preventivo', 'API\\App\\v1\\Preventivi', '1'),
-- Tariffe dei tecnici
(NULL, 'app-v1', 'retrieve', 'tariffe-tecnici', 'API\\App\\v1\\TariffeTecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffe-tecnici-cleanup', 'API\\App\\v1\\TariffeTecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffa-tecnico', 'API\\App\\v1\\TariffeTecnici', '1'),
-- Tariffe relative ai contratti
(NULL, 'app-v1', 'retrieve', 'tariffe-contratti', 'API\\App\\v1\\TariffeContratti', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffe-contratti-cleanup', 'API\\App\\v1\\TariffeContratti', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffa-contratto', 'API\\App\\v1\\TariffeContratti', '1'),
-- Allegati
(NULL, 'app-v1', 'retrieve', 'allegati-interventi', 'API\\App\\v1\\AllegatiInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'allegati-interventi-cleanup', 'API\\App\\v1\\AllegatiInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'allegato-intervento', 'API\\App\\v1\\AllegatiInterventi', '1'),
(NULL, 'app-v1', 'create', 'allegato-intervento', 'API\\App\\v1\\AllegatiInterventi', '1'),
-- Email di rapportino intervento
(NULL, 'app-v1', 'retrieve', 'email-rapportino', 'API\\App\\v1\\RapportinoIntervento', '1'),
(NULL, 'app-v1', 'create', 'email-rapportino', 'API\\App\\v1\\RapportinoIntervento', '1');

-- Impostazioni relative all'applicazione
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Google Maps API key', '', 'string', '1', 'Applicazione', 1),
(NULL, 'Mostra prezzi', '1', 'boolean', '1', 'Applicazione', 1);

-- Aggiunta risorsa per il download degli allegati
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES (NULL, 'v1', 'retrieve', 'allegato', 'API\\Common\\Allegato', '1');

-- Modifica di MyImpianti in Impianti
UPDATE `zz_modules` SET `name` = 'Impianti', `title` = IF(`title` = 'MyImpianti', 'Impianti', `title`), `directory` = 'impianti' WHERE `zz_modules`.`name` = 'MyImpianti';
