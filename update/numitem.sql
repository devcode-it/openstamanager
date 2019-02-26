-- Aggiunto codice cig e codice cup per contratti e interventi
ALTER TABLE `co_contratti` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
ALTER TABLE `in_interventi` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
ALTER TABLE `or_ordini` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
ALTER TABLE `co_preventivi` ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;
