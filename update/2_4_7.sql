UPDATE `in_interventi` SET `id_contratto` = (SELECT `idcontratto` FROM `co_promemoria` WHERE `idintervento` = `in_interventi`.`id`);

ALTER TABLE `co_righe_contratti` ADD `qta_evasa` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_righe_preventivi` ADD `qta_evasa` DECIMAL(12, 4) NOT NULL;

-- Inserisco due nuovi stati preventivi
UPDATE `co_statipreventivi` SET `descrizione` = 'Fatturato', `completato` = 1, `annullato` = 0, `icona` = 'fa fa-file-text-o text-success' WHERE `descrizione` = 'In attesa di pagamento';
INSERT INTO `co_statipreventivi` (`id`, `descrizione`, `completato`, `annullato`, `icona`) VALUES
(NULL, 'Parzialmente fatturato', 0, 0, 'fa fa-file-text-o text-warning');

ALTER TABLE `co_statipreventivi` ADD `fatturabile` BOOLEAN NOT NULL DEFAULT FALSE;
UPDATE `co_statipreventivi` SET `fatturabile` = 1 WHERE `descrizione` IN('Parzialmente fatturato', 'Concluso', 'Pagato', 'In lavorazione', 'Accettato', 'In attesa di conferma');

-- Inserisco due nuovi stati contratti
UPDATE `co_staticontratti` SET `descrizione` = 'Fatturato', `pianificabile` = 0, `annullato` = 0, `icona` = 'fa fa-file-text-o text-success' WHERE `descrizione` = 'In attesa di pagamento';
INSERT INTO `co_staticontratti` (`id`, `descrizione`, `pianificabile`, `fatturabile`, `icona`) VALUES
(NULL, 'Parzialmente fatturato', 0, 1, 'fa fa-file-text-o text-warning');

UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'In attesa di pagamento', 'Fatturato');

-- Rimozione id_ritenuta_acconto_vendite non supportata
ALTER TABLE `an_anagrafiche` DROP `id_ritenuta_acconto_vendite`;
