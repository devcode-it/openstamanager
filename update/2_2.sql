-- Aggiunta ai contratti il collegamento con il contratto precedente
ALTER TABLE `co_contratti` ADD `idcontratto_prev` INT NOT NULL;

-- Aggiunta vista dashboard (mese,settimana,giorno)
INSERT INTO `zz_impostazioni` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Vista dashboard', 'settimana', 'list[mese,settimana,giorno]', '1', 'Generali');

-- Aggiungo nuovi valori predefiniti per le anagrafiche
ALTER TABLE  `an_anagrafiche` ADD  `idtipointervento_default` VARCHAR(25) NOT NULL;

-- Creo tabella my_impianti_contratti
CREATE TABLE IF NOT EXISTS `my_impianti_contratti` (
  `idcontratto` varchar(25) NOT NULL,
  `matricola` varchar(25) NOT NULL
) ENGINE=InnoDB;

-- Aggiunta sesso nelle anagrafiche
ALTER TABLE `an_anagrafiche` ADD `sesso` ENUM('', 'M', 'F') NOT NULL AFTER `luogo_nascita`;

-- Aggiunta tipo anagrafica
ALTER TABLE `an_anagrafiche` ADD `tipo` ENUM('', 'Azienda', 'Privato', 'Ente pubblico') NOT NULL AFTER `ragione_sociale`;

-- Aggiunta scelta impostazioni sicurezza SMTP
INSERT INTO `zz_impostazioni` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Sicurezza SMTP', 'Nessuna', 'list[Nessuna,TLS,SSL]', '1', 'Email');

-- nascondo opzione con indirizzo email destinatario del modulo bug
UPDATE `zz_impostazioni` SET `editable` = '0' WHERE `nome` = 'Destinatario';

-- Aggiornamento query moduli per il nuovo sistema di caricamento via ajax
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Ragione sociale, Tipologia, Città, Telefono, color_Rel.",	"query": "SELECT `idanagrafica` AS `id`, ragione_sociale AS `Ragione sociale`, (SELECT GROUP_CONCAT(descrizione SEPARATOR '', '') FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica GROUP BY idanagrafica HAVING idanagrafica=an_anagrafiche.idanagrafica) AS `Tipologia`, citta AS `Città`, telefono AS `Telefono`, an_relazioni.colore AS `color_Rel.`, an_relazioni.descrizione AS `color_title_Rel.`, deleted FROM an_anagrafiche LEFT OUTER JOIN an_relazioni ON an_anagrafiche.idrelazione=an_relazioni.id HAVING 1=1 AND deleted=0 ORDER BY `ragione_sociale`"}	]}' WHERE `name` = 'Anagrafiche';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "ID, Tipo, Ragione sociale, Stato, Data inizio, Data fine, _print_",	"query": "SELECT `in_interventi`.`idanagrafica`, `in_interventi`.`idintervento` AS `id`, `in_interventi`.`idintervento` AS `ID`, `ragione_sociale` AS `Ragione sociale`, DATE_FORMAT(MIN(`orario_inizio`), ''%d/%m/%Y'') AS `Data inizio`, DATE_FORMAT(MAX(`orario_fine`), ''%d/%m/%Y'') AS `Data fine`, `data_richiesta`, (SELECT `colore` FROM `in_statiintervento` WHERE `idstatointervento`=`in_interventi`.`idstatointervento`) AS `_bg_`, (SELECT `descrizione` FROM `in_statiintervento` WHERE `idstatointervento`=`in_interventi`.`idstatointervento`) AS `Stato`, (SELECT `descrizione` FROM `in_tipiintervento` WHERE `idtipointervento`=`in_interventi`.`idtipointervento`) AS `Tipo`, ''pdfgen.php?ptype=interventi&idintervento=$id$&mode=single'' AS `_print_`, `orario_inizio`, `orario_fine` FROM (`in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento`=`in_interventi`.`idintervento` GROUP BY `in_interventi`.`idintervento` HAVING 1=1 AND ((DATE_FORMAT(`orario_inizio`, ''%Y-%m-%d'') >= ''|period_start|'' AND DATE_FORMAT(`orario_fine`, ''%Y-%m-%d'') <= ''|period_end|'')  OR  (DATE_FORMAT(`data_richiesta`, ''%Y-%m-%d'') >= ''|period_start|'' AND DATE_FORMAT(`data_richiesta`, ''%Y-%m-%d'') <= ''|period_end|'')) ORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC"}	]}' WHERE `zz_modules`.`name` = 'Interventi';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Descrizione", "query": "SELECT `idtipoanagrafica` AS `id`, `descrizione` AS `Descrizione` FROM `an_tipianagrafiche` HAVING 1=1"}	]}' WHERE `name` = 'Tipi di anagrafiche';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, Costo orario, Costo al km, Diritto di chiamata, Costo orario tecnico, Costo al km tecnico, Diritto di chiamata tecnico",	"query": "SELECT `idtipointervento` AS `id`, `idtipointervento` AS `Codice`, `descrizione` AS `Descrizione`, REPLACE(FORMAT(`costo_orario`,2), ''.'', '','') AS `Costo orario`, REPLACE(FORMAT(`costo_km`,2), ''.'', '','') AS `Costo al km`, REPLACE(FORMAT(`costo_diritto_chiamata`,2), ''.'', '','') AS `Diritto di chiamata`, REPLACE(FORMAT(`costo_orario_tecnico`,2), ''.'', '','') AS `Costo orario tecnico`, REPLACE(FORMAT(`costo_km_tecnico`,2), ''.'', '','') AS `Costo al km tecnico`, REPLACE(FORMAT(`costo_diritto_chiamata_tecnico`,2), ''.'', '','') AS `Diritto di chiamata tecnico` FROM `in_tipiintervento` HAVING 1=1"}	]}' WHERE `name` = 'Tipi di intervento';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, color_Colore",	"query": "SELECT `idstatointervento` AS `Codice`, `idstatointervento` AS `id`, `descrizione` AS `Descrizione`, `colore` AS `color_Colore` FROM `in_statiintervento` HAVING 1=1"}	]}' WHERE `name` = 'Stati di intervento';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Nome, Cliente, icon_Stato",	"query": "SELECT `id`, `numero` AS `Numero`, `nome` AS `Nome`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_preventivi`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `co_statipreventivi` WHERE `id`=`idstato`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_statipreventivi` WHERE `id`=`idstato`) AS `icon_title_Stato`, data_bozza, data_conclusione FROM `co_preventivi` HAVING 1=1 AND (''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`) OR (''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`) OR (`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'') OR (`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'') OR (`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'') ORDER BY `id` DESC"}	]}' WHERE `name` = 'Preventivi';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Ragione sociale, Totale, icon_Stato",	"query": "SELECT `co_documenti`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_documenti`.`idanagrafica`) AS `Ragione sociale`, REPLACE(REPLACE(REPLACE(FORMAT((SELECT SUM(`subtotale`-`sconto`+`iva`+`rivalsainps`-`ritenutaacconto`) FROM `co_righe_documenti` GROUP BY `iddocumento` HAVING `iddocumento`=`co_documenti`.`id`) +`bollo` + `iva_rivalsainps`, 2), '','', ''#''), ''.'', '',''), ''#'', ''.'') AS `Totale`, (SELECT `icona` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_title_Stato` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` WHERE 1=1 AND `dir`=''entrata'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY DATE_FORMAT(`data`, ''%Y%m%d'') DESC, CAST(IFNULL(numero_esterno, numero) AS UNSIGNED) DESC"}	]}' WHERE `name`='Fatture di vendita';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Ragione sociale, Totale, icon_Stato",	"query": "SELECT `co_documenti`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_documenti`.`idanagrafica`) AS `Ragione sociale`, REPLACE(REPLACE(REPLACE(FORMAT((SELECT SUM(`subtotale`-`sconto`+`iva`+`rivalsainps`-`ritenutaacconto`) FROM `co_righe_documenti` GROUP BY `iddocumento` HAVING `iddocumento`=`co_documenti`.`id`) +`bollo` + `iva_rivalsainps`, 2), '','', ''#''), ''.'', '',''), ''#'', ''.'') AS `Totale`, (SELECT `icona` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_title_Stato`, `dir`, `data` AS `data1` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` HAVING 1=1 AND `dir`=''uscita'' AND `data1` >= ''|period_start|'' AND `data1` <= ''|period_end|'' ORDER BY DATE_FORMAT(`data1`, ''%Y%m%d'') DESC"}	]}' WHERE `name` = 'Fatture di acquisto';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, Categoria, Subcategoria, Q.tà",	"query": "SELECT `id`, `codice` AS `Codice`, `descrizione` AS `Descrizione`, `categoria` AS `Categoria`, `subcategoria` AS `Subcategoria`, CONCAT_WS('' '', REPLACE(FORMAT(`qta`, 2), ''.'', '',''), (SELECT `valore` FROM `mg_unitamisura` WHERE `id`=`idum`)) AS `Q.tà` FROM `mg_articoli` HAVING 1=1 ORDER BY `descrizione`"}	]}' WHERE `name` = 'Articoli';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, Categoria, Subcategoria, Q.tà",	"query": "SELECT `id`, `codice` AS `Codice`, `descrizione` AS `Descrizione`, `categoria` AS `Categoria`, `subcategoria` AS `Subcategoria`, CONCAT_WS('' '', REPLACE(FORMAT(`qta`, 2), ''.'', '',''), (SELECT `valore` FROM `mg_unitamisura` WHERE `id`=`idum`)) AS `Q.tà` FROM `mg_articoli` HAVING 1=1 ORDER BY `descrizione`"}]	}' WHERE `name` = 'Articoli';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Nome, Percentuale guadagno o sconto,Note", "query": "SELECT `id`, `nome` AS `Nome`, `prc_guadagno` AS `Percentuale guadagno o sconto`,`note` AS `Note`  FROM `mg_listini` HAVING 1=1 ORDER BY `nome`"}	]}' WHERE `name` = 'Listini';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Targa,Nome,Descrizione", "query": "SELECT `id`, `targa` AS `Targa`, `nome` AS `Nome`,`descrizione` AS `Descrizione`  FROM `dt_automezzi` HAVING 1=1 ORDER BY `targa`"}	]}' WHERE `name` = 'Automezzi';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Ragione sociale, icon_Stato",	"query": "SELECT `or_ordini`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`or_ordini`.`idanagrafica`) AS `Ragione sociale`, (SELECT `icona` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_Stato`, (SELECT `descrizione` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_title_Stato`, `dir`, `data` AS `data1` FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id` HAVING 1=1 AND `dir`=''entrata'' AND `data1` >= ''|period_start|'' AND `data1` <= ''|period_end|'' ORDER BY `data1` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC"}	]}' WHERE `name` = 'Ordini cliente';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Ragione sociale, icon_Stato",	"query": "SELECT `or_ordini`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`or_ordini`.`idanagrafica`) AS `Ragione sociale`, (SELECT `icona` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_Stato`, (SELECT `descrizione` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_title_Stato`, `dir`, `data` AS `data1` FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id` HAVING 1=1 AND `dir`=''uscita'' AND `data1` >= ''|period_start|'' AND `data1` <= ''|period_end|'' ORDER BY `data1` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC"}	]}' WHERE `name` = 'Ordini fornitore';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Cliente, icon_Stato",	"query": "SELECT `dt_ddt`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`dt_ddt`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_Stato`, (SELECT `descrizione` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_title_Stato`, `dir`, `data` AS `data1` FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id` HAVING 1=1 AND `dir`=''entrata'' AND `data1` >= ''|period_start|'' AND `data1` <= ''|period_end|'' ORDER BY DATE_FORMAT(`data1`, ''%Y%m%d'') DESC, CAST(`numero_esterno` AS UNSIGNED) DESC"}	]}' WHERE `name` = 'Ddt di vendita';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Cliente, icon_Stato",	"query": "SELECT `dt_ddt`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`dt_ddt`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_Stato`, (SELECT `descrizione` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_title_Stato`, `dir`, `data` AS `data1` FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id` HAVING 1=1 AND `dir`=''uscita'' AND `data1` >= ''|period_start|'' AND `data1` <= ''|period_end|'' ORDER BY DATE_FORMAT(`data1`, ''%Y%m%d'') DESC, CAST(`numero_esterno` AS UNSIGNED) DESC"}	]}' WHERE `name` = 'Ddt di acquisto';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Nome, Descrizione",	"query": "SELECT `id`, `nome` AS `Nome`, `descrizione` AS `Descrizione` FROM `an_zone` HAVING 1=1 ORDER BY `id`"}	]}' WHERE `name` = 'Zone';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Tipo intervento, Tecnico, Costo orario, Costo al km, Diritto di chiamata, Costo orario tecnico, Costo al km tecnico, Diritto di chiamata tecnico",	"query": "SELECT `id`, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=in_tariffe.idtipointervento) AS `Tipo intervento`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`idtecnico`) AS `Tecnico`, REPLACE(FORMAT(`costo_ore`,2), ''.'', '','') AS `Costo orario`, REPLACE(FORMAT(`costo_km`,2), ''.'', '','') AS `Costo al km`, REPLACE(FORMAT(`costo_dirittochiamata`,2), ''.'', '','') AS `Diritto di chiamata`, REPLACE(FORMAT(`costo_ore_tecnico`,2), ''.'', '','') AS `Costo orario tecnico`, REPLACE(FORMAT(`costo_km_tecnico`,2), ''.'', '','') AS `Costo al km tecnico`, REPLACE(FORMAT(`costo_dirittochiamata_tecnico`,2), ''.'', '','') AS `Diritto di chiamata tecnico`, ''Tecnico'' AS `descrizione`, `in_tariffe`.`idtecnico`, `idtipointervento` FROM `in_tariffe` HAVING 1=1 UNION SELECT CONCAT(`an_anagrafiche`.`idanagrafica`,''|'',`in_tipiintervento`.`idtipointervento`) AS `id`, `in_tipiintervento`.`descrizione` AS `Tipo intervento`, `ragione_sociale` AS `Tecnico`, ''0,00'' AS `Costo orario`, ''0,00'' AS `Costo al km`, ''0,00'' AS `Diritto di chiamata`, ''0,00'' AS `Costo orario tecnico`, ''0,00'' AS `Costo al km tecnico`, ''0,00'' AS `Diritto di chiamata tecnico`, `an_tipianagrafiche`.`descrizione`, `an_anagrafiche`.`idanagrafica`, `in_tipiintervento`.`idtipointervento` FROM ((`an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`idtipoanagrafica`) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `in_tipiintervento` ON 2=2) HAVING 1=1 AND `an_tipianagrafiche`.`descrizione`=''Tecnico'' AND CONCAT_WS(''-'', `an_anagrafiche`.`idanagrafica`, `in_tipiintervento`.`idtipointervento`) NOT IN(SELECT CONCAT_WS(''-'', `in_tariffe`.`idtecnico`, `in_tariffe`.`idtipointervento`) FROM `in_tariffe` WHERE `idtecnico`=`an_anagrafiche`.`idanagrafica`) ORDER BY `Tipo intervento`, `Tecnico`"}	]} ' WHERE `name` = 'Tecnici e tariffe';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Matricola, Nome, Cliente, Data, Tecnico", "query": "SELECT `matricola` AS `id`, `matricola` AS `Matricola`, `nome` AS `Nome`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`my_impianti`.`idanagrafica`) AS `Cliente`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`my_impianti`.`idtecnico`) AS `Tecnico` FROM `my_impianti` HAVING 1=1 ORDER BY `matricola`"}	]}' WHERE `name` = 'MyImpianti';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Numero, Nome, Cliente, icon_Stato",	"query": "SELECT `id`, `numero` AS `Numero`, `nome` AS `Nome`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_contratti`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `co_staticontratti` WHERE `id`=`idstato`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_staticontratti` WHERE `id`=`idstato`) AS `icon_title_Stato`, `data_bozza`, `data_conclusione` FROM `co_contratti` HAVING 1=1 AND ((''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`) OR (''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`) OR (`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'') OR (`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'') OR (`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')) ORDER BY `id` DESC"}	]}' WHERE `name` = 'Contratti';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Categoria, Descrizione",	"query": "SELECT `id`, `descrizione` AS `Descrizione`, `categoria` AS `Categoria` FROM `in_vociservizio` HAVING 1=1 ORDER BY `categoria`, `descrizione`"}	]}' WHERE `name` = 'Voci di servizio';
UPDATE `zz_modules` SET `options` = '{	"main_query": [	{	"type": "table", "fields": "Documento, Anagrafica, Tipo di pagamento, Data emissione, Data scadenza, Importo, Pagato", "query": "SELECT co_scadenziario.id AS id, ragione_sociale AS `Anagrafica`, co_pagamenti.descrizione AS `Tipo di pagamento`, CONCAT(co_tipidocumento.descrizione, CONCAT('' numero '', IF(numero_esterno<>'''', numero_esterno, numero))) AS `Documento`, DATE_FORMAT(data_emissione, ''%d/%m/%Y'') AS `Data emissione`, DATE_FORMAT(scadenza, ''%d/%m/%Y'') AS `Data scadenza`, REPLACE(REPLACE(REPLACE(FORMAT(da_pagare, 2), '','', ''#''), ''.'', '',''), ''#'', ''.'') AS `Importo`, REPLACE(REPLACE(REPLACE(FORMAT(pagato, 2), '','', ''#''), ''.'', '',''), ''#'', ''.'') AS `Pagato`, IF(scadenza<NOW(), ''#ff7777'', '''') AS _bg_, da_pagare, pagato, co_statidocumento.descrizione FROM (co_scadenziario INNER JOIN (((co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id HAVING 1=1 AND (ABS(pagato) < ABS(da_pagare) AND co_statidocumento.descrizione IN(''Emessa'',''Parzialmente pagato'')) ORDER BY scadenza ASC"}	]}' WHERE `zz_modules`.`name` = 'Scadenzario';
UPDATE `zz_impostazioni` SET `valore` = '100' WHERE `nome` = 'Righe per pagina';

--
-- Modifica menu fatture, ordini, ecc in Vendita, Acquisti, Contabilità
--
-- Aggiunta VENDITE
INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES (NULL, 'Vendite', '', '', '', '', 'fa fa-line-chart', '2.1', '2.*', '3', '0', '0', '1', '1', 'menu', '0');

-- Aggiunta ACQUISTI
INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES (NULL, 'Acquisti', '', '', '', '', 'fa fa-shopping-cart', '2.1', '2.*', '4', '0', '0', '1', '1', 'menu', '0');

-- Spostamento in giù dei moduli successivi
UPDATE `zz_modules` SET `order`=5 WHERE `name`='Contabilit&agrave;';
UPDATE `zz_modules` SET `order`=6 WHERE `name`='Magazzino';
UPDATE `zz_modules` SET `order`=7 WHERE `name`='MyImpianti';
UPDATE `zz_modules` SET `order`=8 WHERE `name`='Backup';
UPDATE `zz_modules` SET `order`=9 WHERE `name`='Aggiornamenti';

-- Collegamento sottomenu di Contabilità al giusto "contenitore" (vendite o acquisti)
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` IN('Preventivi', 'Contratti', 'Fatture di vendita', 'Ordini cliente') AND `t2`.`name` = 'Vendite') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` IN('Fatture di acquisto', 'Ordini fornitore') AND `t2`.`name` = 'Acquisti') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunta nuovi campi nelle righe preventivi
ALTER TABLE `co_righe_preventivi` ADD `sconto` DECIMAL(12, 4) NOT NULL AFTER `subtotale`;
ALTER TABLE `co_preventivi` ADD `idiva` INT(11) NOT NULL AFTER `idtipointervento`;

-- Creazione collegamento multiplo fra clienti e agenti
CREATE TABLE IF NOT EXISTS `an_anagrafiche_agenti` (
  `idanagrafica` int(11) NOT NULL,
  `idagente` int(11) NOT NULL,
  PRIMARY KEY(`idanagrafica`, `idagente`)
) ENGINE=InnoDB;

-- Aggiunta filtro su Prima nota per mostrare solo quelle dell'agente loggato
INSERT INTO `zz_gruppi_modules` (`idgruppo`, `idmodule`, `clause`) VALUES ((SELECT `id` FROM `zz_gruppi` WHERE `nome`='Agenti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Prima nota'), 'AND idagente=|idanagrafica|');

ALTER TABLE `co_documenti` ADD `idagente` INT(11) NOT NULL AFTER `idanagrafica`;

UPDATE `zz_widget_modules` SET `more_link` = 'if(confirm(''Stampare il riepilogo?'')){ window.open(''templates/pdfgen.php?ptype=riepilogo_interventi&id_module=$id_module$''); }' WHERE `zz_widget_modules`.`name` = 'Stampa riepilogo';

-- Aggiungo tabella log
CREATE TABLE IF NOT EXISTS `zz_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idutente` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `stato` varchar(50) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `timestamp` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Aggiunta tipologia di scadenza
ALTER TABLE `co_scadenziario` ADD `tipo` VARCHAR(50) NOT NULL AFTER `iddocumento`;
UPDATE `co_scadenziario` SET `tipo` = 'fattura';

-- Aggiunto campo bic per l'anagrafica
ALTER TABLE `an_anagrafiche` ADD `bic` VARCHAR(25) NOT NULL AFTER `codiceiban`;

-- Uniformo lunghezza varchar per idintervento in my_impianto_componenti - prima era varchar (20)
ALTER TABLE `my_impianto_componenti` CHANGE `idintervento` `idintervento` VARCHAR(25) NOT NULL;

-- Aggiunto campo ordine per poter ordinare le righe in fattura
ALTER TABLE `co_righe_documenti` ADD `ordine` INT(11) NOT NULL AFTER `altro`;

-- Aggiunto widget per vedere il valore del magazzino + il totale degli articoli disponibili
INSERT INTO `zz_widget_modules` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL, 'Valore magazzino', 'stats', '21', 'controller_right', 'col-md-12', 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT (SUM(prezzo_acquisto*qta),2), ",", "#"), ".", ","), "#", "."), "&euro;") AS dato FROM mg_articoli WHERE qta>0', '#A15D2D', 'fa fa-money', '', '', '', '', 'Valore magazzino', '1', '1');
INSERT INTO `zz_widget_modules` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL, 'Articoli in magazzino', 'stats', '21', 'controller_right', 'col-md-12', 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT (SUM(qta),2), ",", "#"), ".", ","), "#", "."), "unit&agrave;") AS dato FROM mg_articoli WHERE qta>0', '#45A9F1', 'fa fa-check-square-o', '', '', '', '', 'Articoli in magazzino', '1', '1');
-- Controllo scadenze per contratti con data conclusione > 1970
UPDATE `zz_widget_modules` SET `query` = 'SELECT COUNT(id) AS dato FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione="Accettato" OR descrizione="In lavorazione" OR descrizione="In attesa di pagamento") AND rinnovabile=1 AND NOW() > DATE_ADD(data_conclusione, INTERVAL -ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970' WHERE `zz_widget_modules`.`name` = 'Contratti in scadenza';

-- Aumento dimensione campo descrizione su co_pagamenti
ALTER TABLE `co_pagamenti` CHANGE `descrizione` `descrizione` VARCHAR(255) NOT NULL;

-- Aggiunta filtro su MyImpianti per mostrare solo quelli del cliente loggato
INSERT INTO `zz_gruppi_modules` (`idgruppo`, `idmodule`, `clause`) VALUES ((SELECT `id` FROM `zz_gruppi` WHERE `nome`='Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name`='MyImpianti'), 'AND my_impianti.idanagrafica=|idanagrafica|');

-- Aggiunto plugin che mostra elenco ddt di vendita per l'anagrafica
INSERT INTO `zz_modules_plugins` (`id`, `name`, `idmodule_from`, `idmodule_to`, `position`, `script`) VALUES (NULL, 'Ddt del cliente', (SELECT `id` FROM `zz_modules` WHERE `name`='Ddt di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Anagrafiche'), 'tab', 'ddt.anagrafiche.php');

-- Aggiunta nuovi campi nelle righe preventivi
ALTER TABLE `co_righe2_contratti` ADD `sconto` DECIMAL(12, 4) NOT NULL AFTER `subtotale`;
ALTER TABLE `co_righe2_contratti` ADD `idiva` INT(11) NOT NULL AFTER `sconto`;
ALTER TABLE `co_righe2_contratti` ADD `iva` DECIMAL(12, 4) NOT NULL AFTER `idiva`;
ALTER TABLE `co_righe2_contratti` ADD `iva_indetraibile` DECIMAL(12, 4) NOT NULL AFTER `iva`;

-- Aggiunto stato concluso anche ai contratti
INSERT INTO `co_staticontratti` (`id`, `descrizione`, `icona`, `completato`, `annullato`) VALUES (NULL, 'Concluso', 'fa fa-2x fa-check text-success', '0', '0');

-- Aggiunto modulo per gestire componenti
-- (SELECT `id` FROM `zz_modules` WHERE `name`='MyImpianti')
INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES (NULL, 'Gestione componenti', '', 'gestione_componenti', '{ "main_query": [ { "type": "custom" } ]}', '', 'fa fa-external-link', '2.2', '2.2', '0', '1', '30', '1', '1', 'menu', '0');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Gestione componenti' AND `t2`.`name` = 'MyImpianti') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunti campi per gestire firma rapportini
ALTER TABLE `in_interventi` ADD  `firma_file` varchar(255) NOT NULL AFTER `ora_sla`;
ALTER TABLE `in_interventi` ADD `firma_data` DATETIME NOT NULL AFTER `firma_file`;
ALTER TABLE `in_interventi` ADD `firma_nome` VARCHAR(255) NOT NULL AFTER `firma_data`;

-- Aggiunto campo data_invio per salvare data e ora invio email dei rapportini
ALTER TABLE `in_interventi` ADD `data_invio` DATETIME NULL AFTER `firma_nome`;

-- Aggiunta impostazione destinatario fisso in copia
INSERT INTO `zz_impostazioni` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Destinatario fisso in copia (campo CC)', '', 'string', 1, 'Email');

-- Aggiunta legame tra interventi e componenti
CREATE TABLE IF NOT EXISTS `my_componenti_interventi` (
  `id_intervento` varchar(25) NOT NULL,
  `id_componente` varchar(25) NOT NULL
) ENGINE=InnoDB;

-- Aggiunto campo prc_guadagno in co_righe_preventivi
ALTER TABLE `co_righe_preventivi` ADD `prc_guadagno` DECIMAL(5,2) NOT NULL AFTER `sconto`;

-- 2016-11-09 (r1509)
CREATE TABLE IF NOT EXISTS `co_contratti_tipiintervento` (
  `idcontratto` int(11) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `costo_ore` decimal(12,4) NOT NULL,
  `costo_km` decimal(12,4) NOT NULL,
  `costo_dirittochiamata` decimal(12,4) NOT NULL,
  `costo_ore_tecnico` decimal(12,4) NOT NULL,
  `costo_km_tecnico` decimal(12,4) NOT NULL,
  `costo_dirittochiamata_tecnico` decimal(12,4) NOT NULL,
  PRIMARY KEY (`idcontratto`,`idtipointervento`)
) ENGINE=InnoDB;
