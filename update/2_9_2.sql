-- fix permessi otp token
ALTER TABLE `zz_otp_tokens` CHANGE `permessi` `permessi` ENUM('r','rw','ra','rwa') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;

-- Nuovo sottomodulo "Statistiche vendita" sotto Articoli
SET @parent_id = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES
('Statistiche vendita', 'statistiche_articoli',
'SELECT
    |select|
FROM
    `co_documenti`
    INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`. |lang|)
    INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
    INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `co_righe_documenti`.`idarticolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`. |lang|)
    LEFT JOIN (
        SELECT
            SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) AS totale_qta_generale
        FROM
            `co_documenti`
            INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
            INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `co_righe_documenti`.`idarticolo`
            INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
            LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = |lang|)
        WHERE
            `co_tipidocumento`.`dir` = \'entrata\'
            AND (`co_statidocumento_lang`.`title` = \'Pagato\' OR `co_statidocumento_lang`.`title` = \'Parzialmente pagato\' OR `co_statidocumento_lang`.`title` = \'Emessa\')
    ) AS `totali_generali` ON 1=1
WHERE
    1=1
    AND `co_tipidocumento`.`dir` = \'entrata\'
    AND (`co_statidocumento_lang`.`title` = \'Pagato\' OR `co_statidocumento_lang`.`title` = \'Parzialmente pagato\' OR `co_statidocumento_lang`.`title` = \'Emessa\')
GROUP BY
    `co_righe_documenti`.`idarticolo`, `mg_articoli_lang`.`title`
HAVING
    2=2
ORDER BY
    SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) DESC',
'', 'fa fa-circle-o', '2.9.0', '2.*', '1', @parent_id, '1', '1', '0', '0');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Statistiche vendita'), 'Statistiche vendita', 'Statistiche vendita'),
('2', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Statistiche vendita'), 'Sales statistics', 'Sales statistics');

-- Viste per il modulo Statistiche vendita
SET @id_module_stat = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Statistiche vendita');
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(@id_module_stat, 'id', '`mg_articoli`.`id`', 1, 1, 0, 0, '', '', 0, 0, 1),
(@id_module_stat, '_link_module_', '(SELECT `id` FROM `zz_modules` WHERE `name` = \'Articoli\')', 2, 0, 0, 0, '', '', 0, 0, 1),
(@id_module_stat, '_link_record_', '`mg_articoli`.`id`', 3, 0, 0, 0, '', '', 0, 0, 1),
(@id_module_stat, 'Articolo', 'CONCAT(`mg_articoli`.`codice`, \' - \', `mg_articoli_lang`.`title`)', 4, 1, 0, 0, '', '', 1, 0, 1),
(@id_module_stat, 'Q.ta', 'SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`))', 5, 1, 0, 1, '', '', 1, 1, 1),
(@id_module_stat, 'Percentuale Iva', 'ROUND((SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) * 100 / `totali_generali`.`totale_qta_generale`), 2)', 6, 1, 0, 1, '', '', 1, 0, 1),
(@id_module_stat, 'Totale', 'SUM(IF(`co_tipidocumento`.`reversed`=1, -(`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`)))', 7, 1, 0, 1, '', '', 1, 1, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'id'), 'ID'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'id'), 'ID'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_module_'), '_link_module_'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_module_'), '_link_module_'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_record_'), '_link_record_'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_record_'), '_link_record_'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Articolo'), 'Articolo'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Articolo'), 'Article'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Q.ta'), 'Q.tà'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Q.ta'), 'Qty'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Percentuale Iva'), 'Percentuale Iva'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Percentuale Iva'), 'VAT Percentage'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Totale'), 'Totale'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Totale'), 'Total');

DELETE FROM zz_plugins WHERE name = 'Statistiche vendita';

-- Semplificazione query per modulo articoli
UPDATE `zz_modules` SET `options` = 'SELECT\r\n |select|\r\nFROM\r\n `mg_articoli`\r\n LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)\r\n LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`\r\n LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`\r\n LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_impegnata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = \'entrata\' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`\r\n LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_ordinata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = \'uscita\' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) `ordini_fornitore` ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`\r\n LEFT JOIN `zz_categorie` ON `mg_articoli`.`id_categoria` = `zz_categorie`.`id`\r\n LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)\r\n LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`\r\n LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)\r\n LEFT JOIN (SELECT `co_iva`.`percentuale` AS `perc`, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id` = `zz_settings`.`valore`) AS iva ON `iva`.`nome` = \'Iva predefinita\'\r\n LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`\r\n LEFT JOIN (SELECT CASE WHEN MIN(`differenza`) < 0 THEN -1 WHEN MAX(`threshold_qta`) > 0 THEN 1 ELSE 0 END AS `stato_giacenza`, `idarticolo` FROM (SELECT SUM(`mg_movimenti`.`qta`) - COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `differenza`, COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `threshold_qta`, `mg_movimenti`.`idarticolo` FROM `mg_movimenti` LEFT JOIN `mg_scorte_sedi` ON (`mg_scorte_sedi`.`id_sede` = `mg_movimenti`.`idsede` AND `mg_scorte_sedi`.`id_articolo` = `mg_movimenti`.`idarticolo`) GROUP BY `mg_movimenti`.`idarticolo`, `mg_movimenti`.`idsede`) AS `subquery` GROUP BY `idarticolo`) AS `giacenze` ON `giacenze`.`idarticolo` = `mg_articoli`.`id`\r\n LEFT JOIN (SELECT GROUP_CONCAT(`mg_articoli_barcode`.`barcode` SEPARATOR \'<br />\') AS `lista`, `mg_articoli_barcode`.`idarticolo` FROM `mg_articoli_barcode` GROUP BY `mg_articoli_barcode`.`idarticolo`) AS `barcode` ON `barcode`.`idarticolo` = `mg_articoli`.`id`\r\nWHERE\r\n 1=1 AND `mg_articoli`.`deleted_at` IS NULL\r\nHAVING\r\n 2=2\r\nORDER BY\r\n `mg_articoli_lang`.`title`' WHERE `zz_modules`.`name` = 'Articoli'; 

DELETE FROM `an_anagrafiche_agenti` WHERE `idanagrafica` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `an_anagrafiche_agenti` ADD CONSTRAINT `an_anagrafiche_agenti_ibfk_1` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

DELETE FROM `an_anagrafiche_agenti` WHERE `idagente` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `an_anagrafiche_agenti` ADD CONSTRAINT `an_anagrafiche_agenti_ibfk_2` FOREIGN KEY (`idagente`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

DELETE FROM `an_assicurazione_crediti` WHERE `id_anagrafica` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `an_assicurazione_crediti` ADD CONSTRAINT `an_assicurazione_crediti_ibfk_1` FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

DELETE FROM `an_referenti` WHERE `idanagrafica` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `an_referenti` ADD CONSTRAINT `an_referenti_ibfk_1` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

ALTER TABLE `an_referenti` ADD CONSTRAINT `an_referenti_ibfk_3` FOREIGN KEY (`idmansione`) REFERENCES `an_mansioni`(`id`) ON DELETE RESTRICT;

DELETE FROM `an_sedi` WHERE `idanagrafica` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `an_sedi` ADD CONSTRAINT `an_sedi_ibfk_2` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

DELETE FROM `an_pagamenti_anagrafiche` WHERE `idanagrafica` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `an_pagamenti_anagrafiche` ADD CONSTRAINT `an_pagamenti_anagrafiche_ibfk_1` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

-- FOREIGN KEYS per tabelle contratti
ALTER TABLE `co_contratti` ADD CONSTRAINT `co_contratti_ibfk_1` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE RESTRICT;
ALTER TABLE `co_contratti` ADD CONSTRAINT `co_contratti_ibfk_6` FOREIGN KEY (`idpagamento`) REFERENCES `co_pagamenti`(`id`) ON DELETE RESTRICT;

DELETE FROM `co_contratti_tipiintervento` WHERE `idcontratto` NOT IN (SELECT `id` FROM `co_contratti`);
ALTER TABLE `co_contratti_tipiintervento` ADD CONSTRAINT `co_contratti_tipiintervento_ibfk_2` FOREIGN KEY (`idcontratto`) REFERENCES `co_contratti`(`id`) ON DELETE CASCADE;

-- FOREIGN KEYS per tabelle documenti
ALTER TABLE `co_documenti` ADD CONSTRAINT `co_documenti_ibfk_7` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE RESTRICT;
ALTER TABLE `co_documenti` ADD CONSTRAINT `co_documenti_ibfk_12` FOREIGN KEY (`idpagamento`) REFERENCES `co_pagamenti`(`id`) ON DELETE RESTRICT;

-- FOREIGN KEYS per tabelle righe documenti
DELETE FROM `co_righe_documenti` WHERE `iddocumento` NOT IN (SELECT `id` FROM `co_documenti`);
ALTER TABLE `co_righe_documenti` ADD CONSTRAINT `co_righe_documenti_ibfk_3` FOREIGN KEY (`iddocumento`) REFERENCES `co_documenti`(`id`) ON DELETE CASCADE;
ALTER TABLE `co_righe_documenti` ADD CONSTRAINT `co_righe_documenti_ibfk_5` FOREIGN KEY (`idiva`) REFERENCES `co_iva`(`id`) ON DELETE RESTRICT;
ALTER TABLE `co_righe_documenti` ADD CONSTRAINT `co_righe_documenti_ibfk_6` FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE SET NULL;

-- FOREIGN KEYS per tabelle scadenziario
ALTER TABLE `co_scadenziario` ADD CONSTRAINT `co_scadenziario_ibfk_4` FOREIGN KEY (`id_pagamento`) REFERENCES `co_pagamenti`(`id`) ON DELETE RESTRICT;

-- FOREIGN KEYS per tabelle DDT
ALTER TABLE `dt_ddt` ADD CONSTRAINT `dt_ddt_ibfk_2` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE RESTRICT;

ALTER TABLE `dt_righe_ddt` ADD CONSTRAINT `dt_righe_ddt_ibfk_4` FOREIGN KEY (`idiva`) REFERENCES `co_iva`(`id`) ON DELETE RESTRICT;

-- FOREIGN KEYS per tabelle interventi
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_11` FOREIGN KEY (`idclientefinale`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE RESTRICT;

ALTER TABLE `in_righe_interventi` ADD CONSTRAINT `in_righe_interventi_ibfk_4` FOREIGN KEY (`idiva`) REFERENCES `co_iva`(`id`) ON DELETE RESTRICT;

-- FOREIGN KEYS per tabelle articoli
DELETE FROM `mg_scorte_sedi` WHERE `id_articolo` NOT IN (SELECT `id` FROM `mg_articoli`);
ALTER TABLE `mg_scorte_sedi` ADD CONSTRAINT `mg_scorte_sedi_ibfk_1` FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE;

DELETE FROM `mg_scorte_sedi` WHERE `id_sede` NOT IN (SELECT `id` FROM `an_sedi`);
ALTER TABLE `mg_scorte_sedi` ADD CONSTRAINT `mg_scorte_sedi_ibfk_2` FOREIGN KEY (`id_sede`) REFERENCES `an_sedi`(`id`) ON DELETE CASCADE;

DELETE FROM `mg_listini_articoli` WHERE `id_listino` NOT IN (SELECT `id` FROM `mg_listini`);
ALTER TABLE `mg_listini_articoli` ADD CONSTRAINT `mg_listini_articoli_ibfk_1` FOREIGN KEY (`id_listino`) REFERENCES `mg_listini`(`id`) ON DELETE CASCADE;

DELETE FROM `mg_listini_articoli` WHERE `id_articolo` NOT IN (SELECT `id` FROM `mg_articoli`);
ALTER TABLE `mg_listini_articoli` ADD CONSTRAINT `mg_listini_articoli_ibfk_2` FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE;

DELETE FROM `mg_movimenti` WHERE `idarticolo` NOT IN (SELECT `id` FROM `mg_articoli`);
ALTER TABLE `mg_movimenti` ADD CONSTRAINT `mg_movimenti_ibfk_2` FOREIGN KEY (`idarticolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE;

-- FOREIGN KEYS per tabelle ordini
ALTER TABLE `or_ordini` ADD CONSTRAINT `or_ordini_ibfk_2` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE RESTRICT;
ALTER TABLE `or_ordini` ADD CONSTRAINT `or_ordini_ibfk_7` FOREIGN KEY (`idpagamento`) REFERENCES `co_pagamenti`(`id`) ON DELETE RESTRICT;

DELETE FROM `or_righe_ordini` WHERE `idordine` NOT IN (SELECT `id` FROM `or_ordini`);
ALTER TABLE `or_righe_ordini` ADD CONSTRAINT `or_righe_ordini_ibfk_2` FOREIGN KEY (`idordine`) REFERENCES `or_ordini`(`id`) ON DELETE CASCADE;
ALTER TABLE `or_righe_ordini` ADD CONSTRAINT `or_righe_ordini_ibfk_4` FOREIGN KEY (`idiva`) REFERENCES `co_iva`(`id`) ON DELETE RESTRICT;

-- FOREIGN KEYS per tabelle preventivi
ALTER TABLE `co_preventivi` ADD CONSTRAINT `co_preventivi_ibfk_2` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE RESTRICT;

ALTER TABLE `co_righe_preventivi` ADD CONSTRAINT `co_righe_preventivi_ibfk_4` FOREIGN KEY (`idiva`) REFERENCES `co_iva`(`id`) ON DELETE RESTRICT;

-- FOREIGN KEYS per tabelle impianti
DELETE FROM `my_impianti` WHERE `idanagrafica` NOT IN (SELECT `idanagrafica` FROM `an_anagrafiche`);
ALTER TABLE `my_impianti` ADD CONSTRAINT `my_impianti_ibfk_1` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

ALTER TABLE `my_impianti_contratti` CHANGE `idcontratto` `idcontratto` INT NOT NULL; 

DELETE FROM `my_impianti_contratti` WHERE `idcontratto` NOT IN (SELECT `id` FROM `co_contratti`);
ALTER TABLE `my_impianti_contratti` ADD CONSTRAINT `my_impianti_contratti_ibfk_2` FOREIGN KEY (`idcontratto`) REFERENCES `co_contratti`(`id`) ON DELETE CASCADE;

DELETE FROM `zz_otp_tokens` WHERE `id_utente` NOT IN (SELECT `id` FROM `zz_users`);
ALTER TABLE `zz_otp_tokens` ADD CONSTRAINT `zz_otp_tokens_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE;

DELETE FROM `zz_otp_tokens` WHERE `id_module_target` NOT IN (SELECT `id` FROM `zz_modules`);
ALTER TABLE `zz_otp_tokens` ADD CONSTRAINT `zz_otp_tokens_ibfk_2` FOREIGN KEY (`id_module_target`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

DELETE FROM `zz_user_sedi` WHERE `id_user` NOT IN (SELECT `id` FROM `zz_users`);
ALTER TABLE `zz_user_sedi` ADD CONSTRAINT `zz_user_sedi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE;

DELETE FROM `zz_files` WHERE `id_module` NOT IN (SELECT `id` FROM `zz_modules`);
ALTER TABLE `zz_files` ADD CONSTRAINT `zz_files_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

-- Indici per tabelle anagrafiche
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_id_nazione` (`id_nazione`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idpagamento_vendite` (`idpagamento_vendite`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idpagamento_acquisti` (`idpagamento_acquisti`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idiva_vendite` (`idiva_vendite`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idiva_acquisti` (`idiva_acquisti`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_id_settore` (`id_settore`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idagente` (`idagente`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idrelazione` (`idrelazione`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_idzona` (`idzona`);

ALTER TABLE `co_contratti` ADD INDEX `idx_idstato` (`idstato`);

ALTER TABLE `co_documenti` ADD INDEX `idx_ref_documento` (`ref_documento`);
ALTER TABLE `co_documenti` ADD INDEX `idx_idtipodocumento` (`idtipodocumento`);
ALTER TABLE `co_documenti` ADD INDEX `idx_idstatodocumento` (`idstatodocumento`);

ALTER TABLE `co_movimenti` ADD INDEX `idx_idconto` (`idconto`);
ALTER TABLE `co_movimenti` ADD INDEX `idx_idmastrino` (`idmastrino`);

ALTER TABLE `dt_ddt` ADD INDEX `idx_idstatoddt` (`idstatoddt`);
ALTER TABLE `dt_ddt` ADD INDEX `idx_idtipoddt` (`idtipoddt`);

ALTER TABLE `in_interventi` ADD INDEX `idx_idstatointervento` (`idstatointervento`);

ALTER TABLE `mg_articoli` ADD INDEX `idx_id_fornitore` (`id_fornitore`);
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_combinazione` (`id_combinazione`);

ALTER TABLE `mg_movimenti` ADD INDEX `idx_idintervento` (`idintervento`);

ALTER TABLE `or_ordini` ADD INDEX `idx_idstatoordine` (`idstatoordine`);
ALTER TABLE `or_ordini` ADD INDEX `idx_idtipoordine` (`idtipoordine`);

ALTER TABLE `co_preventivi` ADD INDEX `idx_idstato` (`idstato`);

ALTER TABLE `zz_otp_tokens` ADD INDEX `idx_id_record_target` (`id_record_target`);
