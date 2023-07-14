-- Stati di invio del documento
CREATE TABLE IF NOT EXISTS `fe_stati_documento` (
  `codice` varchar(5) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES
('GEN', 'Generata', 'fa fa-file-code-o text-success'),
('WAIT', 'In attesa', 'fa fa-clock-o text-warning'),
('SENT', 'Inviata', 'fa fa-paper-plane-o text-info'),
('ACK', 'Accettata', 'fa fa-paper-check text-success'),
('REF', 'Rifiuta', 'fa fa-times text-error');

ALTER TABLE `co_documenti` ADD `codice_stato_fe` varchar(5), ADD FOREIGN KEY (`codice_stato_fe`) REFERENCES `fe_stati_documento`(`codice`) ON DELETE SET NULL;
UPDATE `co_documenti` SET `codice_stato_fe` = 'GEN' WHERE `xml_generated_at` IS NOT NULL;
ALTER TABLE `co_documenti` DROP `xml_generated_at`;

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'OSMCloud Services API Token', '', 'string', 1, 'Fatturazione Elettronica', 11);

-- Allineo valore Iva predefinita secondo nuovi codici tabella co_iva
UPDATE `zz_settings` SET `valore` = (SELECT id FROM `co_iva` WHERE `codice` = 22 LIMIT 0,1) WHERE `nome` = 'Iva predefinita' AND `valore` = 91;

UPDATE `zz_modules` SET `directory` = 'backups' WHERE `name` = 'Backup';
