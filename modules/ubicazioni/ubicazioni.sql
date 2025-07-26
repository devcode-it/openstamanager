--
-- Struttura della tabella `mg_ubicazioni`
--

CREATE TABLE `mg_ubicazioni` (
  `id` int(11) NOT NULL,
  `u_label` varchar(255) NOT NULL,
  `colore` varchar(255) DEFAULT NULL,
  `u_tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `mg_ubicazioni`
--
ALTER TABLE `mg_ubicazioni`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `mg_ubicazioni`
--
ALTER TABLE `mg_ubicazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

--
-- Struttura della tabella `mg_ubicazioni_lang`
--

CREATE TABLE `mg_ubicazioni_lang` (
  `id` int(11) NOT NULL,
  `id_lang` int(11) NOT NULL,
  `id_record` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `mg_ubicazioni_lang`
--
ALTER TABLE `mg_ubicazioni_lang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mg_ubicazioni_lang_ibfk_2` (`id_lang`) USING BTREE,
  ADD KEY `mg_ubicazioni_lang_ibfk_1` (`id_record`) USING BTREE;

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `mg_ubicazioni_lang`
--
ALTER TABLE `mg_ubicazioni_lang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `mg_ubicazioni_lang`
--
ALTER TABLE `mg_ubicazioni_lang`
  ADD CONSTRAINT `mg_ubicazioni_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `mg_ubicazioni` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mg_ubicazioni_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs` (`id`) ON DELETE CASCADE;
COMMIT;

--
-- Dump dei dati per la tabella `zz_modules`
--

INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES
('Ubicazioni', 'ubicazioni', 'ubicazioni', 'SELECT |select| FROM `mg_ubicazioni` LEFT JOIN `mg_ubicazioni_lang` ON (`mg_ubicazioni`.`id` = `mg_ubicazioni_lang`.`id_record` AND `mg_ubicazioni_lang`.|lang|) WHERE 1=1 HAVING 2=2 ORDER BY TRIM(`mg_ubicazioni`.`u_label`)', '', 'nav-icon fa fa-circle-o', '2.8.1', '2.8.1', 110, 20, 1, 1, 0, 0);
--
-- Dump dei dati per la tabella `zz_modules_lang`
--

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
( 1, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ubicazioni'), 'Ubicazioni', 'Ubicazioni');
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
( 2, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ubicazioni'), 'Ubicazioni', 'Ubicazioni');
