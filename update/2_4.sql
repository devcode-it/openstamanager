--
-- Struttura della tabella `zz_prints`
--

CREATE TABLE IF NOT EXISTS `zz_prints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11) NOT NULL,
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
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE
);

-- Inserimento delle stampe di base
INSERT INTO `zz_prints` (`id_module`, `name`, `directory`, `options`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Fattura di vendita', 'fatture', '', 'iddocumento', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Riepilogo intervento', 'riepilogo_interventi', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Inventario magazzino', 'magazzino_inventario', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Piano dei conti'), 'Mastrino', 'partitario_mastrino', '', 'idconto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenzario', 'scadenzario', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe contabili'), 'Registro IVA', 'registro_iva', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe contabili'), 'Fatturato', 'fatturato', '', '', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe contabili'), 'Spesometro', 'spesometro', '', '', 1, 1);

-- Inserimento delle stampe con prezzo disabilitato
INSERT INTO `zz_prints` (`id_module`, `name`, `directory`, `options`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Contratto (senza costi)', 'contratti', '{"pricing":false}', 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Intervento (senza costi)', 'interventi', '{"pricing":false}', 'idintervento', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Ordine cliente (senza costi)', 'ordini', '{"pricing":false}', 'idordine', 1, 1),
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
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Consuntivo contratto', 'contratti_cons', '{"pricing":true}', 1, 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Consuntivo preventivo', 'preventivi_cons', '{"pricing":true}', 1, 'idpreventivo', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Ordine di servizio', 'interventi_ordiniservizio', '{"pricing":true}', 1, 'idintervento', 1, 1);

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
);

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
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_smtp`) REFERENCES `zz_smtp`(`id`) ON DELETE CASCADE
);

--
-- Struttura della tabella `zz_emails`
--

CREATE TABLE IF NOT EXISTS `zz_email_print` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_email` int(11) NOT NULL,
  `id_print` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_email`) REFERENCES `zz_emails`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_print`) REFERENCES `zz_prints`(`id`) ON DELETE CASCADE
);

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

-- SEZIONALI
-- Modifico co_documenti per aggiungere riferimento al sezionale
ALTER TABLE `co_documenti` ADD `id_sezionale` int(11) NOT NULL ;

-- Creo tabella sezionali
CREATE TABLE IF NOT EXISTS `co_sezionali` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `maschera` varchar(255) NOT NULL,
  `dir` varchar(50) NOT NULL,
  `idautomezzo` int(11) NULL ,
  `note` text NOT NULL,
  PRIMARY KEY (`id`)
);

-- Popolo con i sezionali di default
INSERT INTO `co_sezionali` (`id`, `nome`, `maschera`, `dir`, `idautomezzo`, `note`) VALUES
(1, 'Standard vendite', '###YY', 'entrata', NULL, ''),
(2, 'Standard acquisti', '####', 'uscita', NULL,'');

-- Collego le fatture esistenti al sezionale di default
UPDATE `co_documenti` SET `id_sezionale`='1' WHERE `idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir`='entrata');
UPDATE `co_documenti` SET `id_sezionale`='2' WHERE `idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir`='uscita');

-- Innesto modulo sezionali sotto "Contabilità"
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
(NULL, 'Sezionali', 'Sezionali', 'sezionali', '{	"main_query": [	{	"type": "table", "fields": "Tipo documenti, Nome,  Maschera, Magazzino, Note", "query": "SELECT `id`, `nome` AS `Nome`, `maschera` AS `Maschera`, (SELECT nome FROM dt_automezzi WHERE dt_automezzi.id = idautomezzo) AS Magazzino, IF(`dir`=''entrata'', ''Documenti di vendita'', ''Documenti di acquisto'') AS `Tipo documenti`, `note` AS `Note`  FROM `co_sezionali` HAVING 2=2 ORDER BY `Tipo documenti`, `Nome`"}	]}', '', 'fa fa-database', '2.4', '2.4', 1, 12, 1, 1);

-- Aggiungo impostazione predefinita
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
(NULL, 'Sezionale predefinito fatture di vendita', '1', 'query=SELECT id, CONCAT(nome, '': '', maschera) AS descrizione FROM co_sezionali WHERE dir=''entrata'' ORDER BY nome', 1, 'Fatturazione'),
(NULL, 'Sezionale predefinito fatture di acquisto', '2', 'query=SELECT id, CONCAT(nome, '': '', maschera) AS descrizione FROM co_sezionali WHERE dir=''uscita'' ORDER BY nome', 1, 'Fatturazione');

-- Aggiorno widget Fatturato con i sezionali
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT(SUM((SELECT SUM(subtotale+iva-sconto) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), ",", "#"), ".", ","), "#", "."), "&euro;") AS dato FROM co_documenti WHERE idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir="entrata") |sezionale_entrata| AND data >= "|period_start|" AND data <= "|period_end|" AND 1=1' WHERE `zz_widgets`.`name` = 'Fatturato';

-- Aggiorno widget Acquisti con i sezionali
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT((SELECT ABS(SUM(da_pagare))), 2), ",", "#"), ".", ","), "#", "."), "&euro;") AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE dir=''uscita'' |sezionale_uscita| AND data_emissione >= "|period_start|" AND data_emissione <= "|period_end|"' WHERE `zz_widgets`.`name` = 'Acquisti';

-- Aggiorno widget Crediti da clienti con i sezionali
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT(SUM((SELECT SUM(subtotale+iva-sconto) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), ",", "#"), ".", ","), "#", "."), "€") AS dato FROM co_documenti WHERE idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir="entrata") AND idstatodocumento = (SELECT id FROM co_statidocumento WHERE descrizione="Emessa") |sezionale_entrata| AND data >= "|period_start|" AND data <= "|period_end|" AND 1=1' WHERE `zz_widgets`.`name` = 'Crediti da clienti' ;

-- Aggiorno i moduli Fattura con i sezionali
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE 1=1 AND `dir` = ''uscita'' |sezionale_uscita| AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 2=2 ORDER BY `data` DESC, CAST(IF(numero_esterno='''', numero, numero_esterno) AS UNSIGNED) DESC' WHERE `name` = 'Fatture di acquisto';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE 1=1 AND `dir` = ''entrata'' |sezionale_entrata| AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 2=2 ORDER BY `data` DESC, CAST(numero_esterno AS UNSIGNED) DESC' WHERE `name` = 'Fatture di vendita';

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
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_plugin`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE
);

--
-- Struttura della tabella `zz_fields`
--

CREATE TABLE IF NOT EXISTS `zz_field_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_field` int(11) NOT NULL,
  `id_record` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_field`) REFERENCES `zz_fields`(`id`) ON DELETE CASCADE
);

-- Aggiunta modulo di importazione
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
(NULL, 'Import', 'Import', 'import', 'custom', '', 'fa fa-file-o', '2.4', '2.4', 1, NULL, 1, 1);
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Import' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunta delle email per i rapportini
INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `main`, `deleted`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 1, 'Rapportino', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 1, 'Rapportino', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 1, 'Rapportino', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 1, 'Rapportino', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 1, 'Rapportino', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 1, 'Rapportino', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0');

INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Intervento (senza costi)')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Preventivo')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Contratto')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Ordine cliente')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Fattura di vendita')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Rapportino' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Ddt di vendita'));

-- Aggiunta delle email per i consuntivi rapportini
INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `main`, `deleted`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 1, 'Consuntivo', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 1, 'Consuntivo', '', 'Invio rapportino numero {numero} del {data}', '', '', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato il rapportino numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0', '0');

INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Consuntivo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Consuntivo contratto')),
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Consuntivo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Consuntivo preventivo'));

-- apilayer API key (per validazione email)
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'apilayer API key for Email', '', 'string', '1', 'Generali');

-- apilayer API key (per validazione piva)
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'apilayer API key for VAT number', '', 'string', '1', 'Generali');