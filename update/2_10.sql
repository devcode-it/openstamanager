-- Aggiunta {tipo} come variabile per l'impostazione "Descrizione personalizzata in fatturazione"
UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variabili utilizzabili: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 1;

UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variables availables: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 2;

-- Data inizio e fine competenza per le righe
ALTER TABLE `co_righe_contratti` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `dt_righe_ddt` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `co_righe_documenti` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `co_righe_preventivi` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `in_righe_interventi` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `or_righe_ordini` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `co_righe_promemoria` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;