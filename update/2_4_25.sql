-- Aggiornato widget Contratti in scadenza (sostituito fatturabile con pianificabile)
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) AND rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Aggiunto numero di email da inviare in contemporanea
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Numero email da inviare in contemporanea per account', '10', 'integer', 1, 'Newsletter', 2, 'Numero di email della Coda di invio da inviare in contemporanea per account email');

-- Aggiornamento gestione destinatari per newsletter e liste relative
ALTER TABLE `em_newsletter_anagrafica` DROP FOREIGN KEY `em_newsletter_anagrafica_ibfk_2`;
ALTER TABLE `em_newsletter_anagrafica`
    ADD `record_type` VARCHAR(255) NOT NULL BEFORE `id_anagrafica`, CHANGE `id_anagrafica` `record_id` INT(11) NOT NULL;
UPDATE `em_newsletter_anagrafica`
SET `record_type` ='Modules\\Anagrafiche\\Anagrafica';

ALTER TABLE `em_list_anagrafica` DROP FOREIGN KEY `em_list_anagrafica_ibfk_2`;
ALTER TABLE `em_list_anagrafica`
    ADD `record_type` VARCHAR(255) NOT NULL BEFORE `id_anagrafica`, CHANGE `id_anagrafica` `record_id` INT(11) NOT NULL;
UPDATE `em_list_anagrafica`
SET `record_type` ='Modules\\Anagrafiche\\Anagrafica';

ALTER TABLE `em_list_anagrafica` RENAME TO `em_list_receiver`;
ALTER TABLE `em_newsletter_anagrafica` RENAME TO `em_newsletter_receiver`;

UPDATE `zz_views` SET `query` = '(SELECT COUNT(*) FROM `em_newsletter_receiver` WHERE `em_newsletter_receiver`.`id_newsletter` = `em_newsletters`.`id`)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Newsletter') AND `name` = 'Destinatari';
