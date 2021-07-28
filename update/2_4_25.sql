-- Aggiornamento associazione utente - sede aziendale
ALTER TABLE `zz_user_sedi` RENAME `zz_user_sede`;
ALTER TABLE `zz_user_sede` CHANGE `id_user` `id_utente` INT(11) NOT NULL, CHANGE `idsede` `id_sede` INT(11) NOT NULL;

DELETE FROM `zz_user_sede` WHERE `id_sede` NOT IN (SELECT `id` FROM `an_sedi`);
DELETE FROM `zz_user_sede` WHERE `id_utente` NOT IN (SELECT `id` FROM `zz_users`);

ALTER TABLE `zz_user_sede` ADD FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE, ADD FOREIGN KEY (`id_sede`) REFERENCES `an_sedi`(`id`) ON DELETE CASCADE;
