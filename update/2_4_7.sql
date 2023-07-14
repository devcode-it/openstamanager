UPDATE `in_interventi` SET `id_contratto` = (SELECT `idcontratto` FROM `co_promemoria` WHERE `idintervento` = `in_interventi`.`id` LIMIT 1);

ALTER TABLE `co_righe_contratti` ADD `qta_evasa` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_righe_preventivi` ADD `qta_evasa` DECIMAL(12, 4) NOT NULL;

-- Inserisco due nuovi stati preventivi
UPDATE `co_statipreventivi` SET `descrizione` = 'Fatturato', `completato` = 1, `annullato` = 0, `icona` = 'fa fa-file-text-o text-success' WHERE `descrizione` = 'In attesa di pagamento';
INSERT INTO `co_statipreventivi` (`id`, `descrizione`, `completato`, `annullato`, `icona`) VALUES
(NULL, 'Parzialmente fatturato', 0, 0, 'fa fa-file-text-o text-warning');

ALTER TABLE `co_statipreventivi` ADD `fatturabile` BOOLEAN NOT NULL DEFAULT FALSE;
UPDATE `co_statipreventivi` SET `fatturabile` = 1 WHERE `descrizione` IN('Parzialmente fatturato', 'Concluso', 'Pagato', 'In lavorazione', 'Accettato', 'In attesa di conferma');

-- Inserisco due nuovi stati contratti
UPDATE `co_staticontratti` SET `descrizione` = 'Fatturato', `pianificabile` = 0, `fatturabile` = 0, `icona` = 'fa fa-file-text-o text-success' WHERE `descrizione` = 'In attesa di pagamento';
INSERT INTO `co_staticontratti` (`id`, `descrizione`, `pianificabile`, `fatturabile`, `icona`) VALUES
(NULL, 'Parzialmente fatturato', 0, 1, 'fa fa-file-text-o text-warning');

-- Fix ritenuta contributi
ALTER TABLE `co_documenti` CHANGE `id_ritenuta_contributi` `id_ritenuta_contributi` INT(11);
UPDATE `co_documenti` SET `id_ritenuta_contributi` = NULL WHERE `id_ritenuta_contributi` = 0;
ALTER TABLE `co_documenti` ADD FOREIGN KEY (`id_ritenuta_contributi`) REFERENCES `co_ritenuta_contributi`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_documenti` ADD `ritenuta_contributi` BOOLEAN NOT NULL DEFAULT FALSE;

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Ritenuta contributi', '', 'query=SELECT * FROM co_ritenuta_contributi', 1, 'Fatturazione', 12);

-- Sollecito di pagamento
INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `predefined`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 1, 'Sollecito di pagamento', 'fa fa-envelope', 'Sollecito di pagamento fattura {numero}', '', '', '', '<p>Spett.le {ragione_sociale},</p>\r\n\r\n<p>da un riscontro contabile, ci risulta che la fattura numero {numero} a Voi intestata, equivalente ad un ammontare di Euro {totale}, è scaduta in data {data_scadenza}.</p>\r\n\r\n<p>La sollecitiamo pertanto di provvedere quanto prima a regolarizzare la sua situazione contabile. A tal proposito, il pagamento potrà essere effettuato tramite {pagamento}.</p>\r\n\r\n<p>Se ha già provveduto al pagamento, ritenga nulla la presente.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>La ringraziamo e le porgiamo i nostri saluti.</p>\r\n', '0', '1');
