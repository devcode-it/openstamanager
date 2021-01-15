-- Rimozione campo formattabile su "Causale" e "Sede destinazione" dei Ddt
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`format` = 0 WHERE `zz_views`.`name` IN('Causale', 'Sede partenza') AND `zz_modules`.`name` IN('Ddt di vendita', 'Ddt di acquisto');

-- Aggiunta colonna reversed
ALTER TABLE `dt_causalet` ADD `reversed` TINYINT(1) NOT NULL AFTER `is_importabile`;
UPDATE `dt_causalet` SET `reversed`=1 WHERE `descrizione`='Reso';

-- Ottimizzazione per ricerca articoli da ajax select
ALTER TABLE `mg_movimenti` ADD INDEX(`idarticolo`);