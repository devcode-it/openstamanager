-- Aggiornamento Netto a pagare per considerare lo Sconto finale
UPDATE `zz_views` SET `query` = '(righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale`) * (1 - `co_documenti`.`sconto_finale_percentuale` / 100) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `name` = 'Netto a pagare' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = '(righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale`) * (1 - `co_documenti`.`sconto_finale_percentuale` / 100) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `name` = 'Netto a pagare' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di acquisto');

-- Fix aggiornamento query Articoli per aggiunta quantità ordinata
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `mg_articoli`
    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT JOIN (
        SELECT SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS qta_impegnata, or_righe_ordini.idarticolo
        FROM or_righe_ordini
            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id
            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id
        WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 1)
            AND or_tipiordine.dir = ''entrata''
            AND or_righe_ordini.confermato = 1
        GROUP BY idarticolo
    ) a ON a.idarticolo = mg_articoli.id
    LEFT JOIN (
        SELECT SUM(or_righe_ordini.qta) AS qta_ordinata, or_righe_ordini.idarticolo
        FROM or_righe_ordini
            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id
            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id
        WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 1)
            AND or_tipiordine.dir = ''uscita''
            AND or_righe_ordini.confermato = 1
        GROUP BY idarticolo
    ) ordini_fornitore ON ordini_fornitore.idarticolo = mg_articoli.id
    LEFT JOIN mg_categorie ON mg_articoli.id_categoria = mg_categorie.id
    LEFT JOIN mg_categorie AS sottocategorie ON mg_articoli.id_sottocategoria = sottocategorie.id
WHERE 1=1 AND `mg_articoli`.`deleted_at` IS NULL
HAVING 2=2
ORDER BY `mg_articoli`.`descrizione`' WHERE `zz_modules`.`name`='Articoli';

-- Rimozione flag inutilizzato
ALTER TABLE `or_statiordine` DROP `annullato`;

-- Aggiunta flag "impegnato" sugli stati ordine
ALTER TABLE `or_statiordine` ADD `impegnato` BOOLEAN NOT NULL DEFAULT FALSE AFTER `icona`;
UPDATE `or_statiordine` SET `impegnato` = 1 WHERE `descrizione` IN('Evaso', 'Parzialmente evaso', 'Accettato', 'Parzialmente fatturato', 'Fatturato');

-- Aggiornamento calcolo quantità impegnate ed evase
UPDATE `zz_modules` SET `options` = 'SELECT |select|\nFROM `mg_articoli`\n    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica\n    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id\n    LEFT JOIN (\n        SELECT SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS qta_impegnata, or_righe_ordini.idarticolo\n        FROM or_righe_ordini\n            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id\n            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id\n            INNER JOIN or_statiordine ON or_ordini.idstatoordine = or_statiordine.id\n        WHERE\n            or_tipiordine.dir = \'entrata\'\n            AND or_righe_ordini.confermato = 1\n            AND or_statiordine.impegnato = 1\n        GROUP BY idarticolo\n    ) a ON a.idarticolo = mg_articoli.id\n    LEFT JOIN (\n        SELECT SUM(or_righe_ordini.qta-or_righe_ordini.qta_evasa) AS qta_ordinata, or_righe_ordini.idarticolo\n        FROM or_righe_ordini\n            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id\n            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id\n            INNER JOIN or_statiordine ON or_ordini.idstatoordine = or_statiordine.id\n        WHERE\n            or_tipiordine.dir = \'uscita\'\n            AND or_righe_ordini.confermato = 1\n            AND or_statiordine.impegnato = 1\n        GROUP BY idarticolo\n    ) ordini_fornitore ON ordini_fornitore.idarticolo = mg_articoli.id\n    LEFT JOIN mg_categorie ON mg_articoli.id_categoria = mg_categorie.id\n    LEFT JOIN mg_categorie AS sottocategorie ON mg_articoli.id_sottocategoria = sottocategorie.id\nWHERE 1=1 AND (`mg_articoli`.`deleted_at`) IS NULL\nHAVING 2=2\nORDER BY `mg_articoli`.`descrizione`' WHERE `zz_modules`.`name` = 'Articoli';

-- Fix query widgets Fatturato e Acquisti
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\nWHERE co_tipidocumento.dir=\'entrata\' |segment| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Fatturato';
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\nWHERE co_tipidocumento.dir=\'uscita\' |segment| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Acquisti';

-- Aggiunta risorse API per creazione e modifica Articoli
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'create', 'articolo', 'Modules\\Articoli\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'update', 'articolo', 'Modules\\Articoli\\API\\v1\\Articoli', '1');

-- Fix visualizzazione attività in dashboard
UPDATE `zz_segments` SET `clause` = '(orario_inizio BETWEEN \'|period_start|\' AND \'|period_end|\' OR orario_fine BETWEEN \'|period_start|\' AND \'|period_end|\')' WHERE `zz_segments`.`name` = 'Attività';

-- Aumentato limite per campo note in scheda anagrafica
ALTER TABLE `an_anagrafiche` CHANGE `note` `note` TEXT NOT NULL;

-- Aggiunta risorsa APi per revisione applicazione
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'app-v1', 'retrieve', 'revisione', 'API\\App\\v1\\Revisione', '1');

-- Fix query listini
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM mg_prezzi_articoli  INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = mg_prezzi_articoli.id_anagrafica  INNER JOIN mg_articoli ON mg_articoli.id = mg_prezzi_articoli.id_articolo  LEFT JOIN mg_categorie AS categoria ON mg_articoli.id_categoria=categoria.id  LEFT JOIN mg_categorie AS sottocategoria ON mg_articoli.id_sottocategoria=sottocategoria.id WHERE 1=1 AND mg_articoli.deleted_at IS NULL AND an_anagrafiche.deleted_at IS NULL HAVING 2=2 ORDER BY an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Listini';

-- Cambiato title al plugin prezzi specifici
UPDATE `zz_plugins` SET `title` = 'Prezzi di listino' WHERE `zz_plugins`.`name` = 'Prezzi specifici articolo';

-- Impostati stati fatturabili in ddt e ordini
ALTER TABLE `or_statiordine` ADD `is_fatturabile` TINYINT(1) NOT NULL AFTER `completato`;
ALTER TABLE `dt_statiddt` ADD `is_fatturabile` TINYINT(1) NOT NULL AFTER `completato`;

UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Evaso';
UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Parzialmente evaso';
UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Parzialmente fatturato';
UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Accettato';
UPDATE `dt_statiddt` SET `is_fatturabile` = '1' WHERE `dt_statiddt`.`descrizione` = 'Evaso';
UPDATE `dt_statiddt` SET `is_fatturabile` = '1' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente evaso';
UPDATE `dt_statiddt` SET `is_fatturabile` = '1' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente fatturato';

-- Aggiunta colonna um in Movimenti di magazzino
UPDATE `zz_views` SET `query` = 'mg_movimenti.qta' WHERE `zz_views`.`name` = 'Quantità';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Movimenti'), 'Um', 'mg_articoli.um', 5, 1, 0, 0, '', '', 0, 0, 0);
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) (
SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = 'Um'
);
-- Fix campo iva per Sconti di versioni precedenti
UPDATE `co_righe_documenti` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `co_righe_preventivi` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `co_righe_contratti` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `dt_righe_ddt` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `or_righe_ordini` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `co_righe_promemoria` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `in_righe_interventi` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;

-- Aumentato il campo descrizione in articoli da varchar a text
ALTER TABLE `mg_articoli` CHANGE `descrizione` `descrizione` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

-- Set a NULL le date dei contratti vuote
UPDATE `co_contratti` SET `data_bozza`=NULL WHERE `data_bozza`=0000-00-00;
UPDATE `co_contratti` SET `data_accettazione`=NULL WHERE `data_accettazione`=0000-00-00;
UPDATE `co_contratti` SET `data_rifiuto`=NULL WHERE `data_rifiuto`=0000-00-00;
UPDATE `co_contratti` SET `data_conclusione`=NULL WHERE `data_conclusione`=0000-00-00;

-- Aggiunto sconto finale in preventivi
ALTER TABLE `co_preventivi` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `garanzia`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Aggiunto sconto finale in ordini
ALTER TABLE `or_ordini` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `numero_cliente`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Aggiunto sconto finale in ddt
ALTER TABLE `dt_ddt` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `num_item`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Aggiunto sconto finale in contratti
ALTER TABLE `co_contratti` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `num_item`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Set a NULL le date dei preventivi vuote
UPDATE `co_preventivi` SET `data_bozza`=NULL WHERE `data_bozza`=0000-00-00;
UPDATE `co_preventivi` SET `data_accettazione`=NULL WHERE `data_accettazione`=0000-00-00;
UPDATE `co_preventivi` SET `data_rifiuto`=NULL WHERE `data_rifiuto`=0000-00-00;
UPDATE `co_preventivi` SET `data_conclusione`=NULL WHERE `data_conclusione`=0000-00-00;

-- Aggiunto filtro in attività per vedere interventi assegnati
INSERT INTO `zz_group_module` ( `idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_groups` WHERE `nome`='Tecnici'), (SELECT `id` FROM `zz_modules` WHERE name='Interventi'), 'Mostra interventi ai tecnici assegnati', 'in_interventi.id IN (SELECT id_intervento FROM in_interventi_tecnici_assegnati WHERE id_intervento=in_interventi.id AND id_tecnico=|id_anagrafica|)', 'WHR', 0, 1);

-- Aggiunta colonna Prev. evasione in Ordini
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'), 'icon_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'fa fa-clock-o text-info\', \'fa fa-warning text-danger\'), \'fa fa-check text-success\')', 8, 1, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'), 'icon_title_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'In orario\', \'In ritardo\'), \'Consegnato\')', 9, 1, 0, 0, '', '', 0, 0, 0);

INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'), 'icon_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'fa fa-clock-o text-info\', \'fa fa-warning text-danger\'), \'fa fa-check text-success\')', 8, 1, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'), 'icon_title_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'In orario\', \'In ritardo\'), \'Consegnato\')', 9, 1, 0, 0, '', '', 0, 0, 0);

UPDATE `zz_modules` SET `options` = 'SELECT |select|\nFROM `or_ordini`\n     LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n     LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\n     LEFT JOIN (\n         SELECT `idordine`,\n                SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`,\n                SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\n                SUM(`subtotale` - `sconto` + `iva`) AS `totale`\n         FROM `or_righe_ordini`\n         GROUP BY `idordine`\n     ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\n	LEFT JOIN (\n		SELECT `idordine`,\n        MIN(`data_evasione`) AS `data_evasione`\n        FROM `or_righe_ordini`\n        WHERE (`qta` - `qta_evasa`)>0\n        GROUP BY `idordine`\n    ) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\nWHERE 1=1 AND `dir` = \'entrata\' |date_period(`data`)|\nHAVING 2=2\nORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';

UPDATE `zz_modules` SET `options` = 'SELECT |select|\nFROM `or_ordini`\n     LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n     LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\n     LEFT JOIN (\n         SELECT `idordine`,\n                SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`,\n                SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\n                SUM(`subtotale` - `sconto` + `iva`) AS `totale`\n         FROM `or_righe_ordini`\n         GROUP BY `idordine`\n     ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\n	LEFT JOIN (\n		SELECT `idordine`,\n        MIN(`data_evasione`) AS `data_evasione`\n        FROM `or_righe_ordini`\n        WHERE (`qta` - `qta_evasa`)>0\n        GROUP BY `idordine`\n    ) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\nWHERE 1=1 AND `dir` = \'uscita\' |date_period(`data`)|\nHAVING 2=2\nORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) (
SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name`='Ordini fornitore' AND (`zz_views`.`name` = 'icon_title_Prev. evasione' OR `zz_views`.`name` = 'icon_Prev. evasione')
);

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) (
SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name`='Ordini cliente' AND (`zz_views`.`name` = 'icon_title_Prev. evasione' OR `zz_views`.`name` = 'icon_Prev. evasione')
);

-- Aggiunto campo ora evasione in ordini
ALTER TABLE `or_righe_ordini` ADD `ora_evasione` TIME NULL AFTER `data_evasione`;

-- Aggiunta indice sull'id preventivo per velocizzare il caricamento del rif. numero fattura in vista preventivi
ALTER TABLE `co_righe_documenti` ADD INDEX(`idpreventivo`);

-- Aggiunta stampa dettaglio anagrafica
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES ((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), '1', 'Dettaglio anagrafica', 'Dettaglio anagrafica', 'Anagrafica {codice} - {ragione_sociale}', 'anagrafiche', 'idanagrafica', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- Aggiunta stampa dati aziendali
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES ((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), '1', 'Dati aziendali', 'Dati aziendali', 'Dati aziendali {ragione_sociale}', 'azienda', 'idanagrafica', '', 'fa fa-print', '', '', '0', '0', '0', '1');

-- Correzione per segmenti con pagamenti RiBa per Scadenzario
UPDATE `zz_segments` SET `clause` = REPLACE(`clause`, 'co_pagamenti.riba=1', 'co_pagamenti.codice_modalita_pagamento_fe= ''MP12''');
ALTER TABLE `co_pagamenti` DROP `riba`;

-- Aggiunto filtro in contratti per i clienti
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES(4, 31, 'Mostra i contratti ai clienti coivolti', 'co_contratti.idanagrafica=|id_anagrafica|', 'WHR', 1, 1);

-- Fix widget crediti clienti
UPDATE `zz_widgets` SET `query` = 'SELECT \n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(da_pagare-pagato)), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'entrata\' AND co_documenti.idstatodocumento!=1 |segment| AND 1=1' WHERE `zz_widgets`.`name` = 'Crediti da clienti';

-- Aggiunto campo descrizione revisione in preventivi
ALTER TABLE `co_preventivi` ADD `descrizione_revision` VARCHAR(255) NOT NULL AFTER `default_revision`;
UPDATE `zz_prints` SET `filename` = 'Preventivo num. {numero} del {data} rev {revisione}' WHERE `zz_prints`.`name` = 'Preventivo';

-- Aggiunti campi per componenti IBAN
ALTER TABLE `co_banche` ADD `branch_code` VARCHAR(20) NULL,
    ADD `bank_code` VARCHAR(20) NULL,
    ADD `account_number` VARCHAR(20) NULL,
    ADD `check_digits` VARCHAR(20) NULL,
    ADD `national_check_digits` VARCHAR(20) NULL,
    ADD `id_nazione` INT(11) NULL,
    ADD FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni`(`id`);

-- Fix gestione documentale
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `do_documenti`\r\nINNER JOIN `do_categorie` ON `do_categorie`.`id` = `do_documenti`.`idcategoria`\r\nWHERE 1=1 AND `deleted_at` IS NULL AND\r\n (SELECT `idgruppo` FROM `zz_users` WHERE `zz_users`.`id` = |id_utente|) IN (SELECT `id_gruppo` FROM `do_permessi` WHERE `id_categoria` = `do_documenti`.`idcategoria`)\r\n |date_period(`data`)| OR data IS NULL\r\nHAVING 2=2' WHERE `zz_modules`.`name` = 'Gestione documentale'; 

-- Messaggio Verifica numero intervento
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Verifica numero intervento', '1', 'boolean', 1, 'Attività', 1, 'Visualizza il messaggio che verifica la continuità dei numeri per le attività');

-- Allineamento colore icona EC02
UPDATE `fe_stati_documento` SET `icon` = 'fa fa-times text-danger' WHERE `fe_stati_documento`.`codice` = 'EC02'; 

-- Impostata aliquota iva per dichiarazone d'intento se non presente
UPDATE `zz_settings` SET `valore` = IF(`valore` ='', (SELECT `id` FROM `co_iva` WHERE `descrizione`='Non imp. art. 8 c.1 lett. c DPR 633/1972'), `valore`) WHERE `nome`="Iva per lettere d'intento";

-- Fix query Fatture di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`\nLEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\nLEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\nLEFT JOIN (\n    SELECT `iddocumento`,\n    SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\n    SUM(`subtotale` - `sconto` + `iva`) AS `totale`\n    FROM `co_righe_documenti`\n    GROUP BY `iddocumento`\n) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`\nLEFT JOIN (\n    SELECT COUNT(`d`.`id`) AS `conteggio`,\n        IF(`d`.`numero_esterno`=\'\', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`,\n        `d`.`idanagrafica` AS `anagrafica`\n    FROM `co_documenti` AS `d`\n    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id`\n    WHERE 1=1\n        AND `d_tipo`.`dir` = \'uscita\'\n        AND (\'|period_start|\' <= `d`.`data` AND \'|period_end|\' >= `d`.`data` OR \'|period_start|\' <= `d`.`data_competenza` AND \'|period_end|\' >= `d`.`data_competenza`)\n        GROUP BY `numero_documento`, `d`.`idanagrafica`\n) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno`=\'\', `co_documenti`.`numero`, `co_documenti`.`numero_esterno`) AND `d`.`anagrafica`=`co_documenti`.`idanagrafica`)\nWHERE 1=1 AND `dir` = \'uscita\' |segment(`co_documenti`.`id_segment`)||date_period(custom, \'|period_start|\' <= `co_documenti`.`data` AND \'|period_end|\' >= `co_documenti`.`data`, \'|period_start|\' <= `co_documenti`.`data_competenza` AND \'|period_end|\' >= `co_documenti`.`data_competenza` )|\nHAVING 2=2\nORDER BY `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero` = \'\', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di acquisto';

-- Ripristino Fattura di vendita come stampa predefinita
UPDATE `zz_prints` SET `predefined` = '0' WHERE `zz_prints`.`name` = 'Fattura elettronica di vendita'; 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Modulo', '(SELECT name FROM zz_modules WHERE zz_modules.id= zz_prints.id_module)', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Predefinita', 'zz_prints.predefined', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Ordine', 'zz_prints.order', 6, 1, 0, 0, 1);