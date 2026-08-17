-- Associazione automezzo alle singole sessioni delle attività (#1693)
ALTER TABLE `in_interventi_tecnici`
    ADD `id_automezzo` INT NULL AFTER `id_tecnico`,
    ADD INDEX `idx_in_interventi_tecnici_automezzo` (`id_automezzo`);
