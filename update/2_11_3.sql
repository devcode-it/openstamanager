-- Modalità predefinita per il comando di arrotondamento dei documenti di vendita
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Modalità predefinita arrotondamento documenti di vendita', 'Euro più vicino', 'list[Disabilitato,Euro inferiore,Euro superiore,Euro più vicino]', 1, 'Generali');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Arrotondamento documenti di vendita', 'Modalità proposta dal comando Arrotonda. Il calcolo considera i millesimi.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Sales document rounding', 'Default mode proposed by the Round command. The calculation considers thousandths.');
