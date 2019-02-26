ALTER TABLE `zz_logs` CHANGE `username` `username` varchar(255);


ALTER TABLE `dt_righe_ddt` ADD `id_conto` INT(11) NOT NULL AFTER `qta_evasa`;
ALTER TABLE `co_righe_contratti` ADD `id_conto` INT(11) NOT NULL AFTER `qta_evasa`;
ALTER TABLE `co_righe_preventivi` ADD `id_conto` INT(11) NOT NULL AFTER `qta_evasa`;
ALTER TABLE `or_righe_ordini` ADD `id_conto` INT(11) NOT NULL AFTER `qta_evasa`;


INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Parcella', 'entrata', '0', 'TD06');
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Parcella', 'uscita', '0', 'TD06');