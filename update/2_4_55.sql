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

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`name`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'COUNT(`co_pagamenti_lang`.`name`)' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Rate';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti`.`id`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'id';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_staticontratti`.`id`' WHERE `zz_modules`.`name` = 'Stati dei contratti' AND `zz_views`.`name` = 'id';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_Stato';
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

UPDATE `zz_widgets` SET `query` = "SELECT\n    CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n    SELECT SUM(\n    (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`) * IF(`co_tipidocumento`.`reversed`, -1, 1)\n    )\n    ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM \n    `co_righe_documenti`\n    INNER JOIN `co_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`\n    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\n    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\n    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)\nWHERE \n    `co_statidocumento_lang`.`name`!=\'Bozza\' AND `co_tipidocumento`.`dir`=\'entrata\' |segment(`co_documenti`.`id_segment`)| AND `data` >= \'|period_start|\' AND `data` <= \'|period_end|\' AND 1=1" WHERE `zz_widgets`.`name` = 'Fatturato';

-- Allineamento vista Tecnici e tariffe
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    an_anagrafiche
    INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica = an_tipianagrafiche_anagrafiche.idanagrafica
    LEFT JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.id
    LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)
WHERE 
    1=1 AND an_tipianagrafiche_lang.name = 'Tecnico' AND an_anagrafiche.`deleted_at` IS NULL
HAVING 
    2=2 
ORDER BY 
    ragione_sociale" WHERE `name` = 'Tecnici e tariffe';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_anagrafiche`.`idanagrafica`' WHERE `zz_modules`.`name` = 'Tecnici e tariffe' AND `zz_views`.`name` = 'id';

-- Divisione chiave di licenza Wacom in 2 stringhe come da SDK v2
UPDATE `zz_settings` SET `valore` = '' WHERE `nome` = 'Licenza Wacom SDK';
UPDATE `zz_settings` SET `nome` = 'Licenza Wacom SDK - Key' WHERE `nome` = 'Licenza Wacom SDK';
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES ('Licenza Wacom SDK - Secret', '', 'string', '1', 'Tavoletta Wacom', '1');

-- Allineamento vista Utenti e Permessi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `zz_groups`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` WHERE `zz_users`. `enabled` = 1 GROUP BY `idgruppo`) AS utenti_abilitati ON `zz_groups`.`id`=`utenti_abilitati`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_tokens`.`id`) AS num FROM `zz_users` INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente` WHERE `zz_tokens`. `enabled` = 1 GROUP BY `idgruppo`) AS api_abilitate ON `zz_groups`.`id`=`utenti`.`idgruppo`
    LEFT JOIN (SELECT `zz_modules`.`title`, `zz_modules`.`id` FROM `zz_modules`) AS `module` ON `module`.`id`=`zz_groups`.`id_module_start`
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `id`, `nome` ASC" WHERE `name` = 'Utenti e permessi';

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

INSERT INTO `co_statipreventivi_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `co_statipreventivi`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statipreventivi_lang`.`name`' WHERE `zz_modules`.`name` = 'Stati dei preventivi' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statipreventivi`.`id`' WHERE `zz_modules`.`name` = 'Stati dei preventivi' AND `zz_views`.`name` = 'id';

-- Allineamento vista Preventivi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `co_preventivi`
    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND co_statipreventivi_lang.|lang|)
    LEFT JOIN (SELECT `idpreventivo`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_preventivi` GROUP BY `idpreventivo`) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
    LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`)AS agente ON `agente`.`idanagrafica`=`co_preventivi`.`idagente`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idpreventivo` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\Preventivi\\Preventivo' GROUP BY `idpreventivo`, `original_document_id`) AS `fattura` ON `fattura`.`idpreventivo` = `co_preventivi`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Preventivi') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_preventivi`.`id`
WHERE 
    1=1 |segment(`co_preventivi`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)| AND `default_revision` = 1
GROUP BY 
    `co_preventivi`.`id`, `fattura`.`info`
HAVING 
    2=2
ORDER BY 
    `co_preventivi`.`id` DESC" WHERE `name` = 'Preventivi';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statipreventivi_lang`.`name`' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'icon_title_Stato';

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

INSERT INTO `co_tipidocumento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `co_tipidocumento`;

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
WHERE 
    1=1 AND `deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = 'Tipi documento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Tipi documento' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento`.`id`' WHERE `zz_modules`.`name` = 'Tipi documento' AND `zz_views`.`name` = 'id';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Tipo';

-- Allineamento vista Fatture di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.|lang|)
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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Tipo';

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

INSERT INTO `do_categorie_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `do_categorie`;

ALTER TABLE `do_categorie`
    DROP `descrizione`;

ALTER TABLE `do_categorie` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `do_categorie_lang` ADD CONSTRAINT `do_categorie_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `do_categorie`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Gestione documentale
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `do_documenti`
    INNER JOIN `do_categorie` ON `do_categorie`.`id` = `do_documenti`.`idcategoria`
    LEFT JOIN `do_categorie_lang` ON (`do_categorie_lang`.`id_record` = `do_categorie`.`id` AND `do_categorie_lang`.|lang|)
WHERE 
    1=1 AND `deleted_at` IS NULL AND
    (SELECT `idgruppo` FROM `zz_users` WHERE `zz_users`.`id` = |id_utente|) IN (SELECT `id_gruppo` FROM `do_permessi` WHERE `id_categoria` = `do_documenti`.`idcategoria`)
    |date_period(`data`)| OR data IS NULL
HAVING 
    2=2 
ORDER BY 
    `data` DESC" WHERE `name` = 'Gestione documentale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`do_categorie_lang`.`name`' WHERE `zz_modules`.`name` = 'Gestione documentale' AND `zz_views`.`name` = 'Categoria';

-- Allineamento vista Categorie documenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
   `do_categorie`
   LEFT JOIN `do_categorie_lang` ON (`do_categorie_lang`.`id_record` = `do_categorie`.`id` AND `do_categorie_lang`.|lang|)
WHERE 
    1=1 AND `deleted_at` IS NULL AND
    (SELECT `idgruppo` FROM `zz_users` WHERE `id` = |id_utente|) IN (SELECT `id_gruppo` FROM `do_permessi` WHERE `id_categoria` = `do_categorie`.`id`)
HAVING
    2=2" WHERE `name` = 'Categorie documenti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`do_categorie_lang`.`name`' WHERE `zz_modules`.`name` = 'Categorie documenti' AND `zz_views`.`name` = 'Descrizione';
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

INSERT INTO `dt_aspettobeni_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `dt_aspettobeni`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_aspettobeni_lang`.`name`' WHERE `zz_modules`.`name` = 'Aspetto beni' AND `zz_views`.`name` = 'Descrizione';
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

INSERT INTO `dt_causalet_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `dt_causalet`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet_lang`.`name`' WHERE `zz_modules`.`name` = 'Causali' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet`.`id`' WHERE `zz_modules`.`name` = 'Causali' AND `zz_views`.`name` = 'id';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet_lang`.`name`' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Causale';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet_lang`.`name`' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Causale';

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

INSERT INTO `dt_porto_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `dt_porto`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_porto_lang`.`name`' WHERE `zz_modules`.`name` = 'Porto' AND `zz_views`.`name` = 'Descrizione';
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

INSERT INTO `dt_spedizione_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `dt_spedizione`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione_lang`.`name`' WHERE `zz_modules`.`name` = 'Tipi di spedizione' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione`.`id`' WHERE `zz_modules`.`name` = 'Tipi di spedizione' AND `zz_views`.`name` = 'id';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione_lang`.`name`' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Tipo spedizione';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione_lang`.`name`' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Tipo spedizione';

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

INSERT INTO `dt_statiddt_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `dt_statiddt`;

ALTER TABLE `dt_statiddt`
    DROP `descrizione`;

ALTER TABLE `dt_statiddt` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `dt_statiddt_lang` ADD CONSTRAINT `dt_statiddt_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `dt_statiddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Ddt di vendita
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
    LEFT JOIN (SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
    LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.|lang|)    
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\DDT\\DDT' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`idddt` = `dt_ddt`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Ddt di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY id_record) AS `email` ON `email`.`id_record` = `dt_ddt`.`id`
WHERE
    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = 'entrata' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.`created_at` DESC" WHERE `name` = 'Ddt di vendita';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_statiddt_lang`.`name`' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'icon_title_Stato';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_statiddt_lang`.`name`' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'icon_title_Stato';

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

INSERT INTO `dt_tipiddt_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `dt_tipiddt`;

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
    `description` VARCHAR(255) NOT NULL
);
ALTER TABLE `em_lists_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `em_lists_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `em_lists_lang` (`id`, `id_lang`, `id_record`, `name`, `description`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `name`, `description` FROM `em_lists`;

ALTER TABLE `em_lists`
    DROP `description`,
    DROP `name`;

ALTER TABLE `em_lists` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT; 

ALTER TABLE `em_lists_lang` ADD CONSTRAINT `em_lists_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `em_lists_lang`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_lists_lang`.`name`' WHERE `zz_modules`.`name` = 'Liste newsletter' AND `zz_views`.`name` = 'Nome';

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

INSERT INTO `em_templates_lang` (`id`, `id_lang`, `id_record`, `name`, `subject`, `body`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `name`, `subject`, `body` FROM `em_templates`;

ALTER TABLE `em_templates`
    DROP `name`,
    DROP `subject`,
    DROP `body`;

ALTER TABLE `em_templates_lang` ADD CONSTRAINT `em_templates_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `em_templates`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Template email
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `em_templates`
    LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.|lang|)
    INNER JOIN `zz_modules` on `zz_modules`.`id` = `em_templates`.`id_module`
WHERE
    1=1 AND `deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `zz_modules`.`name`" WHERE `name` = 'Template email';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates_lang`.`subject`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Oggetto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates_lang`.`name`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Nome';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates_lang`.`name`' WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Template';

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

INSERT INTO `fe_modalita_pagamento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `codice`, `descrizione` FROM `fe_modalita_pagamento`;

ALTER TABLE `fe_modalita_pagamento`
    DROP `descrizione`;

ALTER TABLE `fe_modalita_pagamento_lang` ADD CONSTRAINT `fe_modalita_pagamento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_modalita_pagamento`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Pagamenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_pagamenti`
	LEFT JOIN (SELECT `fe_modalita_pagamento`.`codice`, CONCAT(`fe_modalita_pagamento`.`codice`, ' - ', `fe_modalita_pagamento_lang`.`name`) AS tipo FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento`.`codice` = `fe_modalita_pagamento_lang`.`id_record` AND `fe_modalita_pagamento_lang`.|lang|)) AS pagamenti ON `pagamenti`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
WHERE
    1=1
GROUP BY
    `co_pagamenti_lang`.`name`
HAVING
    2=2" WHERE `name` = 'Pagamenti';

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

INSERT INTO `fe_natura_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `codice`, `descrizione` FROM `fe_natura`;

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

INSERT INTO `fe_regime_fiscale_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `codice`, `descrizione` FROM `fe_regime_fiscale`;

ALTER TABLE `fe_regime_fiscale`
    DROP `descrizione`;

ALTER TABLE `fe_regime_fiscale_lang` ADD CONSTRAINT `fe_regime_fiscale_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_regime_fiscale`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `codice` AS id, CONCAT(`codice`, \' - \', `name`)as descrizione FROM fe_regime_fiscale LEFT JOIN `fe_regime_fiscale_lang` ON (`fe_regime_fiscale_lang`.`id_record`=`fe_regime_fiscale`.`codice` AND `fe_regime_fiscale_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \'Lingua\'))' WHERE `zz_settings`.`nome` = 'Regime fiscale'; 

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

INSERT INTO `fe_stati_documento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `codice`, `descrizione` FROM `fe_stati_documento`;

ALTER TABLE `fe_stati_documento`
    DROP `descrizione`;

ALTER TABLE `fe_stati_documento_lang` ADD CONSTRAINT `fe_stati_documento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `fe_stati_documento`(`codice`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Fatture di vendita
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento` FROM `co_movimenti`  WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND co_tipidocumento_lang.|lang|)
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record` AND `fe_stati_documento_lang`.|lang|)
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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`fe_stati_documento_lang`.`name`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_FE';

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

INSERT INTO `fe_tipi_documento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `codice`, `descrizione` FROM `fe_tipi_documento`;

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

INSERT INTO `in_fasceorarie_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `nome` FROM `in_fasceorarie`;

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_fasceorarie_lang`.`name`' WHERE `zz_modules`.`name` = 'Fasce orarie' AND `zz_views`.`name` = 'Nome';

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

INSERT INTO `in_statiintervento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `in_statiintervento`;

ALTER TABLE `in_statiintervento`
    DROP `descrizione`;

ALTER TABLE `in_statiintervento_lang` ADD CONSTRAINT `in_statiintervento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `in_statiintervento`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento_lang`.`name`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Stato';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento_lang`.`name`' WHERE `zz_modules`.`name` = 'Stati di intervento' AND `zz_views`.`name` = 'descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento`.`id`' WHERE `zz_modules`.`name` = 'Stati di intervento' AND `zz_views`.`name` = 'id';


UPDATE `zz_plugins` SET `options` = '{\"main_query\": [{\"type\": \"table\", \"fields\": \"Numero, Data inizio, Data fine, Tipo\", \"query\": \"SELECT in_interventi.id, in_interventi.codice AS Numero, DATE_FORMAT(MAX(orario_inizio),\'%d/%m/%Y\') AS \'Data inizio\', DATE_FORMAT(MAX(orario_fine),\'%d/%m/%Y\') AS \'Data fine\', (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS \'Tipo\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Interventi\') AS _link_module_, in_interventi.id AS _link_record_ FROM in_interventi LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` WHERE 1=1 AND in_interventi.deleted_at IS NULL AND idanagrafica = |id_parent| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY in_interventi.id DESC\"}]}' WHERE `zz_plugins`.`name` = 'Storico attività'; 

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

INSERT INTO `in_tipiintervento_lang` (`id`, `id_lang`, `id_record`, `name`) SELECT NULL, (SELECT `id` FROM `zz_langs` WHERE `iso_code` = 'it'), `id`, `descrizione` FROM `in_tipiintervento`;

ALTER TABLE `in_tipiintervento`
    DROP `descrizione`;

ALTER TABLE `in_tipiintervento_lang` ADD CONSTRAINT `in_tipiintervento_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

-- Allineamento vista Attività
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `idintervento`, SUM(`prezzo_unitario`*`qta`-`sconto`) AS `ricavo_righe`, SUM(`costo_unitario`*`qta`) AS `costo_righe` FROM `in_righe_interventi` GROUP BY `idintervento`) AS `righe` ON `righe`.`idintervento` = `in_interventi`.`id`
    INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
    LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.|lang|)
    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />',IF(`an_sedi`.`telefono`!='',CONCAT(`an_sedi`.`telefono`,'<br />'),''),IF(`an_sedi`.`cellulare`!='',CONCAT(`an_sedi`.`cellulare`,'<br />'),''),`an_sedi`.`citta`,IF(`an_sedi`.`indirizzo`!='',CONCAT(' - ',`an_sedi`.`indirizzo`),'')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `in_interventi`.`idsede_destinazione`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idintervento` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = 'Modules\\Interventi\\Intervento' GROUP BY `idintervento`, `original_document_id`) AS `fattura` ON `fattura`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS `tecnici_assegnati` ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS `tecnici` ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Interventi') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS `descrizione`, `my_impianti_interventi`.`idintervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto` GROUP BY `my_impianti_interventi`.`idintervento`) AS `impianti` ON `impianti`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `co_contratti`.`id`, CONCAT(`co_contratti`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_contratti`) AS `contratto` ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (SELECT `co_preventivi`.`id`, CONCAT(`co_preventivi`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_preventivi`) AS `preventivo` ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (SELECT `or_ordini`.`id`, CONCAT(`or_ordini`.`numero`, ' del ', DATE_FORMAT(`data`, '%d/%m/%Y')) AS `info` FROM `or_ordini`) AS `ordine` ON `ordine`.`id` = `in_interventi`.`id_ordine`
    INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.|lang|)
    LEFT JOIN( SELECT zz_files.* FROM zz_files INNER JOIN zz_modules ON zz_files.id_module = zz_modules.id WHERE zz_modules.name = 'Interventi' ) AS zz_files ON zz_files.id_record = in_interventi.id
WHERE 
    1=1 |segment(`in_interventi`.`id_segment`)| |date_period(`orario_inizio`,`data_richiesta`)|
GROUP BY 
    `in_interventi`.`id`
HAVING 
    2=2
ORDER BY 
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `name` = 'Interventi';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento_lang`.`name`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Tipo';

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
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento_lang`.`name`' WHERE `zz_modules`.`name` = 'Tipi di intervento' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento`.`id`' WHERE `zz_modules`.`name` = 'Tipi di intervento' AND `zz_views`.`name` = 'id';