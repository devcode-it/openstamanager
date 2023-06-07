-- Aggiunta vincolo fra ddt e righe ddt
ALTER TABLE `dt_righe_ddt` ADD CONSTRAINT `dt_righe_ddt_ibfk_2` FOREIGN KEY (`idddt`) REFERENCES `dt_ddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 