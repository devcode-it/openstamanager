-- Aggiunta vincolo fra ddt e righe ddt
ALTER TABLE `dt_righe_ddt` ADD CONSTRAINT `dt_righe_ddt_ibfk_2` FOREIGN KEY (`idddt`) REFERENCES `dt_ddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Rimuovi avviso fatture estere', '0', 'boolean', '1', 'Fatturazione elettronica', NULL, "Abilitare per rimuovere l'avviso di fatture elettroniche estere da inviare, in caso le fatture elettroniche estere non vengano inviate.");