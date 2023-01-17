-- Fix query viste Utenti e permessi
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `zz_groups` 
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`
WHERE 
    1=1
HAVING 
    2=2 
ORDER BY 
    `id`, 
    `nome` ASC" WHERE `name` = 'Utenti e permessi';


-- Aggiunta campo Pagamento predefinito in vista Anagrafiche
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
('2', 'Pagamento cliente', '`pagvendita`.`nome`', '15', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
('2', 'Pagamento fornitore', '`pagacquisto`.`nome`', '16', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');


INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES
('1', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento cliente' AND zz_modules.name='Anagrafiche')),
('2', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento cliente' AND zz_modules.name='Anagrafiche')),
('3', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento cliente' AND zz_modules.name='Anagrafiche')),
('4', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento cliente' AND zz_modules.name='Anagrafiche')),
('1', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento fornitore' AND zz_modules.name='Anagrafiche')),
('2', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento fornitore' AND zz_modules.name='Anagrafiche')),
('3', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento fornitore' AND zz_modules.name='Anagrafiche')),
('4', (SELECT zz_views.id FROM zz_views INNER JOIN zz_modules ON zz_modules.id = zz_views.id_module WHERE zz_views.name = 'Pagamento fornitore' AND zz_modules.name='Anagrafiche'));

UPDATE `zz_modules` SET `options` = "SELECT 
|select|
FROM
    `an_anagrafiche`
LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nomesede SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY idanagrafica) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nome SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY idanagrafica) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`
LEFT JOIN (SELECT `co_pagamenti`.`descrizione`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti`)AS pagvendita ON IF(`an_anagrafiche`.`idpagamento_vendite`>0,`an_anagrafiche`.`idpagamento_vendite`= `pagvendita`.`id`,'')
LEFT JOIN (SELECT `co_pagamenti`.`descrizione`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti`)AS pagacquisto ON IF(`an_anagrafiche`.`idpagamento_acquisti`>0,`an_anagrafiche`.`idpagamento_acquisti`= `pagacquisto`.`id`,'')
WHERE
    1=1 AND `deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`, `pagvendita`.`nome`, `pagacquisto`.`nome`
HAVING
    2=2
ORDER BY
    TRIM(`ragione_sociale`)" WHERE `name` = 'Anagrafiche';

-- Aggiunta descrizione codice natura N7
SELECT @codice := MAX(CAST(codice AS UNSIGNED))+1 FROM co_iva WHERE deleted_at IS NULL;
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Regime OSS, D.Lgs. 83/2021', '0.00', '0.00', '1', NULL, 'N7', NULL, @codice, 'I', '1'); 