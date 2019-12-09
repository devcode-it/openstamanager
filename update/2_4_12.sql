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
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES (NULL, 'Permetti inserimento sessioni degli altri tecnici', '0', 'boolean', '1', 'Interventi', NULL, NULL, NULL, 'Permette al tecnico l\'inserimento delle sessioni di lavoro anche per gli altri tecnici.');