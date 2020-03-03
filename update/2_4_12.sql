UPDATE `co_documenti` SET `data_competenza` = `data` WHERE `data_competenza` = '0000-00-00' OR `data_competenza` IS NULL;

-- In caso di pagamenti anticipati dei clienti. Queste fatture non sono differenziate dalle altre, se non per la descrizione nel corpo fattura, che fa, appunto, riferimento all'anticipo ricevuto.
-- Nelle specifiche della fattura elettronica si parla di un tipo documento TD02 Acconto/anticipo su fattura.
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Acconto/anticipo su fattura', 'entrata', '0', 'TD02');

INSERT INTO `dt_causalet` (`id`, `descrizione`, `predefined`) VALUES (NULL, 'Conto lavorazione', '0'), (NULL, 'Conto visione','0') , (NULL, 'Omaggio','0');

UPDATE `zz_widgets` SET `name` = 'Attività nello stato da programmare', `text` = 'Attività nello stato da programmare'  WHERE `zz_widgets`.`name` = 'Attività in programmazione';

UPDATE `in_statiintervento` SET `descrizione` = 'Programmato' WHERE `in_statiintervento`.`descrizione` = 'In Programmazione' AND `in_statiintervento`.`codice` = 'WIP';

-- Uniformo le date scadenza non settate correttamente
UPDATE `in_interventi` SET `data_scadenza` = NULL WHERE `data_scadenza` = '0000-00-00 00:00:00';

-- Permetti inserimento sessioni anche per altri tecnici
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Permetti inserimento sessioni degli altri tecnici', '0', 'boolean', '1', 'Interventi', NULL, "Permette al tecnico l\'inserimento delle sessioni di lavoro anche per gli altri tecnici.");

-- Aggiunta cartella per il modulo "Movimenti"
UPDATE `zz_modules` SET `directory` = 'movimenti' WHERE `name` = 'Movimenti';

-- Fix nomenclatura stampe (da "senza costi" a "senza prezzi")
UPDATE `zz_prints` SET `title` = 'Intervento (senza prezzi)' WHERE `name` = 'Intervento (senza costi)';
UPDATE `zz_prints` SET `title` = 'Preventivo (senza prezzi)' WHERE `name` = 'Preventivo (senza costi)';
UPDATE `zz_prints` SET `title` = 'Consuntivo preventivo (senza prezzi)' WHERE `name` = 'Consuntivo preventivo (senza costi)';
UPDATE `zz_prints` SET `title` = 'Contratto (senza prezzi)' WHERE `name` = 'Contratto (senza costi)';
UPDATE `zz_prints` SET `title` = 'Consuntivo contratto (senza prezzi)' WHERE `name` = 'Consuntivo contratto (senza costi)';
UPDATE `zz_prints` SET `title` = 'Ordine cliente (senza prezzi)' WHERE `name` = 'Ordine cliente (senza costi)';
UPDATE `zz_prints` SET `title` = 'Ordine fornitore (senza prezzi)' WHERE `name` = 'Ordine fornitore (senza costi)';
UPDATE `zz_prints` SET `title` = 'Ddt di vendita (senza prezzi)' WHERE `name` = 'Ddt di vendita (senza costi)';

-- Aggiunta campo "Ubicazione" per gli articoli
ALTER TABLE `mg_articoli` ADD `ubicazione` VARCHAR(255) NOT NULL AFTER `threshold_qta`;

-- Aggiunta flag per apertura e chiusura bilancio automatici
ALTER TABLE `co_movimenti` ADD `is_apertura` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_insoluto`, ADD `is_chiusura` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_apertura`;

ALTER TABLE `co_pianodeiconti3` DROP `can_delete`,  DROP `can_edit`;

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Conto per Riepilogativo fornitori', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Riepilogativo fornitori'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Riepilogativo clienti', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Riepilogativo clienti'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Iva indetraibile', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Iva indetraibile'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Iva su vendite', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Iva su vendite'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Iva su acquisti', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Iva su acquisti'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Erario c/ritenute d''acconto', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Erario c/ritenute d''acconto'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Erario c/INPS', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Erario c/INPS'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Erario c/enasarco', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Erario c/enasarco'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Apertura conti patrimoniali', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Apertura conti patrimoniali'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1),
(NULL, 'Conto per Chiusura conti patrimoniali', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Chiusura conti patrimoniali'), 'query=SELECT `id`, CONCAT_WS('' - '', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei Conti', 1);
