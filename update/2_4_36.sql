-- Fix eliminazione attività collegata
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_3`; 
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_3` FOREIGN KEY (`id_preventivo`) REFERENCES `co_preventivi`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_4`;
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_4` FOREIGN KEY (`id_contratto`) REFERENCES `co_contratti`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_7`;
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_7` FOREIGN KEY (`id_ordine`) REFERENCES `or_ordini`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;