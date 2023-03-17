-- Aggiunta popup Stampa riepilogo interventi
UPDATE `zz_widgets` SET `more_link` = './modules/interventi/widgets/stampa_riepilogo.php', `more_link_type` = 'popup' WHERE `zz_widgets`.`name` = 'Stampa riepilogo';

-- Aggiunta righe aggiuntive per tipologia intervento
CREATE TABLE IF NOT EXISTS `in_righe_tipiinterventi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_tipointervento` varchar(25) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  `qta` decimal(12,4) NOT NULL,
  `um` varchar(25) DEFAULT NULL,
  `prezzo_acquisto` decimal(12,4) NOT NULL,
  `prezzo_vendita` decimal(12,4) NOT NULL,
  `idiva` int NOT NULL,
  `subtotale` decimal(12,4) NOT NULL,
  PRIMARY KEY (`id`)
);

-- Aggiunto flag is_bloccata per relazioni
ALTER TABLE `an_relazioni` ADD `is_bloccata` tinyint DEFAULT NULL AFTER `colore`;