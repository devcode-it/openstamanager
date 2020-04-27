-- Aggiunta del campo per permettere la modifica delle Viste di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Modifica Viste di default', '0', 'boolean', 0, 'Generali');

-- Retrofix
UPDATE `mg_articoli` SET `id_categoria` = NULL WHERE `codice` = 'DELETED';

-- Ottimizzazione query attività
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM (`in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento` LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede,\'<br>\',an_sedi.telefono,\'<br>\',an_sedi.cellulare,\'<br>\',an_sedi.citta,\' - \', an_sedi.indirizzo) AS info FROM an_sedi) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione LEFT JOIN (SELECT co_righe_documenti.idintervento, CONCAT(\'Fatt. \', co_documenti.numero_esterno,\' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento) AS fattura ON fattura.idintervento = in_interventi.id WHERE 1=1 |date_period(`orario_inizio`,`data_richiesta`)| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `name` = 'Interventi';

-- Aggiunta colonna Rif. fattura per attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Rif. fattura', 'fattura.info', 17, 1, 0, 0, 1);

-- Aggiunta relazione tra articoli e fornitori
CREATE TABLE IF NOT EXISTS `mg_fornitore_articolo` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_articolo` int(11) NOT NULL,
    `id_fornitore` int(11) NOT NULL,
    `codice_fornitore` varchar(255) NOT NULL,
    `descrizione` varchar(255) NOT NULL,
    `prezzo_acquisto` decimal(15, 6) NOT NULL,
    `qta_minima` decimal(15, 6) NOT NULL,
    `giorni_consegna` int(11) NOT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_fornitore`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE
);

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Fornitori Articolo', 'Fornitori', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'fornitori_articolo', 'custom');
