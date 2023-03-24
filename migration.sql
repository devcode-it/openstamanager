CREATE TABLE `mg_articoli_sedi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_articolo` int(11) NOT NULL,
    `id_sede` int(11) DEFAULT NULL,
    `threshold_qta` int(11) NOT NULL COMMENT 'soglia minima',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`)
SELECT 'Gestisci soglia minima per magazzino', '1', 'boolean', '1', 'Magazzino', '2022-12-21 14:51:03', '2023-01-05 11:29:48', NULL, NULL
FROM `zz_settings`
WHERE ((`id` = '21'));
