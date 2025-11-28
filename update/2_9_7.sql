-- Ripristino campi firma
ALTER TABLE `in_interventi` ADD `firma_data` DATETIME NULL , ADD `firma_nome` VARCHAR(255) NOT NULL;