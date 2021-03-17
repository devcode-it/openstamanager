-- Aggiornamento Netto a pagare per considerare lo Sconto finale
UPDATE `zz_views` SET `query` = '(righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale`) * (1 - `co_documenti`.`sconto_finale_percentuale` / 100) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `name` = 'Netto a pagare' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = '(righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale`) * (1 - `co_documenti`.`sconto_finale_percentuale` / 100) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `name` = 'Netto a pagare' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di acquisto');

-- Fix aggiornamento query Articoli per aggiunta quantità ordinata
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `mg_articoli`
    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT JOIN (
        SELECT SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS qta_impegnata, or_righe_ordini.idarticolo
        FROM or_righe_ordini
            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id
            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id
        WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 1)
            AND or_tipiordine.dir = ''entrata''
            AND or_righe_ordini.confermato = 1
        GROUP BY idarticolo
    ) a ON a.idarticolo = mg_articoli.id
    LEFT JOIN (
        SELECT SUM(or_righe_ordini.qta) AS qta_ordinata, or_righe_ordini.idarticolo
        FROM or_righe_ordini
            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id
            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id
        WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 1)
            AND or_tipiordine.dir = ''uscita''
            AND or_righe_ordini.confermato = 1
        GROUP BY idarticolo
    ) ordini_fornitore ON ordini_fornitore.idarticolo = mg_articoli.id
    LEFT JOIN mg_categorie ON mg_articoli.id_categoria = mg_categorie.id
    LEFT JOIN mg_categorie AS sottocategorie ON mg_articoli.id_sottocategoria = sottocategorie.id
WHERE 1=1 AND (`mg_articoli`.`deleted_at`) IS NULL
HAVING 2=2
ORDER BY `mg_articoli`.`descrizione`' WHERE `zz_modules`.`name`='Articoli';

-- Rimozione flag inutilizzato
ALTER TABLE `or_statiordine` DROP `annullato`;

-- Aggiunta flag "impegnato" sugli stati ordine
ALTER TABLE `or_statiordine` ADD `impegnato` BOOLEAN NOT NULL DEFAULT FALSE AFTER `icona`;
UPDATE `or_statiordine` SET `impegnato` = 1 WHERE `descrizione` IN('Evaso', 'Parzialmente evaso', 'Accettato', 'Parzialmente fatturato', 'Fatturato');

-- Aggiornamento calcolo quantità impegnate ed evase
UPDATE `zz_modules` SET `options` = 'SELECT |select|\nFROM `mg_articoli`\n    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica\n    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id\n    LEFT JOIN (\n        SELECT SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS qta_impegnata, or_righe_ordini.idarticolo\n        FROM or_righe_ordini\n            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id\n            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id\n            INNER JOIN or_statiordine ON or_ordini.idstatoordine = or_statiordine.id\n        WHERE\n            or_tipiordine.dir = \'entrata\'\n            AND or_righe_ordini.confermato = 1\n            AND or_statiordine.impegnato = 1\n        GROUP BY idarticolo\n    ) a ON a.idarticolo = mg_articoli.id\n    LEFT JOIN (\n        SELECT SUM(or_righe_ordini.qta-or_righe_ordini.qta_evasa) AS qta_ordinata, or_righe_ordini.idarticolo\n        FROM or_righe_ordini\n            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id\n            INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id\n            INNER JOIN or_statiordine ON or_ordini.idstatoordine = or_statiordine.id\n        WHERE\n            or_tipiordine.dir = \'uscita\'\n            AND or_righe_ordini.confermato = 1\n            AND or_statiordine.impegnato = 1\n        GROUP BY idarticolo\n    ) ordini_fornitore ON ordini_fornitore.idarticolo = mg_articoli.id\n    LEFT JOIN mg_categorie ON mg_articoli.id_categoria = mg_categorie.id\n    LEFT JOIN mg_categorie AS sottocategorie ON mg_articoli.id_sottocategoria = sottocategorie.id\nWHERE 1=1 AND (`mg_articoli`.`deleted_at`) IS NULL\nHAVING 2=2\nORDER BY `mg_articoli`.`descrizione`' WHERE `zz_modules`.`name` = 'Articoli';

-- Fix query widgets Fatturato e Acquisti
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\nWHERE co_tipidocumento.dir=\'entrata\' |segment| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Fatturato';
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\nWHERE co_tipidocumento.dir=\'uscita\' |segment| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Acquisti';

-- Aggiunta risorse API per creazione e modifica Articoli
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'create', 'articolo', 'Modules\\Articoli\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'update', 'articolo', 'Modules\\Articoli\\API\\v1\\Articoli', '1');

-- Fix visualizzazione attività in dashboard
UPDATE `zz_segments` SET `clause` = '(orario_inizio BETWEEN \'|period_start|\' AND \'|period_end|\' OR orario_fine BETWEEN \'|period_start|\' AND \'|period_end|\')' WHERE `zz_segments`.`name` = 'Attività';