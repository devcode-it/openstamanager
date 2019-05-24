-- Fix colonna Totale per Fatture
UPDATE `zz_views` SET `query` = '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + iva_rivalsainps' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND name = 'Totale';
UPDATE `zz_views` SET `query` = '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + iva_rivalsainps' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND name = 'Totale';

-- Fix widget Contratti in scadenza per mostrare i contratti con ore in esaurimento
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato,
       ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.completato = 1)), 0) ) AS ore_rimanenti,
       DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti,
       data_conclusione,
       ore_preavviso_rinnovo,
       giorni_preavviso_rinnovo,
       (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale
FROM co_contratti WHERE
        idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND
        rinnovabile = 1 AND
        YEAR(data_conclusione) > 1970 AND
        (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL
HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo))
ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Miglioramento hooks
ALTER TABLE `zz_hooks` ADD `enabled` boolean NOT NULL DEFAULT 1, ADD `id_module` int(11) NOT NULL;
UPDATE `zz_hooks` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') WHERE `name` = 'Ricevute';
UPDATE `zz_hooks` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') WHERE `name` = 'Fatture';
ALTER TABLE `zz_hooks` ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES
(NULL, 'Ricevute', 'Modules\\Aggiornamenti\\UpdateHook', '7 day', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Aggiornamenti'));
