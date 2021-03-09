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