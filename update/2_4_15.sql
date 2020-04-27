-- Aggiunta del campo per permettere la modifica delle Viste di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Modifica Viste di default', '0', 'boolean', 0, 'Generali');

-- Retrofix
UPDATE `mg_articoli` SET `id_categoria` = NULL WHERE `codice` = 'DELETED';
