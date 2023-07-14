-- Lo stato 'FAT' è da considerarsi completato
UPDATE `in_statiintervento` SET `completato` = '1' WHERE `in_statiintervento`.`idstatointervento` = 'FAT';

-- Nuovi campi per iva su righe 'Materiale utilizzato' in interventi
ALTER TABLE `mg_articoli_interventi` CHANGE `idiva_vendita` `idiva` INT(11) NOT NULL;
ALTER TABLE `mg_articoli_interventi` ADD `desc_iva` VARCHAR(255) NOT NULL AFTER `idiva`, ADD `iva` DECIMAL(12,4) NOT NULL AFTER `desc_iva`;

-- Nuovi campi per iva su righe 'Altre spese' in interventi
ALTER TABLE `in_righe_interventi` ADD `idiva` INT(11) NOT NULL AFTER `prezzo_acquisto`, ADD `desc_iva` VARCHAR(255) NOT NULL AFTER `idiva`, ADD `iva` DECIMAL(12,4) NOT NULL AFTER `desc_iva`;

--
-- Struttura della tabella `zz_prints`
--

CREATE TABLE IF NOT EXISTS `zz_prints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11) NOT NULL,
  `is_record` BOOLEAN NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `directory` varchar(50) NOT NULL,
  `previous` varchar(50) NOT NULL,
  `options` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `version` varchar(15) NOT NULL,
  `compatibility` varchar(1000) NOT NULL,
  `order` int(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `default` tinyint(1) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Inserimento delle stampe di base
INSERT INTO `zz_prints` (`id_module`, `name`, `directory`, `options`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Fattura di vendita', 'fatture', '', 'iddocumento', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Riepilogo interventi', 'riepilogo_interventi', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Inventario magazzino', 'magazzino_inventario', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Piano dei conti'), 'Mastrino', 'partitario_mastrino', '', 'idconto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenzario', 'scadenzario', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe contabili'), 'Registro IVA', 'registro_iva', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe contabili'), 'Fatturato', 'fatturato', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe contabili'), 'Spesometro', 'spesometro', '', '', 1, 1);

UPDATE `zz_prints` SET `is_record` = 0 WHERE `name` = 'Riepilogo interventi';

-- Inserimento delle stampe con prezzo disabilitato
INSERT INTO `zz_prints` (`id_module`, `name`, `directory`, `options`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Contratto (senza costi)', 'contratti', '{"pricing":false}', 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Intervento (senza costi)', 'interventi', '{"pricing":false}', 'idintervento', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Ordine cliente (senza costi)', 'ordini', '{"pricing":false}', 'idordine', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Ordine fornitore', 'ordini', '{"pricing":false}', 'idordine', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Ddt di vendita (senza costi)', 'ddt', '{"pricing":false}', 'idddt', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Preventivo (senza costi)', 'preventivi', '{"pricing":false}', 'idpreventivo', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Consuntivo contratto (senza costi)', 'contratti_cons', '{"pricing":false}', 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Consuntivo preventivo (senza costi)', 'preventivi_cons', '{"pricing":false}', 'idpreventivo', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Ordine di servizio (senza costi)', 'interventi_ordiniservizio', '{"pricing":false}', 'idintervento', 1, 1);

-- Inserimento delle stampe con prezzo abilitate
INSERT INTO `zz_prints` (`id_module`, `name`, `directory`, `options`, `main`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Contratto', 'contratti', '{"pricing":true}', 1, 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Intervento', 'interventi', '{"pricing":true}', 1, 'idintervento', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Ordine cliente', 'ordini', '{"pricing":true}', 1, 'idordine', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Ddt di vendita', 'ddt', '{"pricing":true}', 1, 'idddt', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Preventivo', 'preventivi', '{"pricing":true}', 1, 'idpreventivo', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Consuntivo contratto', 'contratti_cons', '{"pricing":true}', 1, 'idcontratto', 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Consuntivo preventivo', 'preventivi_cons', '{"pricing":true}', 1, 'idpreventivo', 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Ordine di servizio', 'interventi_ordiniservizio', '{"pricing":true}', 1, 'idintervento', 1, 0);

-- Impostazione dei titoli di default
UPDATE `zz_prints` SET `title` = `name` WHERE `title` = '';

-- Impostazione delle icone di default
UPDATE `zz_prints` SET `icon` = 'fa fa-print' WHERE `icon` = '';

-- DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sui contratti';
-- DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sui ddt';
-- DELETE FROM `zz_settings` WHERE `nome` = 'Visualizza i costi sulle stampe degli interventi';
-- DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sugli ordini';
-- DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sui preventivi';

--
-- Struttura della tabella `zz_smtp`
--

CREATE TABLE IF NOT EXISTS `zz_smtp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  `server` varchar(255) NOT NULL,
  `port` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL,
  `from_address` varchar(255) NOT NULL,
  `encryption` enum('','tls','ssl') NOT NULL,
  `pec` tinyint(1) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Struttura della tabella `zz_emails`
--

CREATE TABLE IF NOT EXISTS `zz_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11) NOT NULL,
  `id_smtp` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `reply_to` varchar(255) NOT NULL,
  `cc` varchar(255) NOT NULL,
  `bcc` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `read_notify` tinyint(1) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Struttura della tabella `zz_emails`
--

CREATE TABLE IF NOT EXISTS `zz_email_print` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_email` int(11) NOT NULL,
  `id_print` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Aggiunta dei moduli dedicati alla gestione delle email
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Account email', 'Account email', 'smtp', 'SELECT |select| FROM zz_smtp WHERE 1=1 AND deleted = 0 HAVING 2=2 ORDER BY `name`', 'fa fa-user-o', '2.3', '2.3', '10', NULL, 1, 1);
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Template email', 'Template email', 'emails', 'SELECT |select| FROM zz_emails WHERE 1=1 AND deleted = 0 HAVING 2=2 ORDER BY `name`', 'fa fa-pencil-square-o', '2.3', '2.3', '10', NULL, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Account email'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Account email'), '#', 'id', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Account email'), 'Nome account', 'name', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Account email'), 'Nome visualizzato', 'from_name', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Account email'), 'Email mittente', 'from_address', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Template email'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Template email'), '#', 'id', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Template email'), 'Nome', 'name', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Template email'), 'Oggetto', 'subject', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Template email'), 'Modulo', '(SELECT name FROM zz_modules WHERE id=zz_emails.id_module)', 5, 1, 0, 1, 1);


-- Raggruppamento dei moduli per le email
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Gestione email', 'Gestione email', '', '', '', 'fa fa-envelope', '2.3', '2.3', '1', NULL, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Account email' AND `t2`.`name` = 'Gestione email') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Template email' AND `t2`.`name` = 'Gestione email') SET `t1`.`parent` = `t2`.`id`;

-- Importazione dell'account email di default
INSERT INTO `zz_smtp` (`id`, `name`, `server`, `port`, `username`, `password`, `encryption`, `main`) VALUES (NULL, 'Account email da Impostazioni', (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Server SMTP'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Porta SMTP'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Username SMTP'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Password SMTP'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Sicurezza SMTP'), 1);

DELETE FROM `zz_settings` WHERE
(`nome` = 'Server SMTP') OR
(`nome` = 'Porta SMTP') OR
(`nome` = 'Username SMTP') OR
(`nome` = 'Password SMTP') OR
(`nome` = 'Sicurezza SMTP');

-- SEGNENTI
-- Modifico co_documenti per aggiungere riferimento al segmento
ALTER TABLE `co_documenti` ADD `id_segment` int(11) NOT NULL ;

-- Creo tabella segmenti
CREATE TABLE IF NOT EXISTS `zz_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `clause` TEXT NOT NULL,
  `position` enum('WHR', 'HVN') NOT NULL DEFAULT 'WHR',
  `pattern` varchar(255) NOT NULL,
  `note` text NOT NULL,
  `predefined` BOOLEAN NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Popolo con i segmenti di default
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`,`note`, `predefined`) VALUES
(1, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Standard vendite', '1=1', 'WHR', IF((SELECT COUNT(id) FROM co_documenti) > 0, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Formato numero secondario fattura'), '####/YYYY'), '', 1),
(2, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Standard acquisti', '1=1', 'WHR', '#', '', 1);

-- Rimuovo impostazione per numero secondario fattura
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato numero secondario fattura';

-- Collego le fatture esistenti al segmento di default
UPDATE `co_documenti` SET `id_segment`='1' WHERE `idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir`='entrata');
UPDATE `co_documenti` SET `id_segment`='2' WHERE `idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir`='uscita');

-- Innesto modulo segmenti sotto "Strumenti"
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Segmenti', 'Segmenti', 'segmenti', '{	"main_query": [	{	"type": "table", "fields": "id, Nome, Modulo, Maschera, Note, Predefinito", "query": "SELECT `id`, (IF(predefined=1, ''Sì'', ''No'')) AS `Predefinito`, `name` AS `Nome`, (SELECT name FROM zz_modules WHERE id = zz_segments.id_module) AS Modulo, `pattern` AS `Maschera`, `note` AS `Note`  FROM `zz_segments` HAVING 2=2 ORDER BY name, id_module"}	]}', '', 'fa fa-database', '2.4', '2.4', 1, NULL, 1, 1);
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Segmenti' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;

-- Help text per widget Fatturato
UPDATE `zz_widgets` SET `help` = 'Fatturato IVA inclusa.' WHERE `zz_widgets`.`name` = 'Fatturato';

--
-- Struttura della tabella `zz_fields`
--

CREATE TABLE IF NOT EXISTS `zz_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11),
  `id_plugin` int(11),
  `name` varchar(255) NOT NULL,
  `html_name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `options` text,
  `order` int(11) NOT NULL,
  `on_add` boolean NOT NULL,
  `top` boolean NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Struttura della tabella `zz_fields`
--

CREATE TABLE IF NOT EXISTS `zz_field_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_field` int(11) NOT NULL,
  `id_record` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Aggiunta modulo di importazione
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
(NULL, 'Import', 'Import', 'import', 'custom', '', 'fa fa-file-o', '2.4', '2.4', 1, NULL, 1, 1);
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Import' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunta delle email per i rapportini
INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `main`, `deleted`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 1, 'Rapportino intervento', 'fa fa-envelope', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 1, 'Preventivo', 'fa fa-envelope', 'Invio preventivo numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il preventivo numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 1, 'Contratto', 'fa fa-envelope', 'Invio contratto numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il contratto numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 1, 'Ordine', 'fa fa-envelope', 'Invio ordine numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato l\'ordine numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 1, 'Ordine', 'fa fa-envelope', 'Invio ordine numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato l\'ordine numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 1, 'Fattura', 'fa fa-envelope', 'Invio fattura numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato la fattura numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 1, 'Ddt', 'fa fa-envelope', 'Invio ddt {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il ddt numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0');

INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino intervento' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Intervento (senza costi)')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Preventivo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Preventivo')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Contratto' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Contratto')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Ordine' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Ordine cliente')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Ordine' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Ordine fornitore')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Fattura di vendita')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Ddt' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Ddt di vendita'));

-- Aggiunta delle email per i consuntivi rapportini
INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `main`, `deleted`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 1, 'Consuntivo contratto', 'fa fa-envelope', 'Invio consuntivo contratto numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 1, 'Consuntivo preventivo', 'fa fa-envelope', 'Invio consuntivo del preventivo numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0');

INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Consuntivo contratto' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Consuntivo contratto')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Consuntivo preventivo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Consuntivo preventivo'));

-- apilayer API key (per validazione email)
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'apilayer API key for Email', '', 'string', '1', 'Generali');

-- apilayer API key (per validazione piva)
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'apilayer API key for VAT number', '', 'string', '1', 'Generali');

-- Rimozione impostazioni inutilizzate
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Indirizzo per le email in uscita';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Destinatario';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Destinatario fisso in copia (campo CC)';

-- Conversione numero co_documenti da int(11) a varchar(100)
ALTER TABLE `co_documenti` CHANGE `numero` `numero` VARCHAR(100) NOT NULL;

-- Disabilito di default filtro tecnici che altrimento vedono solo le anagrafiche per i quali hanno eseguito un intervento (issue #190)
UPDATE `zz_group_module` SET `enabled` = '0' WHERE `zz_group_module`.`id` = 2;

-- Disattivazione funzionalità in attesa di approfondire alcuni problemi di rallentamento e ottimizzazione performance
UPDATE `zz_settings` SET `valore` = '0' WHERE `nome` = 'Attiva notifica di presenza utenti sul record';

-- Tabella co_banche
CREATE TABLE IF NOT EXISTS `co_banche` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL,
  `iban` varchar(32) NOT NULL,
  `bic` varchar(11) NOT NULL,
  `id_pianodeiconti3` int(11) DEFAULT NULL,
  `note` text NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Innesto modulo per gestione banche
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Banche', 'Banche', 'banche', 'SELECT |select| FROM `co_banche` WHERE 1=1 AND deleted = 0 GROUP BY `nome` HAVING 2=2', '', 'fa fa-university', '2.4', '2.4', '1', NULL, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Banche' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `enabled`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Banche'), 'id', 'co_banche.id', 0, 0, 0, 0, 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Banche'), 'Nome', 'co_banche.nome', 0, 0, 0, 0, 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Banche'), 'Filiale', 'co_banche.filiale', 0, 0, 0, 0, 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Banche'), 'IBAN', 'co_banche.iban', 0, 0, 0, 0, 1, 0, 0);

-- Aggiungo campi in an_anagrafiche con riferimento banche
ALTER TABLE `an_anagrafiche` ADD `idbanca_vendite` INT(11) NOT NULL AFTER `idconto_cliente`, ADD `idbanca_acquisti` INT(11) NOT NULL AFTER `idbanca_vendite`;

-- Campo idbanca per fatture
ALTER TABLE `co_documenti` ADD `idbanca` INT(11) NOT NULL AFTER `idpagamento`;

-- Campo calcolo_ritenutaacconto per selezione tipo di calcolo ritenuta acconto
ALTER TABLE `co_righe_documenti` ADD `calcolo_ritenutaacconto` VARCHAR(255) NOT NULL AFTER `rivalsainps`;
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Metodologia calcolo ritenuta d''acconto predefinito', 'Imponibile', 'list[Imponibile,Imponibile + rivalsa inps]', 1, 'Fatturazione');

-- Fix doppio conto iva 'Esente art. 10' & 'Esente art.10'
DELETE FROM `co_iva` WHERE `co_iva`.`descrizione` = 'Esente art. 10' AND `co_iva`.`id` NOT IN (SELECT `idiva` FROM co_righe_documenti UNION SELECT `idiva` FROM co_righe_preventivi UNION SELECT `idiva` FROM dt_righe_ddt UNION SELECT `idiva` FROM or_righe_ordini );
