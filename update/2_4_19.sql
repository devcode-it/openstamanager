-- Aggiunta impostazione conto anticipi
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto anticipo clienti', '55', 'query=SELECT id, CONCAT_WS(\' - \', numero, descrizione) AS descrizione FROM co_pianodeiconti3', '1', 'Fatturazione', NULL, NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto anticipo fornitori', '55', 'query=SELECT id, CONCAT_WS(\' - \', numero, descrizione) AS descrizione FROM co_pianodeiconti3', '1', 'Fatturazione', NULL, NULL);

INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES (NULL, '000011', 'Anticipo fornitori', '8', '', '100.00');

INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES (NULL, '000011', 'Anticipo clienti', '2', '', '100.00');