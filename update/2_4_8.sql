ALTER TABLE `zz_logs` CHANGE `username` `username` varchar(255);


ALTER TABLE `dt_righe_ddt` ADD `id_conto` INT(11) NOT NULL AFTER `qta_evasa`;
ALTER TABLE `or_righe_ordini` ADD `id_conto` INT(11) NOT NULL AFTER `qta_evasa`;


ALTER TABLE `dt_righe_ddt` ADD `id_iva` INT(11) NOT NULL AFTER `id_conto`;
ALTER TABLE `or_righe_ordini` ADD `id_iva` INT(11) NOT NULL AFTER `id_conto`;