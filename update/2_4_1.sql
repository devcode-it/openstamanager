ALTER TABLE `co_contratti` ADD `idsede` INT NOT NULL AFTER `idanagrafica`;

-- Imposto conto cassa per contanti e rimesse
UPDATE `co_pagamenti` SET `idconto_vendite` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Cassa'), `idconto_acquisti` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Cassa')  WHERE `co_pagamenti`.`descrizione` = 'Contanti' OR `co_pagamenti`.`descrizione` LIKE 'Rimessa %';

-- Imposto conto banca per tutti i bonifici e ri.ba.
UPDATE `co_pagamenti` SET `idconto_vendite` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C'), `idconto_acquisti` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C')  WHERE `co_pagamenti`.`descrizione` LIKE 'Bonifico %' OR `co_pagamenti`.`descrizione` LIKE 'Ri.Ba. %';

-- Indirizzo PEC
ALTER TABLE `an_anagrafiche` ADD `pec` VARCHAR(255) NOT NULL AFTER `email`;

-- ISO 3166-1 alpha-2 code per nazioni
ALTER TABLE `an_nazioni` ADD `iso2` VARCHAR(2) NOT NULL AFTER `nome`;

-- ISO 2 per ITALIA (https://it.wikipedia.org/wiki/ISO_3166-1_alpha-2)
UPDATE `an_nazioni` SET `iso2` = 'IT' WHERE `an_nazioni`.`nome` = 'ITALIA';

-- Aggiunto name per i filtri
ALTER TABLE `zz_group_module` ADD `name` VARCHAR(255) NOT NULL AFTER `idmodule`;

UPDATE `zz_group_module` SET `name` = 'Mostra interventi ai tecnici coinvolti' WHERE `zz_group_module`.`id` = 1;
UPDATE `zz_group_module` SET `name` = 'Mostra interventi ai clienti coinvolti' WHERE `zz_group_module`.`id` = 5;

-- Abilito plugin Pianificazione fatturazione in contratti
UPDATE `zz_plugins` SET `enabled` = '1' WHERE `zz_plugins`.`name` = 'Pianificazione fatturazione';

-- Abilito widget Rate contrattuali in dashboard
UPDATE `zz_widgets` SET `enabled` = '1' WHERE `zz_widgets`.`name` = 'Rate contrattuali';

-- Help text per i plugins
ALTER TABLE `zz_plugins` ADD `help` VARCHAR(255) NOT NULL AFTER `directory`;

-- Help text per plugin Ddt del cliente
UPDATE `zz_plugins` SET `help` = 'Righe ddt del cliente. I ddt senza righe non saranno visualizzati.' WHERE `zz_plugins`.`name` = 'Ddt del cliente';

-- Creazione tablla per modelli primanota
CREATE TABLE IF NOT EXISTS `co_movimenti_modelli` (
  `id` int(11) NOT NULL,
  `idmastrino` int(11) NOT NULL,
  `descrizione` text NOT NULL,
  `idconto` int(11) NOT NULL
);

ALTER TABLE `co_movimenti_modelli` ADD PRIMARY KEY (`id`);

ALTER TABLE `co_movimenti_modelli` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
-- Modulo modelli prima nota
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Modelli prima nota', 'Modelli prima nota', 'modelli_primanota', 'SELECT |select| FROM `co_movimenti_modelli` WHERE 1=1 GROUP BY `idmastrino` HAVING 2=2', '', 'fa fa-angle-right', '2.4.1', '2.4.1', '1', '40', '1', '1');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `enabled`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name='Modelli prima nota'), 'id', 'co_movimenti_modelli.id', '0', '1', '0', '0', NULL, NULL, '0', '0', '1');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `enabled`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name='Modelli prima nota'), 'Causale predefinita', 'co_movimenti_modelli.descrizione', '1', '1', '0', '0', NULL, NULL, '1', '0', '1');

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES
(1, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='id' )),
(1, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='Causale predefinita' )),
(2, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='id' )),
(2, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='Causale predefinita' )),
(3, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='id' )),
(3, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='Causale predefinita' )),
(4, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='id' )),
(4, (SELECT id FROM `zz_views` WHERE id_module=(SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND name='Causale predefinita' ));


-- Widget per stampa calendario
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help` ) VALUES (NULL, 'Stampa calendario', 'print', '1', 'controller_top', 'col-md-12', NULL, '#4ccc4c', 'fa fa-print', '', './modules/dashboard/widgets/stampa_calendario.dashboard.php', 'popup', '', 'Stampa calendario', '1', '7', NULL);

-- Stampa calendario
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `main`, `default`, `enabled`) VALUES (NULL, '1', '1', 'Stampa calendario', 'Stampa calendario', 'dashboard', '', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- Rimosso group by nome banche
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_banche` WHERE 1=1 AND deleted = 0 HAVING 2=2' WHERE `zz_modules`.`name` = 'Banche';


-- impianti per pianificazione contratti
ALTER TABLE `co_righe_contratti` ADD `idimpianti` VARCHAR(255) NOT NULL AFTER `idsede`;


-- Struttura della tabella `co_righe_contratti_materiali`
CREATE TABLE IF NOT EXISTS `co_righe_contratti_materiali` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `um` varchar(25) NOT NULL,
  `prezzo_vendita` decimal(12,4) NOT NULL,
  `prezzo_acquisto` decimal(12,4) NOT NULL,
  `idiva` int(11) NOT NULL,
  `desc_iva` varchar(255) NOT NULL,
  `iva` decimal(12,4) NOT NULL,
  `id_riga_contratto` int(11) DEFAULT NULL,
  `sconto` decimal(12,4) NOT NULL,
  `sconto_unitario` decimal(12,4) NOT NULL,
  `tipo_sconto` enum('UNT','PRC') NOT NULL DEFAULT 'UNT',
  PRIMARY KEY (`id`),
  KEY `idintervento` (`id_riga_contratto`)
);

-- Modifica query wiget per mostrare solo quelli che non sono sati rinnovati
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato, co_contratti.id, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970 HAVING ISNULL((SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev=co_contratti.id )) ORDER BY giorni_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Aggiunto campo data su movimenti articoli
ALTER TABLE `mg_movimenti` ADD `data` DATE NOT NULL AFTER `movimento`;

-- Campo per indentificare i movimenti manuali
ALTER TABLE `mg_movimenti` ADD `manuale` TINYINT(1) NOT NULL AFTER `data`;

-- Aggiunta possibilità di selezionare anche i conti in 620 Costi diversi negli acquisti
UPDATE `co_pianodeiconti2` SET `dir` = 'uscita' WHERE `co_pianodeiconti2`.`descrizione` = 'Costi diversi';