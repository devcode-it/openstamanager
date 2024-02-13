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
    `is_rtl` tinyint(1) NOT NULL
);

ALTER TABLE `zz_langs`
    ADD PRIMARY KEY (`id`); 

ALTER TABLE `zz_langs`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_langs` (`id`, `name`, `enabled`, `iso_code`, `language_code`, `date`, `time`, `timestamp`, `decimals`, `thousands`, `is_rtl`) VALUES (NULL, 'Italiano (Italian)', '1', 'it', 'it-it', 'd/m/Y', 'H:i', 'd/m/Y H:i', ',', NULL, 0); 
INSERT INTO `zz_langs` (`id`, `name`, `enabled`, `iso_code`, `language_code`, `date`, `time`, `timestamp`, `decimals`, `thousands`, `is_rtl`) VALUES (NULL, 'English (English)', '1', 'en', 'en-gb', 'm/d/Y', 'H:i', 'm/d/Y H:i', ',', NULL, 0); 

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

INSERT INTO `an_nazioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `nome` FROM `an_nazioni`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_nazioni_lang`.`name`' WHERE `zz_modules`.`name` = 'Eventi' AND `zz_views`.`name` = 'Nazione';

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

INSERT INTO `an_provenienze_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `an_provenienze`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_provenienze_lang`.`name`' WHERE `zz_modules`.`name` = 'Provenienze' AND `zz_views`.`name` = 'descrizione';

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

INSERT INTO `an_regioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `nome` FROM `an_regioni`;

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

INSERT INTO `an_relazioni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `an_relazioni`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_relazioni_lang`.`name`' WHERE `zz_modules`.`name` = 'Relazioni' AND `zz_views`.`name` = 'descrizione';

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

INSERT INTO `an_settori_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `an_settori`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_settori_lang`.`name`' WHERE `zz_modules`.`name` = 'Settori' AND `zz_views`.`name` = 'descrizione';

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

INSERT INTO `an_tipianagrafiche_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `an_tipianagrafiche`;

ALTER TABLE `an_tipianagrafiche`
    DROP `descrizione`;

ALTER TABLE `an_tipianagrafiche_lang` ADD CONSTRAINT `an_tipianagrafiche_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `an_tipianagrafiche`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'GROUP_CONCAT(\' \',`an_relazioni_lang`.`name`)' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'color_title_Relazione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'GROUP_CONCAT(\' \',`an_tipianagrafiche_lang`.`name`)' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Tipo';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_tipianagrafiche_lang`.`name`' WHERE `zz_modules`.`name` = 'Tipi di anagrafiche' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_tipianagrafiche`.`id`' WHERE `zz_modules`.`name` = 'Tipi di anagrafiche' AND `zz_views`.`name` = 'id';

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS id, `ragione_sociale` AS descrizione FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `an_tipianagrafiche`.`id` FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON(`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record`) WHERE `name` = \'Azienda\') AND `deleted_at` IS NULL' WHERE `zz_settings`.`nome` = 'Azienda predefinita'; 

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

INSERT INTO `co_iva_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `co_iva`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_iva_lang`.`name`' WHERE `zz_modules`.`name` = 'IVA' AND `zz_views`.`name` = 'descrizione';
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

INSERT INTO `co_pagamenti_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `co_pagamenti`;

ALTER TABLE `co_pagamenti`
    DROP `descrizione`;

ALTER TABLE `co_pagamenti_lang` ADD CONSTRAINT `co_pagamenti_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_pagamenti`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Pagamenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_pagamenti`
	LEFT JOIN (SELECT `fe_modalita_pagamento`.`codice`, CONCAT(`fe_modalita_pagamento`.`codice`, ' - ', `fe_modalita_pagamento`.`descrizione`) AS tipo FROM `fe_modalita_pagamento`) AS pagamenti ON `pagamenti`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND |lang|)
WHERE
    1=1
GROUP BY
    `co_pagamenti`.`id`
HAVING
    2=2" WHERE `name` = 'Pagamenti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`name`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'COUNT(`co_pagamenti_lang`.`name`)' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Rate';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`id`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'id';

-- Allineamento vista Anagrafiche
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_anagrafiche`
    LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
    LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id` = `an_relazioni_lang`.`id_record` AND `an_relazioni_lang`.|lang|)
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.|lang|)
    LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(`nomesede` SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY `idanagrafica`) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`
    LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(`nome` SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY `idanagrafica`) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`
    LEFT JOIN (SELECT `co_pagamenti_lang`.`name`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|))AS pagvendita ON IF(`an_anagrafiche`.`idpagamento_vendite`>0,`an_anagrafiche`.`idpagamento_vendite`= `pagvendita`.`id`,'')
    LEFT JOIN (SELECT `co_pagamenti_lang`.`name`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|))AS pagacquisto ON IF(`an_anagrafiche`.`idpagamento_acquisti`>0,`an_anagrafiche`.`idpagamento_acquisti`= `pagacquisto`.`id`,'')
WHERE
    1=1 AND `an_anagrafiche`.`deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`, `pagvendita`.`nome`, `pagacquisto`.`nome`
HAVING
    2=2
ORDER BY
    TRIM(`ragione_sociale`)" WHERE `name` = 'Anagrafiche';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`name`' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Tipo di pagamento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Pagamento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Pagamento';

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

INSERT INTO `co_staticontratti_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `co_staticontratti`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_staticontratti_lang`.`name`' WHERE `zz_modules`.`name` = 'Stati dei contratti' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Contratti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
    LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br>') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN(SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN(SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
HAVING 
    2=2" WHERE `name` = 'Contratti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_staticontratti_lang`.`name`' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'icon_title_Stato';

-- Aggiunta colonna N. utenti abilitati e N. API abilitate
UPDATE `zz_modules` SET `options` = 'SELECT\n |select|\nFROM \n `zz_groups` \n LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`\n LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` WHERE `zz_users`. `enabled` = 1  GROUP BY `idgruppo`) AS utenti_abilitati ON `zz_groups`.`id`=`utenti`.`idgruppo`\n LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_tokens`.`id`) AS num FROM `zz_users` INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente` WHERE `zz_tokens`. `enabled` = 1  GROUP BY `idgruppo`) AS api_abilitate ON `zz_groups`.`id`=`utenti`.`idgruppo`\n LEFT JOIN (SELECT `zz_modules`.`title`, `zz_modules`.`id` FROM `zz_modules`) AS `module` ON `module`.`id`=`zz_groups`.`id_module_start`\nWHERE \n 1=1\nHAVING \n 2=2 \nORDER BY \n `id`, \n `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi'; 

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

INSERT INTO `co_statidocumento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `co_statidocumento`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Stati fatture' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Fatture di vendita
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento` FROM `co_movimenti`  WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND co_statidocumento_lang.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(id) as `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN(SELECT `id` FROM `zz_modules` WHERE name = 'Fatture di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
	LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND co_pagamenti_lang.|lang|)
	LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN( SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata') AND `numero_esterno` != '' GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento` HAVING COUNT(`numero_esterno`) > 1 |date_period(`co_documenti`.`data`)| ) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1=1 AND `dir` = 'entrata' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Fatture di vendita';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_Stato';


-- Allineamento vista Fatture di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`nome`, ' - ', `iban`) AS `descrizione` FROM `co_banche`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN (SELECT `iddocumento`, GROUP_CONCAT(`co_pianodeiconti3`.`descrizione`) AS `descrizione` FROM `co_righe_documenti` INNER JOIN `co_pianodeiconti3` ON `co_pianodeiconti3`.`id` = `co_righe_documenti`.`idconto` GROUP BY iddocumento) AS `conti` ON `conti`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`idanagrafica` AS `anagrafica`, `d`.`id_segment` FROM `co_documenti` AS `d`
    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `d`.`id_segment`, `numero_documento`, `d`.`idanagrafica`) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`idanagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment`)
WHERE 
    1=1 
AND 
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)| |date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    `co_documenti`.`id`, `d`.`conteggio`
HAVING 
    2=2
ORDER BY 
    `co_documenti`.`data` DESC,
    CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = 'Fatture di acquisto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'icon_title_Stato';

-- Fix plugin Ddt del cliente
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Numero, Data, Descrizione, Qtà\", \"query\": \"SELECT dt_ddt.id, IF(dt_tipiddt.dir = \'entrata\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di vendita\'), (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di acquisto\')) AS _link_module_, dt_ddt.id AS _link_record_, IF(dt_ddt.numero_esterno = \'\', dt_ddt.numero, dt_ddt.numero_esterno) AS Numero, DATE_FORMAT(dt_ddt.data, \'%d/%m/%Y\') AS Data, dt_righe_ddt.descrizione AS `Descrizione`, REPLACE(REPLACE(REPLACE(FORMAT(dt_righe_ddt.qta, 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\') AS `Qtà` FROM dt_ddt LEFT JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt JOIN dt_tipiddt ON dt_ddt.idtipoddt = dt_tipiddt.id WHERE dt_ddt.idanagrafica=|id_parent| HAVING 2=2 ORDER BY dt_ddt.id DESC\"} ]}' WHERE `zz_plugins`.`name` = 'Ddt del cliente'; 

-- Allineamento vista Scadenzario
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenziario`
    LEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN (SELECT COUNT(id_email) as emails, zz_operations.id_record FROM zz_operations WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Scadenzario') AND `zz_operations`.`op` = 'send-email' GROUP BY zz_operations.id_record) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`
WHERE 
    1=1 AND (`co_statidocumento_lang`.`name` IS NULL OR `co_statidocumento_lang`.`name` IN('Emessa','Parzialmente pagato','Pagato')) 
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `name` = 'Scadenzario';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'descrizione';

UPDATE `zz_widgets` SET `query` = "SELECT\n    CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n    SELECT SUM(\n    (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`) * IF(`co_tipidocumento`.`reversed`, -1, 1)\n    )\n    ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM \n    `co_righe_documenti`\n    INNER JOIN `co_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`\n    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\n    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\n    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)\nWHERE \n    `co_statidocumento_lang`.`name`!=\'Bozza\' AND `co_tipidocumento`.`dir`=\'entrata\' |segment(`co_documenti`.`id_segment`)| AND `data` >= \'|period_start|\' AND `data` <= \'|period_end|\" AND 1=1" WHERE `zz_widgets`.`name` = 'Fatturato';