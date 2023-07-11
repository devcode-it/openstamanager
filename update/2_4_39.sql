-- Aumento dimensione massima codicerea
ALTER TABLE `an_anagrafiche` CHANGE `codicerea` `codicerea` VARCHAR(23) DEFAULT NULL; 

-- Pulizia campi inutilizzati
ALTER TABLE `an_anagrafiche` DROP `cciaa`;
ALTER TABLE `an_anagrafiche` DROP `cciaa_citta`;

-- Aggiunta nazioni
INSERT INTO `an_nazioni` (`id`, `nome`, `iso2`, `name`) VALUES (NULL, 'Palestina', 'PS', 'Palestine');

-- Fix query viste Giacenze sedi
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT JOIN (SELECT SUM(qta - qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 0) GROUP BY idarticolo) ordini ON ordini.idarticolo = mg_articoli.id
    LEFT JOIN (SELECT `idarticolo`, `idsede`, SUM(`qta`) AS `qta` FROM `mg_movimenti` WHERE `idsede` = |giacenze_sedi_idsede| GROUP BY `idarticolo`, `idsede`) movimenti ON `mg_articoli`.`id` = `movimenti`.`idarticolo`
    LEFT JOIN (SELECT id, nome AS nome FROM mg_categorie)AS categoria ON categoria.id= mg_articoli.id_categoria
    LEFT JOIN (SELECT id, nome AS nome FROM mg_categorie)AS sottocategoria ON sottocategoria.id=mg_articoli.id_sottocategoria
	LEFT JOIN (SELECT co_iva.percentuale AS perc, co_iva.id, zz_settings.nome FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore)AS iva ON iva.nome= 'Iva predefinita' 
WHERE 
    1=1 AND `mg_articoli`.`deleted_at` IS NULL 
HAVING
    2=2 AND `Q.tà` > 0
ORDER BY
    `descrizione`" WHERE `name` = 'Giacenze sedi';

-- Impostazione per visualizzare i promemoria su app
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Visualizza promemoria', '1', 'boolean', '1', 'Applicazione', '5', '');

-- Aggiunta del riferimento utente nei movimenti
ALTER TABLE `mg_movimenti` ADD `idutente` INT NULL DEFAULT NULL;

-- Aggiunta valori buffer Datatables
UPDATE `zz_settings` SET `tipo` = 'list[5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,250,500,1000]' WHERE `zz_settings`.`nome` = 'Lunghezza in pagine del buffer Datatables';
