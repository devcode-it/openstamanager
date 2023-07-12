-- Aggiunta ai contratti il collegamento con il contratto precedente
ALTER TABLE `co_contratti` ADD `idcontratto_prev` INT NOT NULL;

-- Aggiunta vista dashboard (mese,settimana,giorno)
INSERT INTO `zz_impostazioni` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Vista dashboard', 'settimana', 'list[mese,settimana,giorno]', '1', 'Generali');

-- Aggiungo nuovi valori predefiniti per le anagrafiche
ALTER TABLE  `an_anagrafiche` ADD  `idtipointervento_default` VARCHAR(25) NOT NULL;

-- Creo tabella my_impianti_contratti
CREATE TABLE IF NOT EXISTS `my_impianti_contratti` (
  `idcontratto` varchar(25) NOT NULL,
  `matricola` varchar(25) NOT NULL
) ENGINE=InnoDB;

-- Aggiunta sesso nelle anagrafiche
ALTER TABLE `an_anagrafiche` ADD `sesso` ENUM('', 'M', 'F') NOT NULL AFTER `luogo_nascita`;

-- Aggiunta tipo anagrafica
ALTER TABLE `an_anagrafiche` ADD `tipo` ENUM('', 'Azienda', 'Privato', 'Ente pubblico') NOT NULL AFTER `ragione_sociale`;

-- Aggiunta scelta impostazioni sicurezza SMTP
INSERT INTO `zz_impostazioni` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Sicurezza SMTP', 'Nessuna', 'list[Nessuna,TLS,SSL]', '1', 'Email');

-- nascondo opzione con indirizzo email destinatario del modulo bug
UPDATE `zz_impostazioni` SET `editable` = '0' WHERE `nome` = 'Destinatario';
UPDATE `zz_impostazioni` SET `valore` = '100' WHERE `nome` = 'Righe per pagina';

--
-- Modifica menu fatture, ordini, ecc in Vendita, Acquisti, Contabilità
--
-- Aggiunta VENDITE
INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES (NULL, 'Vendite', '', '', '', '', 'fa fa-line-chart', '2.1', '2.*', '3', '0', '0', '1', '1', 'menu', '0');

-- Aggiunta ACQUISTI
INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES (NULL, 'Acquisti', '', '', '', '', 'fa fa-shopping-cart', '2.1', '2.*', '4', '0', '0', '1', '1', 'menu', '0');

-- Spostamento in giù dei moduli successivi
UPDATE `zz_modules` SET `order`=5 WHERE `name`='Contabilit&agrave;';
UPDATE `zz_modules` SET `order`=6 WHERE `name`='Magazzino';
UPDATE `zz_modules` SET `order`=7 WHERE `name`='MyImpianti';
UPDATE `zz_modules` SET `order`=8 WHERE `name`='Backup';
UPDATE `zz_modules` SET `order`=9 WHERE `name`='Aggiornamenti';

-- Collegamento sottomenu di Contabilità al giusto "contenitore" (vendite o acquisti)
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` IN('Preventivi', 'Contratti', 'Fatture di vendita', 'Ordini cliente') AND `t2`.`name` = 'Vendite') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` IN('Fatture di acquisto', 'Ordini fornitore') AND `t2`.`name` = 'Acquisti') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunta nuovi campi nelle righe preventivi
ALTER TABLE `co_righe_preventivi` ADD `sconto` DECIMAL(12, 4) NOT NULL AFTER `subtotale`;
ALTER TABLE `co_preventivi` ADD `idiva` INT(11) NOT NULL AFTER `idtipointervento`;

-- Creazione collegamento multiplo fra clienti e agenti
CREATE TABLE IF NOT EXISTS `an_anagrafiche_agenti` (
  `idanagrafica` int(11) NOT NULL,
  `idagente` int(11) NOT NULL,
  PRIMARY KEY(`idanagrafica`, `idagente`)
) ENGINE=InnoDB;

-- Aggiunta filtro su Prima nota per mostrare solo quelle dell'agente loggato
INSERT INTO `zz_gruppi_modules` (`idgruppo`, `idmodule`, `clause`) VALUES ((SELECT `id` FROM `zz_gruppi` WHERE `nome`='Agenti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Prima nota'), 'AND idagente=|idanagrafica|');

ALTER TABLE `co_documenti` ADD `idagente` INT(11) NOT NULL AFTER `idanagrafica`;

UPDATE `zz_widget_modules` SET `more_link` = 'if(confirm(''Stampare il riepilogo?'')){ window.open(''templates/pdfgen.php?ptype=riepilogo_interventi&id_module=$id_module$''); }' WHERE `zz_widget_modules`.`name` = 'Stampa riepilogo';

-- Aggiungo tabella log
CREATE TABLE IF NOT EXISTS `zz_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idutente` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `stato` varchar(50) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `timestamp` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Aggiunta tipologia di scadenza
ALTER TABLE `co_scadenziario` ADD `tipo` VARCHAR(50) NOT NULL AFTER `iddocumento`;
UPDATE `co_scadenziario` SET `tipo` = 'fattura';

-- Aggiunto campo bic per l'anagrafica
ALTER TABLE `an_anagrafiche` ADD `bic` VARCHAR(25) NOT NULL AFTER `codiceiban`;

-- Uniformo lunghezza varchar per idintervento in my_impianto_componenti - prima era varchar (20)
ALTER TABLE `my_impianto_componenti` CHANGE `idintervento` `idintervento` VARCHAR(25) NOT NULL;

-- Aggiunto campo ordine per poter ordinare le righe in fattura
ALTER TABLE `co_righe_documenti` ADD `ordine` INT(11) NOT NULL AFTER `altro`;

-- Aggiunto widget per vedere il valore del magazzino + il totale degli articoli disponibili
INSERT INTO `zz_widget_modules` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL, 'Valore magazzino', 'stats', '21', 'controller_right', 'col-md-12', 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT (SUM(prezzo_acquisto*qta),2), ",", "#"), ".", ","), "#", "."), "&euro;") AS dato FROM mg_articoli WHERE qta>0', '#A15D2D', 'fa fa-money', '', '', '', '', 'Valore magazzino', '1', '1');
INSERT INTO `zz_widget_modules` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL, 'Articoli in magazzino', 'stats', '21', 'controller_right', 'col-md-12', 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT (SUM(qta),2), ",", "#"), ".", ","), "#", "."), "unit&agrave;") AS dato FROM mg_articoli WHERE qta>0', '#45A9F1', 'fa fa-check-square-o', '', '', '', '', 'Articoli in magazzino', '1', '1');

-- Aumento dimensione campo descrizione su co_pagamenti
ALTER TABLE `co_pagamenti` CHANGE `descrizione` `descrizione` VARCHAR(255) NOT NULL;

-- Aggiunta filtro su MyImpianti per mostrare solo quelli del cliente loggato
INSERT INTO `zz_gruppi_modules` (`idgruppo`, `idmodule`, `clause`) VALUES ((SELECT `id` FROM `zz_gruppi` WHERE `nome`='Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name`='MyImpianti'), 'AND my_impianti.idanagrafica=|idanagrafica|');

-- Aggiunto plugin che mostra elenco ddt di vendita per l'anagrafica
INSERT INTO `zz_modules_plugins` (`id`, `name`, `idmodule_from`, `idmodule_to`, `position`, `script`) VALUES (NULL, 'Ddt del cliente', (SELECT `id` FROM `zz_modules` WHERE `name`='Ddt di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Anagrafiche'), 'tab', 'ddt.anagrafiche.php');

-- Aggiunta nuovi campi nelle righe preventivi
ALTER TABLE `co_righe2_contratti` ADD `sconto` DECIMAL(12, 4) NOT NULL AFTER `subtotale`;
ALTER TABLE `co_righe2_contratti` ADD `idiva` INT(11) NOT NULL AFTER `sconto`;
ALTER TABLE `co_righe2_contratti` ADD `iva` DECIMAL(12, 4) NOT NULL AFTER `idiva`;
ALTER TABLE `co_righe2_contratti` ADD `iva_indetraibile` DECIMAL(12, 4) NOT NULL AFTER `iva`;

-- Aggiunto stato concluso anche ai contratti
INSERT INTO `co_staticontratti` (`id`, `descrizione`, `icona`, `completato`, `annullato`) VALUES (NULL, 'Concluso', 'fa fa-2x fa-check text-success', '0', '0');

-- Aggiunto modulo per gestire componenti
-- (SELECT `id` FROM `zz_modules` WHERE `name`='MyImpianti')
INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES (NULL, 'Gestione componenti', '', 'gestione_componenti', '{ "main_query": [ { "type": "custom" } ]}', '', 'fa fa-external-link', '2.2', '2.2', '0', '1', '30', '1', '1', 'menu', '0');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Gestione componenti' AND `t2`.`name` = 'MyImpianti') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunti campi per gestire firma rapportini
ALTER TABLE `in_interventi` ADD  `firma_file` varchar(255) NOT NULL AFTER `ora_sla`;
ALTER TABLE `in_interventi` ADD `firma_data` DATETIME NOT NULL AFTER `firma_file`;
ALTER TABLE `in_interventi` ADD `firma_nome` VARCHAR(255) NOT NULL AFTER `firma_data`;

-- Aggiunto campo data_invio per salvare data e ora invio email dei rapportini
ALTER TABLE `in_interventi` ADD `data_invio` DATETIME NULL AFTER `firma_nome`;

-- Aggiunta impostazione destinatario fisso in copia
INSERT INTO `zz_impostazioni` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Destinatario fisso in copia (campo CC)', '', 'string', 1, 'Email');

-- Aggiunta legame tra interventi e componenti
CREATE TABLE IF NOT EXISTS `my_componenti_interventi` (
  `id_intervento` varchar(25) NOT NULL,
  `id_componente` varchar(25) NOT NULL
) ENGINE=InnoDB;

-- Aggiunto campo prc_guadagno in co_righe_preventivi
ALTER TABLE `co_righe_preventivi` ADD `prc_guadagno` DECIMAL(5,2) NOT NULL AFTER `sconto`;

-- 2016-11-09 (r1509)
CREATE TABLE IF NOT EXISTS `co_contratti_tipiintervento` (
  `idcontratto` int(11) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `costo_ore` decimal(12,4) NOT NULL,
  `costo_km` decimal(12,4) NOT NULL,
  `costo_dirittochiamata` decimal(12,4) NOT NULL,
  `costo_ore_tecnico` decimal(12,4) NOT NULL,
  `costo_km_tecnico` decimal(12,4) NOT NULL,
  `costo_dirittochiamata_tecnico` decimal(12,4) NOT NULL,
  PRIMARY KEY (`idcontratto`,`idtipointervento`)
) ENGINE=InnoDB;
