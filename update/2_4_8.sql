-- Fix problema username vuoti in zz_logs
ALTER TABLE `zz_logs` CHANGE `username` `username` varchar(255);

-- Aggiunta tipologia di documento Parcella
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Parcella', 'entrata', '0', 'TD06');
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES (NULL, 'Parcella', 'uscita', '0', 'TD06');
