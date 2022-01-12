-- Aggiunto condizioni fornitura in contratti
ALTER TABLE `co_contratti` ADD `condizioni_fornitura` TEXT NOT NULL AFTER `informazioniaggiuntive`; 

UPDATE `zz_settings` SET `nome` = 'Condizioni generali di fornitura preventivi' WHERE `zz_settings`.`nome` = 'Condizioni generali di fornitura';
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Condizioni generali di fornitura contratti', '', 'ckeditor', '1', 'Contratti', NULL, NULL);

-- Rimossa visualizzazione stampe disabilitate
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `zz_prints` WHERE 1=1 AND enabled=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Stampe'; 

-- Filtro che esclude gli articoli eliminati dal widget degli articoli in esaurimento
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM mg_articoli WHERE qta < threshold_qta AND attivo=1 AND deleted_at IS NULL' WHERE `zz_widgets`.`name` = 'Articoli in esaurimento'; 

-- Fix problema iva di vendita preselezionata
ALTER TABLE `mg_articoli` CHANGE `idiva_vendita` `idiva_vendita` INT(11) NULL DEFAULT NULL;
UPDATE `mg_articoli` SET `idiva_vendita`=NULL WHERE `idiva_vendita`=0;

-- Impostazione per la modifica di altri tecnici nell'app
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Abilita la modifica di altri tecnici', '1', 'boolean', 1, 'Applicazione', 4, '');

-- Fix widget preventivi in lavorazione
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE idstato=(SELECT id FROM co_statipreventivi WHERE descrizione=\"In lavorazione\") AND default_revision=1' WHERE `zz_widgets`.`name` ='Preventivi in lavorazione';

-- Rimosso controllo is_pianificabile widget contratti in scadenza
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND co_contratti.id NOT IN (SELECT idcontratto_prev FROM co_contratti contratti) HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';