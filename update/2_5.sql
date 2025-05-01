-- Aggiornamento impostazione cifre decimali
UPDATE `zz_settings` SET `tipo` = 'list[2,3,4,5]' WHERE `nome` = 'Cifre decimali per importi';
UPDATE `zz_settings` SET `tipo` = 'list[0,1,2,3,4,5]' WHERE `nome` = 'Cifre decimali per quantità';

-- Aggiunta impostazione per lingua di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`,`order`, `help`) VALUES ('Lingua', '1', 'query=SELECT `id`, `name` AS descrizione FROM `zz_langs` ORDER BY `descrizione` ASC', '1', 'Generali',NULL, 'Seleziona la lingua di default del gestionale');

-- Aggiunta tabella zz_langs
CREATE TABLE IF NOT EXISTS `zz_langs` (
    `id` int NOT NULL,
    `name` varchar(255) NOT NULL,
    `enabled` tinyint(1) NOT NULL,
    `iso_code` varchar(2) NOT NULL,
    `language_code` varchar(5) NOT NULL,
    `date` varchar(100) NOT NULL,
    `time` varchar(100) NOT NULL,
    `timestamp` varchar(100) NOT NULL,
    `decimals` varchar(1) NOT NULL,
    `thousands` varchar(1) NULL,
    `is_rtl` tinyint(1) NOT NULL DEFAULT FALSE,
    `predefined` tinyint(1) NOT NULL DEFAULT FALSE
);

ALTER TABLE `zz_langs`
    ADD PRIMARY KEY (`id`); 

ALTER TABLE `zz_langs`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_langs` (`name`, `enabled`, `iso_code`, `language_code`, `date`, `time`, `timestamp`, `decimals`, `thousands`, `is_rtl`, `predefined`) VALUES 
('Italiano (Italian)', '1', 'it', 'it_IT', 'd/m/Y', 'H:i', 'd/m/Y H:i', ',', '.', 0, 1), 
('English (English)', '1', 'en', 'en_GB', 'm/d/Y', 'H:i', 'm/d/Y H:i', '.', ',', 0, 0); 

-- Aggiunta valuta dollaro
INSERT INTO `zz_currencies` (`id`, `name`, `title`, `symbol`) VALUES (NULL, 'Dollaro', 'Dollaro', '&dollar;');

-- Aggiunta tabella an_nazioni_lang
CREATE TABLE IF NOT EXISTS `an_nazioni_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `an_nazioni_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `an_nazioni_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `an_nazioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `an_nazioni`;

INSERT INTO `an_nazioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'en'), `id`, `name` FROM `an_nazioni`;

ALTER TABLE `an_nazioni`
    DROP `nome`,
    DROP `name`; 

ALTER TABLE `an_nazioni_lang` ADD CONSTRAINT `an_nazioni_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_nazioni`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Eventi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `zz_events`
INNER JOIN `an_nazioni` ON `an_nazioni`.`id` = `zz_events`.`id_nazione`
INNER JOIN `an_nazioni_lang` ON (`an_nazioni`.`id` = `an_nazioni_lang`.`id_record` AND |lang|)
WHERE
    1=1 
HAVING
    2=2" WHERE `name` = 'Eventi';

-- Aggiunta tabella an_provenienze_lang
CREATE TABLE IF NOT EXISTS `an_provenienze_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `an_provenienze_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `an_provenienze_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `an_provenienze_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `an_provenienze`;

ALTER TABLE `an_provenienze`
    DROP `descrizione`;

ALTER TABLE `an_provenienze_lang` ADD CONSTRAINT `an_provenienze_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_provenienze`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Provenienze
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_provenienze`
    LEFT JOIN `an_provenienze_lang` ON (`an_provenienze`.`id` = `an_provenienze_lang`.`id_record` AND |lang|)
WHERE
    1=1
HAVING
    2=2" WHERE `name` = 'Provenienze';

-- Aggiunta tabella an_regioni_lang
CREATE TABLE IF NOT EXISTS `an_regioni_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `an_regioni_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `an_regioni_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `an_regioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `an_regioni`;

ALTER TABLE `an_regioni`
    DROP `nome`;

ALTER TABLE `an_regioni_lang` ADD CONSTRAINT `an_regioni_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_regioni`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella an_relazioni_lang
CREATE TABLE IF NOT EXISTS `an_relazioni_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `an_relazioni_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `an_relazioni_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `an_relazioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `an_relazioni`;

ALTER TABLE `an_relazioni`
    DROP `descrizione`;

ALTER TABLE `an_relazioni_lang` ADD CONSTRAINT `an_relazioni_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_relazioni`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Relazioni
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_relazioni`
    LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id` = `an_relazioni_lang`.`id_record` AND |lang|)
WHERE
    1=1 AND `deleted_at` IS NULL
HAVING
    2=2
ORDER BY 
    `an_relazioni`.`created_at` DESC" WHERE `name` = 'Relazioni';

-- Aggiunta tabella an_settori_lang
CREATE TABLE IF NOT EXISTS `an_settori_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `an_settori_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `an_settori_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `an_settori_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `an_settori`;

ALTER TABLE `an_settori`
    DROP `descrizione`;

ALTER TABLE `an_settori_lang` ADD CONSTRAINT `an_settori_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_settori`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Settori
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_settori`
    LEFT JOIN `an_settori_lang` ON (`an_settori`.`id` = `an_settori_lang`.`id_record` AND |lang|)
WHERE
    1=1
HAVING
    2=2" WHERE `name` = 'Settori';

ALTER TABLE `an_tipianagrafiche` CHANGE `idtipoanagrafica` `id` INT NOT NULL AUTO_INCREMENT; 

-- Aggiunta tabella an_tipianagrafiche_lang
CREATE TABLE IF NOT EXISTS `an_tipianagrafiche_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `an_tipianagrafiche_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `an_tipianagrafiche_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `an_tipianagrafiche_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `an_tipianagrafiche`;

ALTER TABLE `an_tipianagrafiche`
    DROP `descrizione`;

ALTER TABLE `an_tipianagrafiche_lang` ADD CONSTRAINT `an_tipianagrafiche_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_tipianagrafiche`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Tipi di anagrafiche
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_tipianagrafiche`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.|lang|)
WHERE
    1=1
HAVING
    2=2" WHERE `name` = 'Tipi di anagrafiche';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_tipianagrafiche`.`id`' WHERE `zz_modules`.`name` = 'Tipi di anagrafiche' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella co_iva_lang
CREATE TABLE IF NOT EXISTS `co_iva_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_iva_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_iva_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_iva_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `co_iva`;

ALTER TABLE `co_iva`
    DROP `descrizione`;

ALTER TABLE `co_iva_lang` ADD CONSTRAINT `co_iva_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_iva`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista IVA
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_iva`
    LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND |lang|)
WHERE
    1=1 AND `deleted_at` IS NULL
HAVING
    2=2" WHERE `name` = 'IVA';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_iva`.`id`' WHERE `zz_modules`.`name` = 'IVA' AND `zz_views`.`name` = 'id';

SELECT @codice := MAX(CAST(codice AS UNSIGNED))+1 FROM co_iva WHERE deleted_at IS NULL;
UPDATE co_iva SET codice = @codice WHERE id = (SELECT id_record FROM co_iva_lang WHERE name = 'Art.9 c.1 DPR 633/1972');
UPDATE co_iva SET codice = @codice+1 WHERE id = (SELECT id_record FROM co_iva_lang WHERE name = 'Non imp. art.72 DPR 633/1972');
UPDATE co_iva SET codice = @codice+2 WHERE id = (SELECT id_record FROM co_iva_lang WHERE name = 'Art. 71 DPR 633/1972');

-- Aggiunta tabella co_movimenti_modelli_lang
CREATE TABLE IF NOT EXISTS `co_pagamenti_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_pagamenti_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_pagamenti_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_pagamenti_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `co_pagamenti`;

ALTER TABLE `co_pagamenti`
    DROP `descrizione`;

ALTER TABLE `co_pagamenti_lang` ADD CONSTRAINT `co_pagamenti_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_pagamenti`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti`.`id`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'id';

-- Fix per file sql di update aggiornato dopo rilascio 2.4.35
UPDATE `zz_modules` SET `icon` = 'fa fa-exchange'  WHERE `zz_modules`.`name` = 'Causali movimenti';

-- Allineamento widgets
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND name=\"Cliente\" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di clienti';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND name=\"Tecnico\" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di tecnici';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND name=\"Fornitore\" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di fornitori';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND name=\"Agente\" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di agenti';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND name=\"Vettore\" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di vettori';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = 'Tutte le anagrafiche';

-- Aggiunta tabella co_staticontratti_lang
CREATE TABLE IF NOT EXISTS `co_staticontratti_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_staticontratti_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_staticontratti_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_staticontratti_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `co_staticontratti`;

ALTER TABLE `co_staticontratti`
    DROP `descrizione`;

ALTER TABLE `co_staticontratti` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `co_staticontratti_lang` ADD CONSTRAINT `co_staticontratti_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_staticontratti`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Stati dei contratti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_staticontratti`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
WHERE 
    1=1 AND deleted_at IS NULL 
HAVING 
    2=2" WHERE `name` = 'Stati dei contratti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_staticontratti`.`id`' WHERE `zz_modules`.`name` = 'Stati dei contratti' AND `zz_views`.`name` = 'id';

-- Aggiunta colonna N. utenti abilitati
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'N. utenti abilitati', '`utenti_abilitati`.`num`', '3', '1', '0', '0', '0', '', '', '1', '0', '0'); 

-- Aggiunta colonna N. API abilitate
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'N. API abilitate', '`api_abilitate`.`num`', '3', '1', '0', '0', '0', '', '', '1', '0', '0'); 

-- Aggiunta tabella co_statidocumento_lang
CREATE TABLE IF NOT EXISTS `co_statidocumento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_statidocumento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_statidocumento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_statidocumento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `co_statidocumento`;

ALTER TABLE `co_statidocumento`
    DROP `descrizione`;

ALTER TABLE `co_statidocumento` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `co_statidocumento_lang` ADD CONSTRAINT `co_statidocumento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_statidocumento`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Stati fatture
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_statidocumento`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND |lang|)
WHERE 
    1=1
HAVING 
    2=2" WHERE `name` = 'Stati fatture';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento`.`id`' WHERE `zz_modules`.`name` = 'Stati fatture' AND `zz_views`.`name` = 'id';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_anagrafiche`.`idanagrafica`' WHERE `zz_modules`.`name` = 'Tecnici e tariffe' AND `zz_views`.`name` = 'id';

-- Divisione chiave di licenza Wacom in 2 stringhe come da SDK v2
UPDATE `zz_settings` SET `valore` = '' WHERE `nome` = 'Licenza Wacom SDK';
UPDATE `zz_settings` SET `nome` = 'Licenza Wacom SDK - Key' WHERE `nome` = 'Licenza Wacom SDK';
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES ('Licenza Wacom SDK - Secret', '', 'string', '1', 'Tavoletta Wacom', '1');

-- Fix per errore creazione tabella an_sedi_tecnici v. 2.4.52
ALTER TABLE `an_sedi_tecnici`  CHANGE `updated_at` `updated_at` TIMESTAMP NULL on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `an_sedi_tecnici`  CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Aggiunto flag "Fatture Elettroniche" in segmenti
ALTER TABLE `zz_segments` ADD `for_fe` BOOLEAN NOT NULL AFTER `autofatture`;
UPDATE `zz_segments` SET `for_fe` = '1' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Fatture di vendita') OR `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Fatture di acquisto') AND `is_sezionale` = 1 AND `is_fiscale` = 1 AND `name` NOT LIKE '%non elettroniche%'; 

-- Aggiunto help impostazioni
UPDATE `zz_settings` SET `help` = 'Abilita esportazione delle viste anche nel formato xlsx e pdf' WHERE `zz_settings`.`nome` = 'Abilita esportazione Excel e PDF'; 

-- Miglioria plugin Regole pagamenti 
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Mese di chiusura, Giorno di riprogrammazione\", \"query\": \"SELECT id, IF(mese=\'01\', \'Gennaio\', IF(mese=\'02\', \'Febbraio\',IF(mese=\'03\', \'Marzo\',IF(mese=\'04\', \'Aprile\',IF(mese=\'05\', \'Maggio\', IF(mese=\'06\', \'Giugno\', IF(mese=\'07\', \'Luglio\',IF(mese=\'08\', \'Agosto\',IF(mese=\'09\', \'Settembre\', IF(mese=\'10\', \'Ottobre\', IF(mese=\'11\', \'Novembre\',\'Dicembre\'))))))))))) AS `Mese di chiusura`, giorno_fisso AS `Giorno di riprogrammazione` FROM an_pagamenti_anagrafiche WHERE 1=1 AND idanagrafica=|id_parent| GROUP BY id HAVING 2=2 ORDER BY an_pagamenti_anagrafiche.mese ASC\"} ]}' WHERE `zz_plugins`.`name` = 'Regole pagamenti'; 

-- Impostazione per data inizio verifica contatore fattura di vendita
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `help`) VALUES (NULL, 'Data inizio verifica contatore fattura di vendita', NULL, 'date', '1', 'Fatturazione', NULL);

-- Introduco tabella an_sdi per censire tutti i codici degli intermediari di fatturazione elettronica italiani
CREATE TABLE IF NOT EXISTS `an_sdi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255),
  `codice` varchar(7),
  PRIMARY KEY (`id`)
);

INSERT INTO an_sdi (nome, codice) VALUES
('Danea/TeamSystem', 'M5UXCR1'),
('Zucchetti', 'SUBM70N'),
('Wolters Kluwer', 'W7YVJK9'),
('Fattura PA', '5RUO82D'),
('Studio Rubino', 'T9K4ZHO'),
('Aruba', 'KRRH6B9'),
('WebClient', 'T04ZHR3'),
('Sistemi', 'USAL8PV'),
('LICON by Ix', 'A4707H7'),
('Buffetti', 'BA6ET11'),
('Tech Edge', '0G6TBBX'),
('Ente autonomo volturno', '2LCMINU'),
('Archivium srl', '3ZJY534'),
('Coldiretti', '5W4A8J1'),
('InfoCamere', '66OZKW1'),
('CloudFinance', '6JXPS2J'),
('Cia', '6RB0OU9'),
('Consorzio CIAT', 'AU7YEU4'),
('Alto Trevigiano Servizi', 'C1QQYZR'),
('Linea Ufficio', 'EH1R83N'),
('Danisoft', 'G1XGCBG'),
('Arthur Informatica', 'G4AI1U8'),
('BesideTech', 'G9HZJRW'),
('SeDiCo Servizi', 'G9YK3BM'),
('MultiWire', 'GR2P7ZP'),
('MySond', 'H348Q01'),
('Ediel', 'HHBD9AK'),
('DocEasy', 'J6URRTW'),
('InformItalia SRL', 'K0ROACV'),
('QuickMastro', 'KJSRCTG'),
('AGYO (Teamsystem)', 'KUPCRMI'),
('Var Group', 'M5ITOJA'),
('Nebu', 'MJEGRSK'),
('K Link Solutions', 'MSUXCR1'),
('Credemtel (gruppo Banca Credem)', 'MZO2A0U'),
('Kalyos', 'N9KM26R'),
('IDOCTORS', 'NKNH5UQ'),
('Extreme software', 'E2VWRNU'),
('Unimatica', 'E06UCUD'),
('CompEd', 'WHP7LTE');

-- Aggiunta tabella co_statipreventivi_lang
CREATE TABLE IF NOT EXISTS `co_statipreventivi_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_statipreventivi_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_statipreventivi_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_statipreventivi_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `co_statipreventivi`;

ALTER TABLE `co_statipreventivi`
    DROP `descrizione`;

ALTER TABLE `co_statipreventivi` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `co_statipreventivi_lang` ADD CONSTRAINT `co_statipreventivi_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_statipreventivi`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Stati dei preventivi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_statipreventivi`
    LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND |lang|)
WHERE 
    1=1
HAVING 
    2=2" WHERE `name` = 'Stati dei preventivi';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statipreventivi`.`id`' WHERE `zz_modules`.`name` = 'Stati dei preventivi' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella co_tipidocumento_lang
CREATE TABLE IF NOT EXISTS `co_tipidocumento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_tipidocumento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_tipidocumento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_tipidocumento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `co_tipidocumento`;

ALTER TABLE `co_tipidocumento`
    DROP `descrizione`;

ALTER TABLE `co_tipidocumento` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `co_tipidocumento_lang` ADD CONSTRAINT `co_tipidocumento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_tipidocumento`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Tipi documento
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_tipidocumento`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.|lang|)
    LEFT JOIN `zz_segments` ON `co_tipidocumento`.`id_segment` = `zz_segments`.`id` 
    LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.|lang|)
WHERE 
    1=1 AND `deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Tipi documento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento`.`id`' WHERE `zz_modules`.`name` = 'Tipi documento' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella co_tipi_scadenze_lang
CREATE TABLE IF NOT EXISTS `co_tipi_scadenze_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NOT NULL
);
ALTER TABLE `co_tipi_scadenze_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `co_tipi_scadenze_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `co_tipi_scadenze_lang` (`id`, `id_lang`, `id_record`, `name`, `description`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome`, `descrizione` FROM `co_tipi_scadenze`;

ALTER TABLE `co_tipi_scadenze`
    DROP `nome`,
    DROP `descrizione`;

ALTER TABLE `co_tipi_scadenze_lang` ADD CONSTRAINT `co_tipi_scadenze_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_tipi_scadenze`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella do_categorie_lang
CREATE TABLE IF NOT EXISTS `do_categorie_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `do_categorie_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `do_categorie_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `do_categorie_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `do_categorie`;

ALTER TABLE `do_categorie`
    DROP `descrizione`;

ALTER TABLE `do_categorie` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `do_categorie_lang` ADD CONSTRAINT `do_categorie_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `do_categorie`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`do_categorie`.`id`' WHERE `zz_modules`.`name` = 'Categorie documenti' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella dt_aspettobeni_lang
CREATE TABLE IF NOT EXISTS `dt_aspettobeni_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `dt_aspettobeni_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `dt_aspettobeni_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `dt_aspettobeni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `dt_aspettobeni`;

ALTER TABLE `dt_aspettobeni`
    DROP `descrizione`;

ALTER TABLE `dt_aspettobeni` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_aspettobeni_lang` ADD CONSTRAINT `dt_aspettobeni_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_aspettobeni`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Aspetto beni
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `dt_aspettobeni`
    LEFT JOIN `dt_aspettobeni_lang` ON (`dt_aspettobeni_lang`.`id_record` = `dt_aspettobeni`.`id` AND `dt_aspettobeni_lang`.|lang|)
WHERE 
    1=1 
HAVING 
    2=2" WHERE `name` = 'Aspetto beni';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_aspettobeni`.`id`' WHERE `zz_modules`.`name` = 'Aspetto beni' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella dt_causalet_lang
CREATE TABLE IF NOT EXISTS `dt_causalet_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `dt_causalet_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `dt_causalet_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `dt_causalet_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `dt_causalet`;

ALTER TABLE `dt_causalet`
    DROP `descrizione`;

ALTER TABLE `dt_causalet` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_causalet_lang` ADD CONSTRAINT `dt_causalet_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_causalet`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Causali trasporto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `dt_causalet`
    LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.|lang|)
WHERE 
    1=1 AND `deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Causali';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet`.`id`' WHERE `zz_modules`.`name` = 'Causali' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella dt_porto_lang
CREATE TABLE IF NOT EXISTS `dt_porto_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `dt_porto_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `dt_porto_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `dt_porto_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `dt_porto`;

ALTER TABLE `dt_porto`
    DROP `descrizione`;

ALTER TABLE `dt_porto` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_porto_lang` ADD CONSTRAINT `dt_porto_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_porto`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Porto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `dt_porto` 
    LEFT JOIN `dt_porto_lang` ON (`dt_porto_lang`.`id_record` = `dt_porto`.`id` AND `dt_porto_lang`.|lang|)
WHERE 
    1=1 
HAVING 
    2=2" WHERE `name` = 'Porto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_porto`.`id`' WHERE `zz_modules`.`name` = 'Porto' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella dt_spedizione_lang
CREATE TABLE IF NOT EXISTS `dt_spedizione_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `dt_spedizione_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `dt_spedizione_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `dt_spedizione_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `dt_spedizione`;

ALTER TABLE `dt_spedizione`
    DROP `descrizione`;

ALTER TABLE `dt_spedizione` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_spedizione_lang` ADD CONSTRAINT `dt_spedizione_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_spedizione`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Tipi di spedizione
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `dt_spedizione`
    LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id` AND `dt_spedizione_lang`.|lang|)
WHERE 
    1=1 
HAVING 
    2=2" WHERE `name` = 'Tipi di spedizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione`.`id`' WHERE `zz_modules`.`name` = 'Tipi di spedizione' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella dt_statiddt_lang
CREATE TABLE IF NOT EXISTS `dt_statiddt_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `dt_statiddt_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `dt_statiddt_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `dt_statiddt_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `dt_statiddt`;

ALTER TABLE `dt_statiddt`
    DROP `descrizione`;

ALTER TABLE `dt_statiddt` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_statiddt_lang` ADD CONSTRAINT `dt_statiddt_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_statiddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Ddt di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.|lang|)
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id` AND `dt_spedizione_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN(SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt` 
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
    LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\DDT\\DDT' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`idddt` = `dt_ddt`.`id`
WHERE
    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = 'uscita' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.`created_at` DESC" WHERE `name` = 'Ddt di acquisto';

-- Aggiunta tabella dt_tipiddt_lang
CREATE TABLE IF NOT EXISTS `dt_tipiddt_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `dt_tipiddt_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `dt_tipiddt_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `dt_tipiddt_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `dt_tipiddt`;

ALTER TABLE `dt_tipiddt`
    DROP `descrizione`;

ALTER TABLE `dt_tipiddt` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_tipiddt_lang` ADD CONSTRAINT `dt_tipiddt_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_tipiddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella em_lists_lang
CREATE TABLE IF NOT EXISTS `em_lists_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL
);
ALTER TABLE `em_lists_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `em_lists_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `em_lists_lang` (`id`, `id_lang`, `id_record`, `name`, `description`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `description` FROM `em_lists`;

ALTER TABLE `em_lists`
    DROP `description`;

ALTER TABLE `em_lists` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `em_lists_lang` ADD CONSTRAINT `em_lists_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `em_lists`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Liste newsletter
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `em_lists`
    LEFT JOIN `em_lists_lang` ON (`em_lists_lang`.`id_record` = `em_lists`.`id` AND `em_lists_lang`.|lang|)
WHERE 
    1=1 AND deleted_at IS NULL 
HAVING 
    2=2" WHERE `name` = 'Liste newsletter';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_lists`.`id`' WHERE `zz_modules`.`name` = 'Liste newsletter' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_lists_lang`.`description`' WHERE `zz_modules`.`name` = 'Liste newsletter' AND `zz_views`.`name` = 'Descrizione';

-- Aggiunta tabella em_templates_lang
CREATE TABLE IF NOT EXISTS `em_templates_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL
);
ALTER TABLE `em_templates_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `em_templates_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `em_templates_lang` (`id`, `id_lang`, `id_record`, `name`, `subject`, `body`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `subject`, `body` FROM `em_templates`;

ALTER TABLE `em_templates`
    DROP `subject`,
    DROP `body`;

ALTER TABLE `em_templates_lang` ADD CONSTRAINT `em_templates_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `em_templates`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates_lang`.`subject`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Oggetto';

-- Allineamento vista Newsletter
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `em_newsletters` 
    LEFT JOIN `em_templates` ON `em_newsletters`.`id_template` = `em_templates`.`id`
    LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.|lang|)
    LEFT JOIN (SELECT `id_newsletter`, COUNT(*) AS totale FROM `em_newsletter_receiver` GROUP BY  `id_newsletter`) AS riceventi ON `riceventi`.`id_newsletter` = `em_newsletters`.`id`
WHERE 
    1=1 AND `em_newsletters`.`deleted_at` IS NULL
HAVING 
    2=2" WHERE `name` = 'Newsletter';

-- Aggiunta tabella fe_modalita_pagamento_lang
CREATE TABLE IF NOT EXISTS `fe_modalita_pagamento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` varchar(4) NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `fe_modalita_pagamento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `fe_modalita_pagamento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `fe_modalita_pagamento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `codice`, `descrizione` FROM `fe_modalita_pagamento`;

ALTER TABLE `fe_modalita_pagamento`
    DROP `descrizione`;

ALTER TABLE `fe_modalita_pagamento_lang` ADD CONSTRAINT `fe_modalita_pagamento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_modalita_pagamento`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella fe_natura_lang
CREATE TABLE IF NOT EXISTS `fe_natura_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` varchar(5) NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `fe_natura_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `fe_natura_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `fe_natura_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `codice`, `descrizione` FROM `fe_natura`;

ALTER TABLE `fe_natura`
    DROP `descrizione`;

ALTER TABLE `fe_natura_lang` ADD CONSTRAINT `fe_natura_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_natura`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella fe_regime_fiscale_lang
CREATE TABLE IF NOT EXISTS `fe_regime_fiscale_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` varchar(5) NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `fe_regime_fiscale_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `fe_regime_fiscale_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `fe_regime_fiscale_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `codice`, `descrizione` FROM `fe_regime_fiscale`;

ALTER TABLE `fe_regime_fiscale`
    DROP `descrizione`;

ALTER TABLE `fe_regime_fiscale_lang` ADD CONSTRAINT `fe_regime_fiscale_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_regime_fiscale`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella fe_stati_documento_lang
CREATE TABLE IF NOT EXISTS `fe_stati_documento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` varchar(5) NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `fe_stati_documento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `fe_stati_documento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `fe_stati_documento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `codice`, `descrizione` FROM `fe_stati_documento`;

ALTER TABLE `fe_stati_documento`
    DROP `descrizione`;

ALTER TABLE `fe_stati_documento_lang` ADD CONSTRAINT `fe_stati_documento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_stati_documento`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella fe_tipi_documento_lang
CREATE TABLE IF NOT EXISTS `fe_tipi_documento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` varchar(5) NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `fe_tipi_documento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `fe_tipi_documento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `fe_tipi_documento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `codice`, `descrizione` FROM `fe_tipi_documento`;

ALTER TABLE `fe_tipi_documento`
    DROP `descrizione`;

ALTER TABLE `fe_tipi_documento_lang` ADD CONSTRAINT `fe_tipi_documento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_tipi_documento`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella in_fasceorarie_lang
CREATE TABLE IF NOT EXISTS `in_fasceorarie_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `in_fasceorarie_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `in_fasceorarie_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `in_fasceorarie_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `in_fasceorarie`;

ALTER TABLE `in_fasceorarie`
    DROP `nome`;

ALTER TABLE `in_fasceorarie` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `in_fasceorarie_lang` ADD CONSTRAINT `in_fasceorarie_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `in_fasceorarie`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Fasce orarie
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `in_fasceorarie`
    LEFT JOIN `in_fasceorarie_lang` ON (`in_fasceorarie_lang`.`id_record` = `in_fasceorarie`.`id` AND `in_fasceorarie_lang`.|lang|)
WHERE 
    1=1 AND deleted_at IS NULL 
HAVING 
    2=2" WHERE `name` = 'Fasce orarie';

-- Aggiunta tabella in_statiintervento_lang
CREATE TABLE IF NOT EXISTS `in_statiintervento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `in_statiintervento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `in_statiintervento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `in_statiintervento` CHANGE `idstatointervento` `id` INT NOT NULL AUTO_INCREMENT; 

INSERT INTO `in_statiintervento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `in_statiintervento`;

ALTER TABLE `in_statiintervento`
    DROP `descrizione`;

ALTER TABLE `in_statiintervento_lang` ADD CONSTRAINT `in_statiintervento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `in_statiintervento`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Stati di intervento
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `in_statiintervento`
    LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.|lang|)
WHERE 
    1=1 AND `deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Stati di intervento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento`.`id`' WHERE `zz_modules`.`name` = 'Stati di intervento' AND `zz_views`.`name` = 'id';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE id NOT IN (SELECT idintervento FROM in_interventi_tecnici) AND idstatointervento IN (SELECT id FROM in_statiintervento WHERE is_completato = 0) ' WHERE `zz_widgets`.`name` = 'Attività da pianificare'; 
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.id FROM in_statiintervento WHERE in_statiintervento.codice=\'TODO\') ORDER BY in_interventi.data_richiesta ASC' WHERE `zz_widgets`.`name` = 'Attività nello stato da programmare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.id FROM in_statiintervento WHERE in_statiintervento.codice=\'WIP\') ORDER BY in_interventi.data_richiesta ASC' WHERE `zz_widgets`.`name` = 'Attività confermate'; 

-- Aggiunta tabella in_tipiintervento_lang
CREATE TABLE IF NOT EXISTS `in_tipiintervento_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `in_tipiintervento_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `in_tipiintervento_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `in_tipiintervento` CHANGE `idtipointervento` `id` INT NOT NULL AUTO_INCREMENT; 

INSERT INTO `in_tipiintervento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `in_tipiintervento`;

ALTER TABLE `in_tipiintervento`
    DROP `descrizione`;

ALTER TABLE `in_tipiintervento_lang` ADD CONSTRAINT `in_tipiintervento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Tipi di intervento
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `in_tipiintervento`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.|lang|)
WHERE 
    1=1 AND `deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Tipi di intervento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento`.`id`' WHERE `zz_modules`.`name` = 'Tipi di intervento' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella mg_articoli_lang
CREATE TABLE IF NOT EXISTS `mg_articoli_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `mg_articoli_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `mg_articoli_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `mg_articoli_lang` ADD CONSTRAINT `mg_articoli_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella mg_attributi_lang
CREATE TABLE IF NOT EXISTS `mg_attributi_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL
);
ALTER TABLE `mg_attributi_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `mg_attributi_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `mg_attributi_lang` (`id`, `id_lang`, `id_record`, `name`, `title`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome`, `titolo` FROM `mg_attributi`;

ALTER TABLE `mg_attributi`
    DROP `nome`,
    DROP `titolo`;

ALTER TABLE `mg_attributi_lang` ADD CONSTRAINT `mg_attributi_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `mg_attributi`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Attributi Combinazioni
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `mg_attributi` 
    LEFT JOIN `mg_attributi_lang` ON (`mg_attributi`.`id` = `mg_attributi_lang`.`id_record` AND `mg_attributi_lang`.|lang|)
WHERE 
    1=1 AND 
    `mg_attributi`.`deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Attributi Combinazioni';

-- Aggiunta tabella mg_categorie_lang
CREATE TABLE IF NOT EXISTS `mg_categorie_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `mg_categorie_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `mg_categorie_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `mg_categorie_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `mg_categorie`;

ALTER TABLE `mg_categorie`
    DROP `nome`;

ALTER TABLE `mg_categorie_lang` ADD CONSTRAINT `mg_categorie_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `mg_categorie`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Categorie articoli
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `mg_categorie`
    LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id` = `mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.|lang|)
WHERE 
    1=1 AND `parent` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Categorie articoli';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_categorie`.`id`' WHERE `zz_modules`.`name` = 'Categorie articoli' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella my_impianti_categorie_lang
CREATE TABLE IF NOT EXISTS `my_impianti_categorie_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `my_impianti_categorie_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `my_impianti_categorie_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `my_impianti_categorie_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `my_impianti_categorie`;

ALTER TABLE `my_impianti_categorie`
    DROP `nome`;

ALTER TABLE `my_impianti_categorie_lang` ADD CONSTRAINT `my_impianti_categorie_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `my_impianti_categorie`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Categorie impianti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `my_impianti_categorie`
    LEFT JOIN `my_impianti_categorie_lang` ON (`my_impianti_categorie`.`id` = `my_impianti_categorie_lang`.`id_record` AND `my_impianti_categorie_lang`.|lang|) 
WHERE 
    1=1 AND parent IS NULL 
HAVING 
    2=2" WHERE `name` = 'Categorie impianti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`my_impianti_categorie`.`id`' WHERE `zz_modules`.`name` = 'Categorie impianti' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella mg_causali_movimenti_lang
CREATE TABLE IF NOT EXISTS `mg_causali_movimenti_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NOT NULL
);
ALTER TABLE `mg_causali_movimenti_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `mg_causali_movimenti_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `mg_causali_movimenti_lang` (`id`, `id_lang`, `id_record`, `name`, `description`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome`, `descrizione` FROM `mg_causali_movimenti`;

ALTER TABLE `mg_causali_movimenti`
    DROP `nome`,
    DROP `descrizione`;

ALTER TABLE `mg_causali_movimenti_lang` ADD CONSTRAINT `mg_causali_movimenti_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `mg_causali_movimenti`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Causali movimenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `mg_causali_movimenti`
    LEFT JOIN `mg_causali_movimenti_lang` ON (`mg_causali_movimenti`.`id` = `mg_causali_movimenti_lang`.`id_record` AND `mg_causali_movimenti_lang`.|lang|)
WHERE 
    1=1 
HAVING 
    2=2" WHERE `name` = 'Causali movimenti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_causali_movimenti_lang`.`description`' WHERE `zz_modules`.`name` = 'Causali movimenti' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_causali_movimenti`.`id`' WHERE `zz_modules`.`name` = 'Causali movimenti' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella mg_combinazioni_lang
CREATE TABLE IF NOT EXISTS `mg_combinazioni_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `mg_combinazioni_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `mg_combinazioni_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `mg_combinazioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `mg_combinazioni`;

ALTER TABLE `mg_combinazioni`
    DROP `nome`;

ALTER TABLE `mg_combinazioni_lang` ADD CONSTRAINT `mg_combinazioni_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `mg_combinazioni`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Combinazioni
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `mg_combinazioni`
    LEFT JOIN `mg_combinazioni_lang` ON (`mg_combinazioni`.`id` = `mg_combinazioni_lang`.`id_record` AND `mg_combinazioni_lang`.|lang|)
WHERE 
    1=1 AND 
    `mg_combinazioni`.`deleted_at` IS NULL 
GROUP BY
    `mg_combinazioni`.`id`
HAVING 
    2=2" WHERE `name` = 'Combinazioni';

-- Aggiunta tabella or_statiordine_lang
CREATE TABLE IF NOT EXISTS `or_statiordine_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `or_statiordine_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `or_statiordine_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `or_statiordine_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `or_statiordine`;

ALTER TABLE `or_statiordine`
    DROP `descrizione`;

ALTER TABLE `or_statiordine` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `or_statiordine_lang` ADD CONSTRAINT `or_statiordine_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `or_statiordine`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Stati degli ordini
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `or_statiordine`
    LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.|lang|)
WHERE 
    1=1 AND 
    deleted_at IS NULL 
HAVING 
    2=2" WHERE `name` = 'Stati degli ordini';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`or_statiordine`.`id`' WHERE `zz_modules`.`name` = 'Stati degli ordini' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella or_tipiordine_lang
CREATE TABLE IF NOT EXISTS `or_tipiordine_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `or_tipiordine_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `or_tipiordine_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `or_tipiordine_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `descrizione` FROM `or_tipiordine`;

ALTER TABLE `or_tipiordine`
    DROP `descrizione`;

ALTER TABLE `or_tipiordine` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `or_tipiordine_lang` ADD CONSTRAINT `or_tipiordine_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `or_tipiordine`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Introduzione stampa cespiti
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Magazzino cespiti', '', 'query=SELECT id, nomesede AS descrizione FROM an_sedi WHERE idanagrafica=(SELECT valore FROM zz_settings WHERE nome=\'Azienda predefinita\')', '1', 'Magazzino', NULL, 'Magazzino cespiti per la stampa dei cespiti dal modulo articoli');

INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES
(NULL, 'Stampa cespiti', 'print', 21, 'controller_top', 'col-md-3', '', '#45a9f1', 'fa fa-print', '', './modules/articoli/widgets/stampa_cespiti.php', 'popup', '', 'Stampa cespiti', 1, 1, NULL);

INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE name = 'Articoli'), 0, 'Inventario cespiti', 'Inventario cespiti', 'Cespiti', 'magazzino_cespiti', '', '', 'fa fa-print', '', '', 0, 0, 1, 1);

-- Aggiunta tabella zz_currencies_lang
CREATE TABLE IF NOT EXISTS `zz_currencies_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL
);
ALTER TABLE `zz_currencies_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_currencies_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_currencies_lang` (`id`, `id_lang`, `id_record`, `name`, `title`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `title` FROM `zz_currencies`;

ALTER TABLE `zz_currencies`
    DROP `title`;

ALTER TABLE `zz_currencies_lang` ADD CONSTRAINT `zz_currencies_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_currencies`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_currencies`.`id` AS id, CONCAT(`title`, \' - \', `symbol`) AS text FROM zz_currencies LEFT JOIN `zz_currencies_lang` ON (`zz_currencies_lang`.`id_record` = `zz_currencies`.`id` AND `zz_currencies_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = 'Valuta'; 

-- Aggiunta tabella zz_widgets_lang
CREATE TABLE IF NOT EXISTS `zz_widgets_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `text` VARCHAR(255) NOT NULL
);
ALTER TABLE `zz_widgets_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_widgets_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_widgets_lang` (`id`, `id_lang`, `id_record`, `name`, `text`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `text` FROM `zz_widgets`;

ALTER TABLE `zz_widgets`
    DROP `text`;

ALTER TABLE `zz_widgets_lang` ADD CONSTRAINT `zz_widgets_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_widgets`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(co_preventivi.id) AS dato FROM co_preventivi INNER JOIN co_statipreventivi ON co_preventivi.idstato = co_statipreventivi.id LEFT JOIN co_statipreventivi_lang ON (co_statipreventivi_lang.id_record = co_statipreventivi.id AND co_statipreventivi_lang.id_lang = (SELECT valore FROM zz_settings WHERE nome = "Lingua")) WHERE name =\"In lavorazione\" AND default_revision=1' WHERE `zz_widgets`.`id` = (SELECT `id_record` FROM `zz_widgets_lang` WHERE  `name` = 'Preventivi in lavorazione'); 

-- Aggiunta tabella zz_plugins_lang
CREATE TABLE IF NOT EXISTS `zz_plugins_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL
);
ALTER TABLE `zz_plugins_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_plugins_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_plugins_lang` (`id`, `id_lang`, `id_record`, `name`, `title`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `title` FROM `zz_plugins`;

ALTER TABLE `zz_plugins`
    DROP `title`;

ALTER TABLE `zz_plugins_lang` ADD CONSTRAINT `zz_plugins_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_modules_lang
CREATE TABLE IF NOT EXISTS `zz_modules_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL
);
ALTER TABLE `zz_modules_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_modules_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_modules_lang` (`id`, `id_lang`, `id_record`, `name`, `title`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `title` FROM `zz_modules`;

ALTER TABLE `zz_modules`
    DROP `title`;

ALTER TABLE `zz_modules_lang` ADD CONSTRAINT `zz_modules_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules`.`id`' WHERE `zz_modules_lang`.`name` = 'Viste' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules`.`id`' WHERE `zz_modules_lang`.`name` = 'Viste' AND `zz_views`.`name` = 'Numero';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Viste' AND `zz_views`.`name` = 'Nome';

-- Allineamento vista Campi personalizzati
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `zz_fields`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_fields`.`id_module`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)
    LEFT JOIN `zz_plugins` ON `zz_plugins`.`id` = `zz_fields`.`id_plugin`
    LEFT JOIN `zz_plugins_lang` ON (`zz_plugins_lang`.`id_record` = `zz_plugins`.`id` AND `zz_plugins_lang`.|lang|)
WHERE
    1=1
HAVING
    2=2" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Campi personalizzati');
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Campi personalizzati' AND `zz_views`.`name` = 'Modulo';

-- Allineamento vista Checklists
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `zz_checklists`
    LEFT JOIN `zz_modules` ON `zz_checklists`.`id_module` = `zz_modules`.`id`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)
    LEFT JOIN `zz_plugins` ON `zz_checklists`.`id_plugin`=`zz_plugins`.`id`
    LEFT JOIN `zz_plugins_lang` ON (`zz_plugins_lang`.`id_record` = `zz_plugins`.`id` AND `zz_plugins_lang`.|lang|)
WHERE
    1=1
HAVING
    2=2" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Checklists');
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Checklists' AND `zz_views`.`name` = 'Modulo';

-- Allineamento vista Prima nota
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `co_movimenti`
    INNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`idconto` = `co_pianodeiconti3`.`id`
    LEFT JOIN `co_documenti` ON `co_documenti`.`id` = `co_movimenti`.`iddocumento`
    LEFT JOIN `an_anagrafiche` ON `co_movimenti`.`id_anagrafica` = `an_anagrafiche`.`idanagrafica`
WHERE
    1=1 AND `primanota` = 1  |date_period(`co_movimenti`.`data`)|
GROUP BY
    `idmastrino`,
    `primanota`,
    `co_movimenti`.`data`,
    `numero_esterno`,
    `co_movimenti`.`descrizione`,
    `an_anagrafiche`.`ragione_sociale`
HAVING
    2=2
ORDER BY
    `co_movimenti`.`data` DESC" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Prima nota');

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Segmenti' AND `zz_views`.`name` = 'Modulo';

-- Allineamento vista Stampe
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `zz_prints`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_prints`.`id_module`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)
WHERE 
    1=1 
    AND `zz_prints`.`enabled`=1 
HAVING 
    2=2" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Stampe');
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Stampe' AND `zz_views`.`name` = 'Modulo';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Template email' AND `zz_views`.`name` = 'Modulo';

-- Aggiunte note in impianto-intervento
ALTER TABLE `my_impianti_interventi` ADD `note` TEXT NOT NULL AFTER `idimpianto`;

-- Aggiornato plugin Impianti
UPDATE `zz_plugins` SET `script` = '', `directory` = 'impianti_intervento', `options` = 'custom' WHERE `zz_plugins`.`id` = (SELECT `id_record` FROM `zz_plugins_lang` WHERE  `name` = 'Impianti');

DROP TABLE IF EXISTS `in_vociservizio`;

DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Voci di servizio');
DELETE FROM `zz_modules` WHERE `id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Voci di servizio');
DELETE FROM `zz_modules_lang` WHERE `name` = 'Voci di servizio';

-- Api per campi personalizzati
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES (NULL, 'app-v1', 'retrieve', 'campi-personalizzati', 'API\\App\\v1\\CampiPersonalizzati', '1');
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES (NULL, 'app-v1', 'retrieve', 'campi-personalizzati-cleanup', 'API\\App\\v1\\CampiPersonalizzati', '1');

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'app-v1', 'retrieve', 'campi-personalizzati-valori', 'API\\App\\v1\\CampiPersonalizzatiValori', 1),
(NULL, 'app-v1', 'retrieve', 'campi-personalizzati-valori-cleanup', 'API\\App\\v1\\CampiPersonalizzatiValori', 1),
(NULL, 'app-v1', 'update', 'campi-personalizzati-valori', 'API\\App\\v1\\CampiPersonalizzatiValori', 1);

-- Allineamento vista Tipi scadenze
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_tipi_scadenze`
    LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.|lang|)
WHERE 
    1=1
HAVING
    2=2" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Tipi scadenze');
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`co_tipi_scadenze`.`id`' WHERE `zz_modules_lang`.`name` = 'Tipi scadenze' AND `zz_views`.`name` = 'id';

-- Aggiunta tabella zz_segments_lang
CREATE TABLE IF NOT EXISTS `zz_segments_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `zz_segments_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_segments_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_segments_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_segments`;

ALTER TABLE `zz_segments_lang` ADD CONSTRAINT `zz_segments_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_segments`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_views_lang
CREATE TABLE IF NOT EXISTS `zz_views_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);
ALTER TABLE `zz_views_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_views_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_views_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_views`;

ALTER TABLE `zz_views_lang` ADD CONSTRAINT `zz_views_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_views`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_settings_lang
CREATE TABLE IF NOT EXISTS `zz_settings_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `help` VARCHAR(500) NOT NULL
);

ALTER TABLE `zz_settings_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_settings_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome`, `help` FROM `zz_settings`;

ALTER TABLE `zz_settings`
    DROP `help`;

ALTER TABLE `zz_settings_lang` ADD CONSTRAINT `zz_settings_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_settings`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `in_statiintervento`.`id`, `name` AS text FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE is_completato = 1' WHERE `zz_settings`.`nome` = "Stato dell'attività alla chiusura";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `in_statiintervento`.`id`, `name` AS text FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Stato dell'attività dopo la firma";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `in_statiintervento`.`id`, `name` AS text FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Stato predefinito dell'attività";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `in_statiintervento`.`id`, `name` AS text FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Stato predefinito dell'attività da Dashboard";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `co_iva`.`id`, `name` AS text FROM `co_iva` LEFT JOIN `co_iva_lang` ON (`co_iva_lang`.`id_record` = `co_iva`.`id` AND `co_iva_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Iva predefinita";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `co_pagamenti`.`id`, `name` AS descrizione FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Tipo di pagamento predefinito";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `co_iva`.`id`, IF(`codice_natura_fe` IS NULL, IF(`codice` IS NULL, `name`, CONCAT(`codice`, " - ", `name`)), CONCAT( IF(`codice` IS NULL, `name`, CONCAT(`codice`, " - ", `name`)), " (", `codice_natura_fe`, ")" )) AS descrizione  FROM `co_iva` LEFT JOIN `co_iva_lang` ON (`co_iva_lang`.`id_record` = `co_iva`.`id` AND `co_iva_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `deleted_at` IS NULL ORDER BY `name` ASC' WHERE `zz_settings`.`nome` = "Iva da applicare su marca da bollo";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `co_iva`.`id`, CONCAT(`codice`," - ",`name`) AS descrizione FROM `co_iva` LEFT JOIN `co_iva_lang` ON (`co_iva_lang`.`id_record` = `co_iva`.`id` AND `co_iva_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE codice_natura_fe LIKE "N3.%" AND `deleted_at` IS NULL ORDER BY `name` ASC' WHERE `zz_settings`.`nome` = "Iva per lettere d'intento";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_segments`.`id`, `name` AS descrizione FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments_lang`.`id_record` = `zz_segments`.`id` AND `zz_segments_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `name`="Fatture di vendita") ORDER BY `name`' WHERE `zz_settings`.`nome` = "Sezionale per autofatture di vendita";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_segments`.`id`, `name` AS descrizione FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments_lang`.`id_record` = `zz_segments`.`id` AND `zz_segments_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `name`="Fatture di acquisto") ORDER BY `name`' WHERE `zz_settings`.`nome` = "Sezionale per autofatture di acquisto";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `codice` AS id, CONCAT(`codice`, " - ", `name`)as descrizione FROM `fe_regime_fiscale` LEFT JOIN `fe_regime_fiscale_lang` ON (`fe_regime_fiscale_lang`.`id_record`=`fe_regime_fiscale`.`codice` AND `fe_regime_fiscale_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Regime fiscale";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS id, `ragione_sociale` AS descrizione FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `idtipoanagrafica` FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `name` = "Fornitore") AND `deleted_at` IS NULL' WHERE `zz_settings`.`nome` = "Terzo intermediario";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_modules`.`id`, `title` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `enabled` = 1 AND `options` != "" AND `options` != "menu" AND `options` IS NOT NULL ORDER BY `order` ASC' WHERE `zz_settings`.`nome` = "Prima pagina";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_currencies`.`id`, `name` AS descrizione FROM `zz_currencies` LEFT JOIN `zz_currencies_lang` ON (`zz_currencies_lang`.`id_record` = `zz_currencies`.`id` AND `zz_currencies_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Valuta";

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `em_templates`.`id`, `name` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `zz_settings`.`nome` = "Template email invio sollecito";

-- Aggiunta tabella zz_tasks_lang
CREATE TABLE IF NOT EXISTS `zz_tasks_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_tasks_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_tasks_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_tasks_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_tasks`;

ALTER TABLE `zz_tasks_lang` ADD CONSTRAINT `zz_tasks_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_tasks`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Gestione task
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `zz_tasks` 
    LEFT JOIN `zz_tasks_lang` ON (`zz_tasks_lang`.`id_record` = `zz_tasks`.`id` AND `zz_tasks_lang`.|lang|)
WHERE 
    1=1 
HAVING 
    2=2" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Gestione task');
UPDATE `zz_views` LEFT JOIN `zz_views_lang` ON (`zz_views_lang`.`id_record` = `zz_views`.`id` AND `zz_views_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_tasks`.`id`' WHERE `zz_modules_lang`.`name` = 'Gestione task' AND `zz_views_lang`.`name` = 'id';

-- Aggiunta tabella zz_prints_lang
CREATE TABLE IF NOT EXISTS `zz_prints_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `filename` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_prints_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_prints_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_prints_lang` (`id`, `id_lang`, `id_record`, `name`, `title`, `filename`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name`, `title`, `filename` FROM `zz_prints`;

ALTER TABLE `zz_prints`
    DROP `title`,
    DROP `filename`;

ALTER TABLE `zz_prints_lang` ADD CONSTRAINT `zz_prints_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_prints`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Stampe
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `zz_prints`
    LEFT JOIN `zz_prints_lang` ON (`zz_prints_lang`.`id_record` = `zz_prints`.`id` AND `zz_prints_lang`.|lang|)
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_prints`.`id_module`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)
WHERE 
    1=1 
    AND `zz_prints`.`enabled`=1 
HAVING 
    2=2" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Stampe');
UPDATE `zz_views` LEFT JOIN `zz_views_lang` ON (`zz_views_lang`.`id_record` = `zz_views`.`id` AND `zz_views_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_prints_lang`.`title`' WHERE `zz_modules_lang`.`name` = 'Stampe' AND `zz_views_lang`.`name` = 'Titolo';
UPDATE `zz_views` LEFT JOIN `zz_views_lang` ON (`zz_views_lang`.`id_record` = `zz_views`.`id` AND `zz_views_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = '`zz_prints_lang`.`filename`' WHERE `zz_modules_lang`.`name` = 'Stampe' AND `zz_views_lang`.`name` = 'Nome del file';

-- Aggiunta tabella zz_imports_lang
CREATE TABLE IF NOT EXISTS `zz_imports_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_imports_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_imports_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_imports_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_imports`;

ALTER TABLE `zz_imports_lang` ADD CONSTRAINT `zz_imports_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_imports`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_hooks_lang
CREATE TABLE IF NOT EXISTS `zz_hooks_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_hooks_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_hooks_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_hooks_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_hooks`;

ALTER TABLE `zz_hooks_lang` ADD CONSTRAINT `zz_hooks_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_hooks`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_groups_lang
CREATE TABLE IF NOT EXISTS `zz_groups_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_groups_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_groups_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_groups_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `nome` FROM `zz_groups`;

ALTER TABLE `zz_groups_lang` ADD CONSTRAINT `zz_groups_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_group_module_lang
CREATE TABLE IF NOT EXISTS `zz_group_module_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_group_module_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_group_module_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_group_module_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_group_module`;

ALTER TABLE `zz_group_module_lang` ADD CONSTRAINT `zz_group_module_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_group_module`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Aggiunta tabella zz_cache_lang
CREATE TABLE IF NOT EXISTS `zz_cache_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `name` VARCHAR(255) NOT NULL
);

ALTER TABLE `zz_cache_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_cache_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_cache_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), `id`, `name` FROM `zz_cache`;

ALTER TABLE `zz_cache_lang` ADD CONSTRAINT `zz_cache_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `zz_cache`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Raggruppa attività per tipologia in fattura', '0', 'boolean', '1', 'Fatturazione');
INSERT INTO `zz_settings_lang` (`id_record`, `id_lang`, `title`) VALUES ((SELECT `id` FROM `zz_settings` WHERE `nome` = 'Raggruppa attività per tipologia in fattura'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"), 'Raggruppa attività per tipologia in fattura');

-- Introduzione adattatori di archiviazione
CREATE TABLE `zz_storage_adapters` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `options` text NOT NULL,
  `can_delete` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL,
  `is_local` tinyint(1) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `zz_storage_adapters` ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_storage_adapters` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_storage_adapters` (`id`, `name`, `class`, `options`, `can_delete`, `is_default`, `is_local`, `deleted_at`) VALUES
(1, 'Adattatore locale', '\\Modules\\FileAdapters\\Adapters\\LocalAdapter', '{ \"directory\":\"/files\" }', 0, 1, 1, NULL);

-- Modulo adattatori di archiviazione
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Adattatori di archiviazione', 'adattatori_archiviazione', 'SELECT |select| FROM zz_storage_adapters WHERE 1=1 HAVING 2=2', '', 'fa fa-folder', '2.5', '2.5', '2', (SELECT id FROM zz_modules m WHERE directory='adattatori_archiviazione'), '1', '1', '0', '0');
INSERT INTO `zz_modules_lang` (`id`, `id_lang`, `id_record`, `name`, `title`) VALUES (NULL, '1', (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione'), 'Adattatori di archiviazione', 'Adattatori di archiviazione');

-- Viste modulo adattatori di archiviazione
INSERT INTO `zz_views` (`id`, `id_module`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione'), 'id', 1, 0, 0, 0, 0, '', '', 0, 0, 0),
(NULL, (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione'), 'name', 2, 1, 0, 0, 0, '', '', 1, 0, 0),
(NULL, (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione'), 'if(is_default=1, "fa fa-check", "")', 2, 1, 0, 0, 0, '', '', 1, 0, 0);

INSERT INTO `zz_views_lang` (`id`, `id_lang`, `id_record`, `name`) VALUES
(NULL, 1, (SELECT id FROM zz_views WHERE id_module = (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione') AND query = 'id'), 'id'),
(NULL, 1, (SELECT id FROM zz_views WHERE id_module = (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione') AND query = 'name'), 'Nome'),
(NULL, 1, (SELECT id FROM zz_views WHERE id_module = (SELECT id FROM zz_modules WHERE directory='adattatori_archiviazione') AND query = 'if(is_default=1, "fa fa-check", "")'), 'icon_Predefinito');

ALTER TABLE `zz_files` ADD `id_adapter` INT NOT NULL AFTER `id_record`;
UPDATE zz_files SET id_adapter=1;

DELETE FROM `zz_settings` WHERE `nome` = 'Iva da applicare su marca da bollo';

-- Aggiunta gestione stato documento Non valida
INSERT INTO `co_statidocumento` (`icona`, `colore`) VALUES ('fa fa-times text-muted', '#d3d3d3');
INSERT INTO `co_statidocumento_lang` (`id_record`, `id_lang`, `name`) VALUES ((SELECT MAX(`id`) FROM `co_statidocumento`), (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"), 'Non valida');

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Giorni validità fattura scartata', '5', 'int', '0', 'Fatturazione Elettronica');
INSERT INTO `zz_settings_lang` (`id_record`, `id_lang`, `title`, `help`) VALUES ((SELECT `id` FROM `zz_settings` WHERE `nome` = 'Giorni validità fattura scartata'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"), 'Giorni validità fattura scartata','Numero di giorni a disposizione per poter correggere una fattura scartata dallo SDI prima di non poter più utilizzare il suo numero di fatturazione. Una volta passati i giorni indicati è necessario emettere una nuova fattura e questa passa in stato Non valida.');

ALTER TABLE `in_interventi` ADD `idpagamento` INT NOT NULL AFTER `id_ordine`;

UPDATE `zz_views` SET `query` = '(`righe`.`totale_imponibile` + IF(`co_documenti`.`split_payment` = 0, `righe`.`iva`, 0) + `co_documenti`.`rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale` - IF(`co_documenti`.`id_ritenuta_contributi` != 0, (( `righe`.`totale_imponibile` * `co_ritenuta_contributi`.`percentuale_imponibile` / 100) / 100 * `co_ritenuta_contributi`.`percentuale`), 0)) *(1 - `co_documenti`.`sconto_finale_percentuale` / 100 ) * IF(`co_tipidocumento`.`reversed`, -1, 1)' WHERE `zz_views`.`name` = 'Netto a pagare' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');

-- Risorsa api per la sincronizzazione dei pagamenti su app
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES 
(NULL, 'app-v1', 'retrieve', 'pagamenti', 'API\\App\\v1\\Pagamenti', '1'), 
(NULL, 'app-v1', 'retrieve', 'pagamenti-cleanup', 'API\\App\\v1\\Pagamenti', '1'), 
(NULL, 'app-v1', 'retrieve', 'pagamento', 'API\\App\\v1\\Pagamenti', '1');

ALTER TABLE `mg_categorie` CHANGE `nota` `nota` VARCHAR(1000) NULL; 
ALTER TABLE `mg_categorie` CHANGE `colore` `colore` VARCHAR(255) NULL; 

ALTER TABLE `my_impianti_categorie` CHANGE `nota` `nota` VARCHAR(1000) NULL; 
ALTER TABLE `my_impianti_categorie` CHANGE `colore` `colore` VARCHAR(255) NULL; 

-- Esclusioni default in preventivi
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Esclusioni default preventivi', '', 'textarea', '1', 'Preventivi', NULL);
INSERT INTO `zz_settings_lang` (`id_record`, `id_lang`, `title`) VALUES ((SELECT `id` FROM `zz_settings` WHERE `nome` = 'Esclusioni default preventivi'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"), 'Esclusioni default preventivi');