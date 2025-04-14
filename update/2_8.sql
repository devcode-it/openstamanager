-- Servizio verifica iban con ibanapi.com
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES (NULL, 'Endpoint ibanapi.com', 'https://api.ibanapi.com', 'string', '1', 'API', NULL, '0');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES (NULL, 'Api key ibanapi.com', '', 'string', '1', 'API', NULL, '0');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`) VALUES (1, (SELECT id FROM zz_settings WHERE `nome`='Endpoint ibanapi.com'), 'Endpoint ibanapi.com');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`) VALUES (1, (SELECT id FROM zz_settings WHERE `nome`='Api key ibanapi.com'), 'Api key ibanapi.com');