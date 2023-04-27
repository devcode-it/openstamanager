-- Aggiunto import Preventivi
INSERT INTO `zz_imports` (`id`, `id_module`, `name`, `class`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Preventivi'), 'Preventivi', 'Modules\\Preventivi\\Import\\CSV', NULL);

-- Modifica nomi colonne Totali
UPDATE `zz_views` SET `name` = 'Totale documento' WHERE `name` = 'Totale ivato';
UPDATE `zz_views` SET `name` = 'Imponibile' WHERE `name` = 'Totale';