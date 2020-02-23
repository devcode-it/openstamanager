-- Fix problema username vuoti in zz_logs
ALTER TABLE `zz_logs` CHANGE `username` `username` varchar(255);

-- Aggiunta tipologia di documento Parcella
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Parcella', 'entrata', '0', 'TD06');
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Parcella', 'uscita', '0', 'TD06');

-- Aggiunto codice cig e codice cup per contratti e interventi
ALTER TABLE `co_contratti` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
ALTER TABLE `in_interventi` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
ALTER TABLE `or_ordini` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
ALTER TABLE `co_preventivi` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;

-- Aggiunta data scadenza attività
ALTER TABLE `in_interventi` ADD `data_scadenza` DATETIME AFTER `data_invio`;
