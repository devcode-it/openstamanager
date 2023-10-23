-- Aggiunto modulo Stati ordini
INSERT INTO `zz_modules` (`name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES ('Stati fatture', 'Stati fatture','stati_fattura', 'SELECT |select| FROM `co_statidocumento` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.50', '2.4.50', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'Icona', 'icona', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'Descrizione', 'descrizione', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'color_Colore', 'colore', 1, 0, 0, 1, 0);

-- Evita di caricare impostazioni doppie
-- Non compatibile con versioni di MySQL < 5.7
ALTER TABLE `zz_settings` ADD UNIQUE(`nome`);

-- Evita di caricare username doppi
-- Non compatibile con versioni di MySQL < 5.7
ALTER TABLE `zz_users` ADD UNIQUE(`username`);
