-- Soglia oltre la quale i sottoconti di un mastro del Piano dei conti vengono mostrati come tabella con ricerca e impaginazione
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Soglia datatable sottoconti', '500', 'integer', 1, 'Piano dei conti', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Soglia datatable sottoconti'), 'Soglia datatable sottoconti', 'Numero di sottoconti oltre il quale, espandendo un mastro nel Piano dei conti, i sottoconti vengono mostrati in una tabella con ricerca e impaginazione invece dell\'elenco semplice. Default 500.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Soglia datatable sottoconti'), 'Subaccount datatable threshold', 'Number of subaccounts above which, when expanding an account in the Chart of accounts, subaccounts are shown in a searchable, paginated table instead of the plain list. Default 500.');

-- Indice per la selezione paginata dei sottoconti per mastro
ALTER TABLE `co_piano_dei_conti3` ADD INDEX `idx_id_piano_dei_conti2_numero` (`id_piano_dei_conti2`, `numero`);

-- Indici per il join anagrafica del dettaglio sottoconti
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_id_conto_cliente` (`id_conto_cliente`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_id_conto_fornitore` (`id_conto_fornitore`);
