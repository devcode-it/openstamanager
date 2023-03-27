CREATE TABLE `ac_acconti` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `idanagrafica` int NULL,
  `idordine` int NULL COMMENT 'se idordine non presente allora l\'acconto non viene fatto sull\'anagrafica',
  `importo` float NOT NULL
);

CREATE TABLE `ac_acconti_righe` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `idacconto` int NOT NULL,
  `idfattura` int NULL,
  `importo_fatturato` float NOT NULL,
  `tipologia` varchar(30) NULL
);

ALTER TABLE `ac_acconti_righe`
CHANGE `idfattura` `idfattura` int(11) NOT NULL AFTER `idacconto`,
ADD `idriga_fattura` int(11) NULL AFTER `idfattura`;

ALTER TABLE `ac_acconti_righe`
ADD `idiva` int(11) NULL AFTER `idriga_fattura`;

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`, `created_at`, `updated_at`) VALUES
(53,	'Anticipi',	'Anticipi',	24,	24,	'tab',	'ordini.anticipi.php',	1,	1,	0,	'',	'',	NULL,	NULL,	'',	'',	'2022-03-07 10:22:56',	'2022-03-07 10:22:56');
