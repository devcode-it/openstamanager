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
  `icon` varchar(255) NOT NULL,
  `version` varchar(15) NOT NULL,
  `compatibility` varchar(1000) NOT NULL,
  `order` int(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `default` tinyint(1) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

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
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Ordine clienti (senza costi)', 'ordini', '{"pricing":false}', 'idordine', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Ddt di vendita (senza costi)', 'ddt', '{"pricing":false}', 'idddt', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Preventivo (senza costi)', 'preventivi', '{"pricing":false}', 'idpreventivo', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Consuntivo contratto (senza costi)', 'contratti_cons', '{"pricing":false}', 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Consuntivo preventivo (senza costi)', 'preventivi_cons', '{"pricing":false}', 'idpreventivo', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Ordine di servizio (senza costi)', 'interventi_ordiniservizio', '{"pricing":false}', 'idintervento', 1, 1);

-- Inserimento delle stampe con prezzo abilitate
INSERT INTO `zz_prints` (`id_module`, `name`, `directory`, `options`, `main`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Contratto', 'contratti', '{"pricing":true}', 1, 'idcontratto', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Intervento', 'interventi', '{"pricing":true}', 1, 'idintervento', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Ordine clienti', 'ordini', '{"pricing":true}', 1, 'idordine', 1, 1),
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
