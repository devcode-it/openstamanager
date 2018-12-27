INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Ricevute FE', 'Ricevute FE', (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), 'tab_main', 'receiptFE', 'custom');

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-check text-success' WHERE `codice` = 'ACK';

-- Introduzione del flag split payment
ALTER TABLE `an_anagrafiche` ADD `split_payment` BOOLEAN NOT NULL DEFAULT FALSE AFTER `codice_destinatario`;

-- Prezzo di acquisto in Fatture di vendita e Preventivi

ALTER TABLE `co_righe_documenti` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;
ALTER TABLE `co_righe_preventivi` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;

UPDATE `fe_stati_documento` SET `descrizione`='Rifiutata' WHERE `codice`='REF';
