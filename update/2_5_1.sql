-- Aggiunta user-agent nei log
ALTER TABLE `zz_logs` ADD `user_agent` VARCHAR(255) NULL DEFAULT NULL AFTER `ip`;

-- Allineamento vista Scadenzario
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenziario`
    LEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN `co_pagamenti` as b ON `b`.`id` = `co_scadenziario`.`id_pagamento`
    LEFT JOIN `co_pagamenti_lang` as b_lang ON (`b_lang`.`id_record` = `b`.`id` AND `b_lang`.|lang|)
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN (SELECT COUNT(id_email) as emails, zz_operations.id_record FROM zz_operations WHERE id_module IN(SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Scadenzario' AND |lang|) AND `zz_operations`.`op` = 'send-email' GROUP BY zz_operations.id_record) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`
WHERE 
    1=1 AND (`co_statidocumento`.`id` IS NULL OR `co_statidocumento`.`id` IN(SELECT id_record FROM co_statidocumento_lang WHERE name IN ('Emessa', 'Parzialmente pagato', 'Pagato'))) 
GROUP BY 
    `co_scadenziario`.`id`
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Scadenzario' LIMIT 1);
UPDATE `zz_views` LEFT JOIN `zz_views_lang` ON (`zz_views_lang`.`id_record` = `zz_views`.`id` AND `zz_views_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) SET `zz_views`.`query` = 'IF(`co_scadenziario`.`id_pagamento` < 1, `co_pagamenti_lang`.`name`, `b_lang`.`name`)' WHERE `zz_modules_lang`.`name` = 'Scadenzario' AND `zz_views_lang`.`name` = 'Tipo di pagamento';

-- Fix plugin Impianti del cliente
UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Matricola, Nome, Data, Descrizione", "query": "SELECT id, (SELECT `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua") WHERE `name` = "Impianti") AS _link_module_, id AS _link_record_, matricola AS Matricola, nome AS Nome, DATE_FORMAT(data, "%d/%m/%Y") AS Data, descrizione AS Descrizione FROM my_impianti WHERE idanagrafica=|id_parent| HAVING 2=2 ORDER BY id DESC"} ]}' WHERE `zz_plugins`.`id` = (SELECT `id_record` FROM `zz_plugins_lang` WHERE `name` = 'Impianti del cliente' LIMIT 1);

-- Fix plugin Contratti del cliente
UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Numero, Nome, Totale, Stato, Predefinito", "query": "SELECT `co_contratti`.`id`, `numero` AS Numero, `co_contratti`.`nome` AS Nome, `an_anagrafiche`.`ragione_sociale` AS Cliente, FORMAT(`righe`.`totale_imponibile`,2) AS Totale, `co_staticontratti_lang`.`name` AS Stato, IF(`co_contratti`.`predefined`=1, "SÌ", "NO") AS Predefinito FROM `co_contratti` LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id` LEFT JOIN `co_staticonrtatti_lang` ON (co_staticontratti.`id` = `co_staticonrtatti_lang`.`id_record` AND `co_staticonrtatti_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto` ) AS righe ON `co_contratti`.`id` =`righe`.`idcontratto` WHERE 1=1 AND `co_contratti`.`idanagrafica`=|id_parent| GROUP BY `co_contratti`.`id` HAVING 2=2 ORDER BY `co_contratti`.`id` ASC"} ]}' WHERE `zz_plugins`.`id` = (SELECT `id_record` FROM `zz_plugins_lang` WHERE `name` = 'Contratti del cliente' LIMIT 1);

-- Allineamento vista Listini
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `mg_prezzi_articoli`
    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `mg_prezzi_articoli`.`id_anagrafica`
    INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `mg_prezzi_articoli`.`id_articolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `mg_categorie` AS `categoria` ON `mg_articoli`.`id_categoria` = `categoria`.`id`
    LEFT JOIN `mg_categorie_lang` AS `categorialang` ON (`categorialang`.`id_record` = `categoria`.`id` AND `categorialang`.|lang|)
    LEFT JOIN `mg_categorie` AS `sottocategoria` ON `mg_articoli`.`id_sottocategoria` = `sottocategoria`.`id`
    LEFT JOIN `mg_categorie_lang` AS `sottocategorialang` ON (`sottocategorialang`.`id_record` = `sottocategoria`.`id` AND `sottocategorialang`.|lang|)
    LEFT JOIN `zz_modules_lang` ON `zz_modules_lang`.`name`= 'Articoli'
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_modules_lang`.`id_record`
    LEFT JOIN (SELECT `codice_fornitore` AS codice, `id_articolo`, `id_fornitore`, `barcode_fornitore` AS barcode, `deleted_at` FROM `mg_fornitore_articolo`) AS fornitore ON `mg_prezzi_articoli`.`id_articolo`= `fornitore`.`id_articolo` AND `mg_prezzi_articoli`.`id_anagrafica`=`fornitore`.`id_fornitore` AND `fornitore`.`deleted_at` IS NULL
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL AND `an_anagrafiche`.`deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `an_anagrafiche`.`ragione_sociale`" WHERE `zz_modules`.`id` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Listini' LIMIT 1);

UPDATE `zz_views` 
SET `query` = 'CONCAT(`mg_articoli`.`codice`, " - ", `mg_articoli_lang`.`name`)' 
WHERE `zz_views`.`id` IN (
    SELECT * 
    FROM (
        SELECT `id_record` 
        FROM `zz_views_lang` 
        INNER JOIN `zz_views` ON `zz_views`.`id` = `zz_views_lang`.`id_record` 
        WHERE `name` = 'Articolo' 
        AND `id_module` = (
            SELECT `zz_modules`.`id` 
            FROM `zz_modules` 
            LEFT JOIN `zz_modules_lang` ON `zz_modules`.`id` = `zz_modules_lang`.`id_record` 
            WHERE `name` = 'Listini'
            LIMIT 1
        )
    ) AS tmp
);

UPDATE `zz_views` 
SET `query` = '`categorialang`.`name`' 
WHERE `zz_views`.`id` IN (
    SELECT * 
    FROM (
        SELECT `id_record` 
        FROM `zz_views_lang` 
        INNER JOIN `zz_views` ON `zz_views`.`id` = `zz_views_lang`.`id_record` 
        WHERE `name` = 'Categoria' 
        AND `id_module` = (
            SELECT `zz_modules`.`id` 
            FROM `zz_modules` 
            LEFT JOIN `zz_modules_lang` ON `zz_modules`.`id` = `zz_modules_lang`.`id_record` 
            WHERE `name` = 'Listini'
            LIMIT 1
        )
    ) AS tmp
);

UPDATE `zz_views` 
SET `query` = '`sottocategorialang`.`name`' 
WHERE `zz_views`.`id` IN (
    SELECT * 
    FROM (
        SELECT `id_record` 
        FROM `zz_views_lang` 
        INNER JOIN `zz_views` ON `zz_views`.`id` = `zz_views_lang`.`id_record` 
        WHERE `name` = 'Sottocategoria' 
        AND `id_module` = (
            SELECT `zz_modules`.`id` 
            FROM `zz_modules` 
            LEFT JOIN `zz_modules_lang` ON `zz_modules`.`id` = `zz_modules_lang`.`id_record` 
            WHERE `name` = 'Listini'
            LIMIT 1
        )
    ) AS tmp
);