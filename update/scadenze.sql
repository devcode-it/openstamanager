
-- Aggiunto nuovo sistema di gestione Scadenze
CREATE TABLE IF NOT EXISTS `co_gruppi_scadenze` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_documento` int(11) DEFAULT NULL,
    `descrizione` varchar(255) NOT NULL,
    `note` TEXT,
    `data_emissione` date DEFAULT NULL,
    `totale_pagato` decimal(12, 6) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(`id`),
    FOREIGN KEY (`id_documento`) REFERENCES `co_documenti`(`id`)
) ENGINE=InnoDB;

ALTER TABLE `co_scadenze` RENAME TO `co_scadenze`;
ALTER TABLE `co_scadenze` ADD `id_gruppo` INT(11), ADD FOREIGN KEY (`id_gruppo`) REFERENCES `co_gruppi_scadenze`(`id`);

ALTER TABLE `co_gruppi_scadenze` ADD `id_scadenza_origine` INT(11);

-- Inserimento gruppi per Documenti
INSERT INTO `co_gruppi_scadenze` (`id_scadenza_origine`, `id_documento`, `descrizione`, `data_emissione`, `totale_pagato`) SELECT `id`, `iddocumento`, `descrizione`, `data_emissione`, SUM(`pagato`) FROM `co_scadenze` WHERE `iddocumento` != 0 GROUP BY `iddocumento`;

UPDATE `co_scadenze` INNER JOIN `co_gruppi_scadenze` ON `co_scadenze`.`iddocumento` = `co_gruppi_scadenze`.`id_documento` SET `co_scadenze`.`id_gruppo` =  `co_gruppi_scadenze`.`id`;

-- Inserimento gruppi per Scadenze indipendenti
INSERT INTO `co_gruppi_scadenze` (`id_scadenza_origine`, `id_documento`, `descrizione`, `data_emissione`, `totale_pagato`) SELECT `id`, `iddocumento`, `descrizione`, `data_emissione`, `pagato` FROM `co_scadenze` WHERE `iddocumento` = 0;

UPDATE `co_scadenze` INNER JOIN `co_gruppi_scadenze` ON `co_scadenze`.`id` = `co_gruppi_scadenze`.`id_scadenza_origine` SET `co_scadenze`.`id_gruppo` =  `co_gruppi_scadenze`.`id`;

-- ALTER TABLE `co_gruppi_scadenze` DROP `id_scadenza_origine`;

-- Correzioni per il modulo Scadenzario
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_scadenze`
    INNER JOIN `co_gruppi_scadenze` ON `co_gruppi_scadenze`.`id` = `co_scadenze`.`id_gruppo`
    LEFT JOIN `co_documenti` ON `co_gruppi_scadenze`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
WHERE 1=1 AND
    (`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN(''Emessa'',''Parzialmente pagato'',''Pagato''))
HAVING 2=2
ORDER BY `co_scadenze`.`scadenza` ASC' WHERE `zz_modules`.`name` = 'Scadenzario';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), '_link_record_', 'co_gruppi_scadenze.id', 0, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), '_link_hash_', 'CONCAT(''scadenza_'', co_scadenze.id)', 0, 0, 0);

UPDATE `zz_views` SET `query` = 'co_scadenze.scadenza' WHERE `name` = 'Data scadenza' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'co_scadenze.id' WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'co_gruppi_scadenze.data_emissione' WHERE `name` = 'Data emissione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'co_scadenze.da_pagare' WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'co_scadenze.pagato' WHERE `name` = 'Pagato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'co_gruppi_scadenze.descrizione' WHERE `name` = 'Descrizione scadenza' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'IF(an_anagrafiche.ragione_sociale IS NULL, co_gruppi_scadenze.descrizione, an_anagrafiche.ragione_sociale)' WHERE `name` = 'Anagrafica' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_views` SET `query` = 'co_scadenze.note' WHERE `name` = 'Note' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name = 'Scadenzario');

UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'co_scadenziario', 'co_scadenze');
UPDATE `zz_segments` SET `clause` = REPLACE(`clause`, 'co_scadenziario', 'co_scadenze');