-- Gestione documentale
CREATE TABLE IF NOT EXISTS `zz_documenti_categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zz_documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcategoria` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_documenti_categorie` (`id`, `descrizione`) VALUES
(NULL, 'Documenti società'),
(NULL, 'Contratti assunzione personale');

-- Innesto modulo gestione documentale
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Gestione documentale', 'Gestione documentale', 'gestione_documentale', '{	"main_query": [	{	"type": "table", "fields": "Categoria, Nome, Data", "query": "SELECT id,(SELECT descrizione FROM zz_documenti_categorie WHERE zz_documenti_categorie.id = idcategoria) AS Categoria, zz_documenti.nome AS Nome, DATE_FORMAT( zz_documenti.`data`, ''%d/%m/%Y'' ) AS `Data` FROM zz_documenti  WHERE  `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 1=1"}	]}', '', 'fa fa-file-text-o', '2.4', '2.4', '1', NULL, '1', '1');

-- Innesto modulo categorie documenti
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Categorie documenti', 'Categorie documenti', 'categorie_documenti', '{	"main_query": [	{	"type": "table", "fields": "Descrizione", "query": "SELECT zz_documenti_categorie.`descrizione`as Descrizione, zz_documenti_categorie.`id`as id FROM zz_documenti_categorie WHERE deleted_at IS NULL HAVING 1=1"}	]}', '', 'fa fa-file-text-o', '2.4', '2.4', '1', NULL, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Categorie documenti' AND `t2`.`name` = 'Gestione documentale') SET `t1`.`parent` = `t2`.`id`;

-- Fatturazione elettronica
ALTER TABLE `an_nazioni` ADD `name` VARCHAR(255);
ALTER TABLE `co_documenti` ADD `codice_xml` VARCHAR(255);

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Allega stampa per fattura verso Privati', '0', 'boolean', 1, 'Fatturazione Elettronica', 8),
(NULL, 'Allega stampa per fattura verso Aziende', '0', 'boolean', 1, 'Fatturazione Elettronica', 9),
(NULL, 'Allega stampa per fattura verso PA', '0', 'boolean', 1, 'Fatturazione Elettronica', 10);

CREATE TABLE IF NOT EXISTS `fe_regime_fiscale` (
  `codice` varchar(4) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_regime_fiscale` (`codice`, `descrizione`) VALUES
('RF01','Ordinario'),
('RF02','Contribuenti minimi (art.1, c.96-117, L. 244/07)'),
('RF04','Agricoltura e attività connesse e pesca (artt.34 e 34-bis, DPR 633/72)'),
('RF05','Vendita sali e tabacchi (art.74, c.1, DPR. 633/72)'),
('RF06','Commercio fiammiferi (art.74, c.1, DPR  633/72)'),
('RF07','Editoria (art.74, c.1, DPR  633/72)'),
('RF08','Gestione servizi telefonia pubblica (art.74, c.1, DPR 633/72)'),
('RF09','Rivendita documenti di trasporto pubblico e di sosta (art.74, c.1, DPR  633/72)'),
('RF10','Intrattenimenti, giochi e altre attività di cui alla tariffa allegata al DPR 640/72 (art.74, c.6, DPR 633/72)'),
('RF11','Agenzie viaggi e turismo (art.74-ter, DPR 633/72)'),
('RF12','Agriturismo (art.5, c.2, L. 413/91)'),
('RF13','Vendite a domicilio (art.25-bis, c.6, DPR  600/73)'),
('RF14','Rivendita beni usati, oggetti d’arte, d’antiquariato o da collezione (art.36, DL 41/95)'),
('RF15','Agenzie di vendite all’asta di oggetti d’arte, antiquariato o da collezione (art.40-bis, DL 41/95)'),
('RF16','IVA per cassa P.A. (art.6, c.5, DPR 633/72)'),
('RF17','IVA per cassa (art. 32-bis, DL 83/2012)'),
('RF18','Altro'),
('RF19','Regime forfettario (art.1, c.54-89, L. 190/2014)');

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Regime Fiscale', '', 'query=SELECT codice AS id, descrizione FROM fe_regime_fiscale', 1, 'Fatturazione Elettronica', 1);

CREATE TABLE IF NOT EXISTS `fe_tipo_cassa` (
  `codice` varchar(4) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_tipo_cassa` (`codice`, `descrizione`) VALUES
('TC01','Cassa nazionale previdenza e assistenza avvocati e procuratori legali'),
('TC02','Cassa previdenza dottori commercialisti'),
('TC03','Cassa previdenza e assistenza geometri'),
('TC04','Cassa nazionale previdenza e assistenza ingegneri e architetti liberi professionisti'),
('TC05','Cassa nazionale del notariato'),
('TC06','Cassa nazionale previdenza e assistenza ragionieri e periti commerciali'),
('TC07','Ente nazionale assistenza agenti e rappresentanti di commercio (ENASARCO)'),
('TC08','Ente nazionale previdenza e assistenza consulenti del lavoro (ENPACL)'),
('TC09','Ente nazionale previdenza e assistenza medici (ENPAM)'),
('TC10','Ente nazionale previdenza e assistenza farmacisti (ENPAF)'),
('TC11','Ente nazionale previdenza e assistenza veterinari (ENPAV)'),
('TC12','Ente nazionale previdenza e assistenza impiegati dell agricoltura (ENPAIA)'),
('TC13','Fondo previdenza impiegati imprese di spedizione e agenzie marittime'),
('TC14','Istituto nazionale previdenza giornalisti italiani (INPGI)'),
('TC15','Opera nazionale assistenza orfani sanitari italiani (ONAOSI)'),
('TC16','Cassa autonoma assistenza integrativa giornalisti italiani (CASAGIT)'),
('TC17','Ente previdenza periti industriali e periti industriali laureati (EPPI)'),
('TC18','Ente previdenza e assistenza pluricategoriale (EPAP)'),
('TC19','Ente nazionale previdenza e assistenza biologi (ENPAB)'),
('TC20','Ente nazionale previdenza e assistenza professione infermieristica (ENPAPI)'),
('TC21','Ente nazionale previdenza e assistenza psicologi (ENPAP)'),
('TC22','INPS');

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Tipo Cassa', '', 'query=SELECT codice AS id, descrizione FROM fe_tipo_cassa', 1, 'Fatturazione Elettronica', 2);

CREATE TABLE IF NOT EXISTS `fe_modalita_pagamento` (
  `codice` varchar(4) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_modalita_pagamento` (`codice`, `descrizione`) VALUES
('MP01','Contanti'),
('MP02','Assegno'),
('MP03','Assegno circolare'),
('MP04','Contanti presso Tesoreria'),
('MP05','Bonifico'),
('MP06','Vaglia cambiario'),
('MP07','Bollettino bancario'),
('MP08','Carta di pagamento'),
('MP09','RID'),
('MP10','RID utenze'),
('MP11','RID veloce'),
('MP12','RIBA'),
('MP13','MAV'),
('MP14','Quietanza erario'),
('MP15','Giroconto su conti di contabilità speciale'),
('MP16','Domiciliazione bancaria'),
('MP17','Domiciliazione postale'),
('MP18','Bollettino di c/c postale'),
('MP19','SEPA Direct Debit'),
('MP20','SEPA Direct Debit CORE'),
('MP21','SEPA Direct Debit B2B'),
('MP22','Trattenuta su somme già riscosse');

ALTER TABLE `co_pagamenti` ADD `codice_modalita_pagamento_fe` varchar(4);
UPDATE `co_pagamenti` SET `codice_modalita_pagamento_fe` = 'MP01' WHERE `descrizione` IN ('Rimessa diretta', 'Rimessa diretta a 30gg', 'Rimessa diretta 30gg fisso al 15', 'Contanti');
UPDATE `co_pagamenti` SET `codice_modalita_pagamento_fe` = 'MP02' WHERE `descrizione` IN ('Assegno');
UPDATE `co_pagamenti` SET `codice_modalita_pagamento_fe` = 'MP05' WHERE `descrizione` IN ('Bonifico 30gg d.f.', 'Bonifico 60gg d.f.', 'Bonifico 90gg d.f.', 'Bonifico 120gg d.f.', 'Bonifico 150gg d.f.', 'Bonifico 180gg d.f.', 'Bonifico 30/60gg d.f.', 'Bonifico 30/60gg d.f.', 'Bonifico 30/60/90gg d.f.', 'Bonifico 30/60/90gg d.f.', 'Bonifico 30/60/90gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30gg d.f.f.m.', 'Bonifico 60gg d.f.f.m.', 'Bonifico 90gg d.f.f.m.', 'Bonifico 120gg d.f.f.m.', 'Bonifico 150gg d.f.f.m.', 'Bonifico 180gg d.f.f.m.', 'Bonifico 30/60gg d.f.f.m.', 'Bonifico 30/60gg d.f.f.m.', 'Bonifico 30/60/90gg d.f.f.m.', 'Bonifico 30/60/90gg d.f.f.m.', 'Bonifico 30/60/90gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico bancario');
UPDATE `co_pagamenti` SET `codice_modalita_pagamento_fe` = 'MP06' WHERE `descrizione` IN ('Cambiale');
UPDATE `co_pagamenti` SET `codice_modalita_pagamento_fe` = 'MP08' WHERE `descrizione` IN ('Bancomat', 'Visa');
UPDATE `co_pagamenti` SET `codice_modalita_pagamento_fe` = 'MP12' WHERE `descrizione` IN ('Ri.Ba. 30gg d.f.', 'Ri.Ba. 60gg d.f.', 'Ri.Ba. 90gg d.f.', 'Ri.Ba. 120gg d.f.', 'Ri.Ba. 150gg d.f.', 'Ri.Ba. 180gg d.f.', 'Ri.Ba. 30/60gg d.f.', 'Ri.Ba. 30/60gg d.f.', 'Ri.Ba. 30/60/90gg d.f.', 'Ri.Ba. 30/60/90gg d.f.', 'Ri.Ba. 30/60/90gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30gg d.f.f.m.', 'Ri.Ba. 60gg d.f.f.m.', 'Ri.Ba. 90gg d.f.f.m.', 'Ri.Ba. 120gg d.f.f.m.', 'Ri.Ba. 150gg d.f.f.m.', 'Ri.Ba. 180gg d.f.f.m.', 'Ri.Ba. 30/60gg d.f.f.m.', 'Ri.Ba. 30/60gg d.f.f.m.', 'Ri.Ba. 30/60/90gg d.f.f.m.', 'Ri.Ba. 30/60/90gg d.f.f.m.', 'Ri.Ba. 30/60/90gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.');
ALTER TABLE `co_pagamenti` ADD FOREIGN KEY (`codice_modalita_pagamento_fe`) REFERENCES `fe_modalita_pagamento`(`codice`) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS `fe_tipi_documento` (
  `codice` varchar(4) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_tipi_documento` (`codice`, `descrizione`) VALUES
('TD01','Fattura'),
('TD02','Acconto/anticipo su fattura'),
('TD03','Acconto/anticipo su parcella'),
('TD04','Nota di credito'),
('TD05','Nota di debito'),
('TD06','Parcella');

ALTER TABLE `co_tipidocumento` ADD `codice_tipo_documento_fe` varchar(4) NOT NULL;
UPDATE `co_tipidocumento` SET `codice_tipo_documento_fe` = 'TD01' WHERE `descrizione` IN ('Fattura immediata di acquisto', 'Fattura immediata di vendita', 'Fattura differita di acquisto', 'Fattura differita di vendita', 'Fattura accompagnatoria di acquisto', 'Fattura accompagnatoria di vendita');
UPDATE `co_tipidocumento` SET `codice_tipo_documento_fe` = 'TD04', `descrizione` = 'Nota di credito' WHERE `descrizione` = 'Nota di accredito';
UPDATE `co_tipidocumento` SET `codice_tipo_documento_fe` = 'TD05', `descrizione` = 'Nota di debito' WHERE `descrizione` = 'Nota di addebito';
ALTER TABLE `co_tipidocumento` ADD FOREIGN KEY (`codice_tipo_documento_fe`) REFERENCES `fe_tipi_documento`(`codice`) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS `fe_natura` (
  `codice` varchar(2) NOT NULL,
  `descrizione` text NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_natura` (`codice`, `descrizione`) VALUES
('N1','Escluse ex art. 15'),
('N2','Non soggette'),
('N3','Non imponibili'),
('N4','Esenti'),
('N5','Regime del margine / IVA non esposta in fattura'),
('N6','Inversione contabile (per le operazioni in reverse charge ovvero nei casi di autofatturazione per acquisti extra UE di servizi ovvero per importazioni di beni nei soli casi previsti)'),
('N7','IVA assolta in altro stato UE (vendite a distanza ex art. 40 c. 3 e 4 e art. 41 c. 1 lett. b, DL 331/93; prestazione di servizi di telecomunicazioni, tele-radiodiffusione ed elettronici ex art. 7-sexies lett. f, g, art. 74-sexies DPR 633/72)');

ALTER TABLE `co_iva` ADD `codice_natura_fe` varchar(4), ADD `deleted_at` timestamp NULL DEFAULT NULL, ADD `codice` int(11), ADD `esigibilita` enum('I', 'D', 'S') NOT NULL DEFAULT 'I', ADD `default` boolean NOT NULL DEFAULT 0, ADD FOREIGN KEY (`codice_natura_fe`) REFERENCES `fe_natura`(`codice`) ON DELETE CASCADE;
UPDATE `co_iva` SET `deleted_at` = NOW();

INSERT INTO `co_iva` (`descrizione`, `percentuale`, `indetraibile`, `esente`, `codice_natura_fe`, `codice`, `default`) VALUES
("Fuori campo IVA", 0, 0, 1, "N2", 300, 1),
("Es.art27DL98/11", 0, 0, 1, "N2", 301, 1),
("Escluso art. 2", 0, 0, 1, "N2", 302, 1),
("Escluso art. 3", 0, 0, 1, "N2", 303, 1),
("Escluso art. 4", 0, 0, 1, "N2", 304, 1),
("Escluso art. 5", 0, 0, 1, "N2", 305, 1),
("Esc.art7tr noUE", 0, 0, 1, "N2", 306, 1),
("Escl.art7ter UE", 0, 0, 1, "N6", 307, 1),
("Es. art.10 n.18", 0, 0, 1, "N4", 308, 1),
("Es.art.10 n.1/9", 0, 0, 1, "N4", 309, 1),
("Esente art. 10", 0, 0, 1, "N4", 310, 1),
("Art8,1/a triang", 0, 0, 1, "N3", 311, 1),
("N.I.art.8,2 ITA", 0, 0, 1, "N3", 312, 1),
("N.I.art.8,2 UE", 0, 0, 1, "N3", 313, 1),
("N.I. art. 9 c.1", 0, 0, 1, "N3", 314, 1),
("Escluso art. 15", 0, 0, 1, "N1", 315, 1),
("Art.17,6 let.a-", 0, 0, 1, "N6", 316, 1),
("N.I. art.74 ter", 0, 0, 1, "N5", 317, 1),
("N.I.art.14 L.49", 0, 0, 1, "N3", 318, 1),
("Es.art.10 n.27q", 0, 0, 1, "N4", 319, 1),
("N.I.a.8,2 no-UE", 0, 0, 1, "N3", 320, 1),
("Es. art.10 n.11", 0, 0, 1, "N4", 321, 1),
("N.I. art. 8 bis", 0, 0, 1, "N3", 322, 1),
("N.I. art.8,1 b", 0, 0, 1, "N3", 323, 1),
("N.I. art.8,1 c", 0, 0, 1, "N3", 324, 1),
("N.I. art.8,1 a", 0, 0, 1, "N3", 325, 1),
("N.V.escl.art.26", 0, 0, 1, "N2", 326, 1),
("N.I. altri acq.", 0, 0, 1, "N3", 327, 1),
("Op. non sog.ter", 0, 0, 1, "N2", 328, 1),
("N.I. art. 9 c.2", 0, 0, 1, "N3", 329, 1),
("Esc.art7quatrUE", 0, 0, 1, "N6", 330, 1),
("Esc.art7qtrNOUE", 0, 0, 1, "N2", 331, 1),
("Esc.art7quinqUE", 0, 0, 1, "N6", 332, 1),
("Esc.art7qnqNOUE", 0, 0, 1, "N2", 333, 1),
("Art.36-bis", 0, 0, 1, "N2", 334, 1),
("Art.17 comma 3", 0, 0, 1, "N2", 335, 1),
("DL41/95 art.36", 0, 0, 1, "N5", 336, 1),
("Es.art.19c3abis", 0, 0, 1, "N4", 337, 1),
("N.I. art.38 q.", 0, 0, 1, "N3", 338, 1),
("Escl.art.7nodet", 0, 0, 1, "N2", 339, 1),
("Esc.art.7spt/sx", 0, 0, 1, "N2", 340, 1),
("DL331/93 art.41", 0, 0, 1, "N3", 341, 1),
("DL331art42,40c2", 0, 0, 1, "N3", 342, 1),
("N.I.art.8,1 b2", 0, 0, 1, "N3", 343, 1),
("Escl.art.7bisUE", 0, 0, 1, "N6", 344, 1),
("DL331 a.50b,4-g", 0, 0, 1, "N3", 350, 1),
("DL331 a.50b,4-f", 0, 0, 1, "N3", 351, 1),
("Cess. dep. IVA", 0, 0, 1, "N2", 352, 1),
("N.I.Acq.dep.IVA", 0, 0, 1, "N2", 353, 1),
("Es.art1 L190/14", 0, 0, 1, "N2", 354, 1),
("Ces.gratuiteExp", 0, 0, 1, "N3", 355, 1),
("DL331/93 a.58,1", 0, 0, 1, "N3", 358, 1),
("Esc.legge67/88", 0, 0, 1, "N2", 367, 1),
("Imp.n.s. art.68", 0, 0, 1, "N3", 368, 1),
("Art.74 ter c. 8", 0, 0, 1, "N6", 369, 1),
("Escl.art.7bis", 0, 0, 1, "N2", 370, 1),
("N.I.art.71 V-SM", 0, 0, 1, "N3", 371, 1),
("N.I. art. 72", 0, 0, 1, "N3", 372, 1),
("N.I.art.74c.1-2", 0, 0, 1, "N2", 374, 1),
("Art. 74 c. 7-8", 0, 0, 1, "N6", 375, 1),
("Art. 17 c. 5", 0, 0, 1, "N6", 376, 1),
("Art.17,6 lett.a", 0, 0, 1, "N6", 377, 1),
("Art.74 ter c. 8", 0, 0, 1, "N3", 378, 1),
("Art.17,6 lett.b", 0, 0, 1, "N6", 379, 1),
("Art.17,6 lett.c", 0, 0, 1, "N6", 380, 1),
("Art.17,6 let.a3", 0, 0, 1, "N6", 381, 1),
("Art.17,6,lett.d", 0, 0, 1, "N6", 382, 1),
("Aliq. Iva 2%", 2, 0, 0, NULL, 2, 1),
("Aliq. Iva 4%", 4, 0, 0, NULL, 4, 1),
("Aliq. Iva 5%", 5, 0, 0, NULL, 5, 1),
("Aliq. Iva 7%", 7, 0, 0, NULL, 7, 1),
("Aliq. Iva 8%", 8, 0, 0, NULL, 8, 1),
("Aliq. Iva 10%", 10, 0, 0, NULL, 10, 1),
("Aliq. Iva 12,3%", 12.3, 0, 0, NULL, 13, 1),
("Aliq. Iva 20%", 20, 0, 0, NULL, 20, 1),
("Aliq. Iva 21%", 21, 0, 0, NULL, 21, 1),
("Aliq. Iva 22%", 22, 0, 0, NULL, 22, 1),
("Aliq. Iva 7,3%", 7.3, 0, 0, NULL, 73, 1),
("Aliq. Iva 7,5%", 7.5, 0, 0, NULL, 75, 1),
("Aliq. Iva 7,65%", 7.65, 0, 0, NULL, 76, 1),
("Aliq. Iva 7,7%", 7.7, 0, 0, NULL, 77, 1),
("Aliq. Iva 7,95%", 7.95, 0, 0, NULL, 79, 1),
("Aliq. Iva 8,3%", 8.3, 0, 0, NULL, 83, 1),
("Aliq. Iva 8,5%", 8.5, 0, 0, NULL, 85, 1),
("Aliq. Iva 8,8%", 8.8, 0, 0, NULL, 88, 1),
("Scorporo 2%", 2, 0, 0, NULL, 102, 1),
("Scorporo 4%", 4, 0, 0, NULL, 104, 1),
("Scorporo 5%", 5, 0, 0, NULL, 105, 1),
("Scorporo 7%", 7, 0, 0, NULL, 107, 1),
("Scorporo 8%", 8, 0, 0, NULL, 108, 1),
("Scorporo 10%", 10, 0, 0, NULL, 110, 1),
("Scorporo 12,3%", 12.3, 0, 0, NULL, 113, 1),
("Scorporo 20%", 20, 0, 0, NULL, 120, 1),
("Scorporo 21%", 21, 0, 0, NULL, 121, 1),
("Scorporo 22%", 22, 0, 0, NULL, 122, 1),
("Scorporo 7,3%", 7.3, 0, 0, NULL, 173, 1),
("Scorporo 7,5%", 7.5, 0, 0, NULL, 175, 1),
("Scorporo 7,65%", 7.65, 0, 0, NULL, 176, 1),
("Scorporo 7,7%", 7.7, 0, 0, NULL, 177, 1),
("Scorporo 7,95%", 7.95, 0, 0, NULL, 179, 1),
("Scorporo 8,3%", 8.3, 0, 0, NULL, 183, 1),
("Scorporo 8,5%", 8.5, 0, 0, NULL, 185, 1),
("Scorporo 8,8%", 8.8, 0, 0, NULL, 188, 1),
("Corr. Ventilati", 0, 0, 0, NULL, 200, 1),
("Iva Vent. 2%", 2, 0, 0, NULL, 202, 1),
("Iva Vent. 4%", 4, 0, 0, NULL, 204, 1),
("Iva Vent. 5%", 5, 0, 0, NULL, 205, 1),
("Iva Vent. 7%", 7, 0, 0, NULL, 207, 1),
("Iva Vent. 8%", 8, 0, 0, NULL, 208, 1),
("Iva Vent. 10%", 10, 0, 0, NULL, 210, 1),
("Iva Vent. 12,3%", 12.3, 0, 0, NULL, 213, 1),
("Iva Vent. 20%", 20, 0, 0, NULL, 220, 1),
("Iva Vent. 21%", 21, 0, 0, NULL, 221, 1),
("Iva Vent. 22%", 22, 0, 0, NULL, 222, 1),
("Iva Vent. 7,3%", 7.3, 0, 0, NULL, 273, 1),
("Iva Vent. 7,5%", 7.5, 0, 0, NULL, 275, 1),
("Iva Vent. 7,65%", 7.65, 0, 0, NULL, 276, 1),
("Iva Vent. 7,7%", 7.7, 0, 0, NULL, 277, 1),
("Iva Vent. 7,95%", 7.95, 0, 0, NULL, 279, 1),
("Iva Vent. 8,3%", 8.3, 0, 0, NULL, 283, 1),
("Iva Vent. 8,5%", 8.5, 0, 0, NULL, 285, 1),
("Iva Vent. 8,8%", 8.8, 0, 0, NULL, 288, 1),
("Iva Tot. Indetr 2%", 2, 100, 0, NULL, 602, 1),
("Iva Tot. Indetr 4%", 4, 100, 0, NULL, 604, 1),
("Iva Tot. Indetr 5%", 5, 100, 0, NULL, 605, 1),
("Iva Tot. Indetr 7%", 7, 100, 0, NULL, 607, 1),
("Iva Tot. Indetr 8%", 8, 100, 0, NULL, 608, 1),
("Iva Tot. Indetr 10%", 10, 100, 0, NULL, 610, 1),
("Iva Tot. Indetr 12,3%", 12.3, 100, 0, NULL, 613, 1),
("Iva Tot. Indetr 20%", 20, 100, 0, NULL, 620, 1),
("Iva Tot. Indetr 21%", 21, 100, 0, NULL, 621, 1),
("Iva Tot. Indetr 22%", 22, 100, 0, NULL, 622, 1),
("Iva Tot. Indetr 7,3%", 7.3, 100, 0, NULL, 673, 1),
("Iva Tot. Indetr 7,5%", 7.5, 100, 0, NULL, 675, 1),
("Iva Tot. Indetr 7,65%", 7.65, 100, 0, NULL, 676, 1),
("Iva Tot. Indetr 7,7%", 7.7, 100, 0, NULL, 677, 1),
("Iva Tot. Indetr 7,95%", 7.95, 100, 0, NULL, 679, 1),
("Iva Tot. Indetr 8,3%", 8.3, 100, 0, NULL, 683, 1),
("Iva Tot. Indetr 8,5%", 8.5, 100, 0, NULL, 685, 1),
("Iva Tot. Indetr 8,8%", 8.8, 100, 0, NULL, 688, 1),
("Iva Agric. 2%", 2, 0, 0, NULL, 802, 1),
("Iva Agric. 4%", 4, 0, 0, NULL, 804, 1),
("Iva Agric. 5%", 5, 0, 0, NULL, 805, 1),
("Iva Agric. 7%", 7, 0, 0, NULL, 807, 1),
("Iva Agric. 8%", 8, 0, 0, NULL, 808, 1),
("Iva Agric. 10%", 10, 0, 0, NULL, 810, 1),
("Iva Agric. 12,3", 12.3, 0, 0, NULL, 813, 1),
("Iva Agric. 20%", 20, 0, 0, NULL, 820, 1),
("Iva Agric. 21%", 21, 0, 0, NULL, 821, 1),
("Iva Agric. 22%", 22, 0, 0, NULL, 822, 1),
("Iva Agric. 7,3%", 7.3, 0, 0, NULL, 873, 1),
("Iva Agric. 7,5%", 7.5, 0, 0, NULL, 875, 1),
("Iva Agric. 7,65", 7.65, 0, 0, NULL, 876, 1),
("Iva Agric. 7,7%", 7.7, 0, 0, NULL, 877, 1),
("Iva Agric. 7,95", 7.95, 0, 0, NULL, 879, 1),
("Iva Agric. 8,3%", 8.3, 0, 0, NULL, 883, 1),
("Iva Agric. 8,5%", 8.5, 0, 0, NULL, 885, 1),
("Iva Agric. 8,8%", 8.8, 0, 0, NULL, 888, 1);

CREATE TABLE IF NOT EXISTS `fe_causali_pagamento_ritenuta` (
  `codice` varchar(4) NOT NULL,
  `descrizione` varchar(1000) NOT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES
('A', 'Prestazioni di lavoro autonomo rientranti nell''esercizio di arte o professione abituale'),
('B', 'Utilizzazione economica, da parte dell''autore o dell''inventore, di opere dell''ingegno, di brevetti industriali e di processi, formule o informazioni relativi a esperienze acquisite in campo industriale, commerciale o scientifico'),
('C', 'Utili derivanti da contratti di associazione in partecipazione e da contratti di cointeressenza, quando l''apporto è costituito esclusivamente dalla prestazione di lavoro'),
('D', 'Utili spettanti ai soci promotori e ai soci fondatori delle società di capitali'),
('E', 'Levata di protesti cambiari da parte dei segretari comunali'),
('G', 'Indennità corrisposte per la cessazione di attività sportiva professionale'),
('H', 'Indennità corrisposte per la cessazione dei rapporti di agenzia delle persone fisiche e delle società di persone, con esclusione delle somme maturate entro il 31.12.2003, già imputate per competenza e tassate come reddito d''impresa'),
('I', 'Indennità corrisposte per la cessazione da funzioni notarili'),
('L', 'Utilizzazione economica, da parte di soggetto diverso dall''autore o dall''inventore, di opere dell''ingegno, di brevetti industriali e di processi, formule e informazioni relative a esperienze acquisite in campo industriale, commerciale o scientifico'),
('M', 'Prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere'),
('N', 'Indennità di trasferta, rimborso forfetario di spese, premi e compensi erogati nell''esercizio diretto di attività sportive dilettantistiche e in relazione a rapporti di collaborazione coordinata e continuativa di carattere amministrativo-gestionale, di natura non profe
ssionale, resi a favore di società e associazioni sportive dilettantistiche e di cori, bande e filodrammatiche da parte del diretto
re e dei collaboratori tecnici'),
('O', 'Prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere, per le quali non sussiste l''obbligo di iscrizione alla gestione separata (Circ. Inps 104/2001)'),
('P', 'Compensi corrisposti a soggetti non residenti privi di stabile organizzazione per l''uso o la concessione in uso di attrezzature industriali, commerciali o scientifiche che si trovano nel territorio dello Stato ovvero a società svizzere o stabili organizzazioni di soci
età svizzere che possiedono i requisiti di cui all''art. 15, c. 2 dell’Accordo tra la Comunità Europea e la Confederazione svizzera del 26.10
.2004 (pubblicato in G.U.C.E. 29.12.2004, n. 385/30)'),
('Q', 'Provvigioni corrisposte ad agente o rappresentante di commercio monomandatario'),
('R', 'Provvigioni corrisposte ad agente o rappresentante di commercio plurimandatario'),
('S', 'Provvigioni corrisposte a commissionario'),
('T', 'Provvigioni corrisposte a mediatore'),
('U', 'Provvigioni corrisposte a procacciatore di affari'),
('V', 'Provvigioni corrisposte a incaricato per le vendite a domicilio e provvigioni corrisposte a incaricato per la vendita porta a porta e per la vendita ambulante di giornali quotidiani e periodici (L. 25.02.1987, n. 67)'),
('W', 'Corrispettivi erogati nel 2013 per prestazioni relative a contratti d''appalto cui si sono resi applicabili le disposizioni contenute nell''art. 25-ter D.P.R. 600/1973'),
('X', 'Canoni corrisposti nel 2004 da società o enti residenti, ovvero da stabili organizzazioni di società estere di cui all''art. 26-quater, c. 1, lett. a) e b) D.P.R. 600/1973, a società o stabili organizzazioni di società, situate in altro Stato membro dell''Unione Europea in presenza dei relativi requisiti richiesti, per i quali è stato effettuato nel 2006 il rimborso della ritenuta ai sensi dell''art. 4 D. Lgs. 143/2005'),
('Y', 'Canoni corrisposti dal 1.01.2005 al 26.07.2005 da soggetti di cui al punto precedente'),
('Z', 'Titolo diverso dai precedenti');

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Causale ritenuta d''acconto', '', 'query=SELECT codice AS id, descrizione FROM fe_causali_pagamento_ritenuta', 1, 'Fatturazione Elettronica', 3);

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Authorization ID Indice PA', '', 'string', 1, 'Fatturazione Elettronica', 4);

ALTER TABLE `an_anagrafiche` ADD `codice_destinatario` varchar(7);

-- Plugin Fatturazione Elettronica
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Fatturazione Elettronica', 'Fatturazione Elettronica', (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), 'tab', 'exportPA', 'custom'),
(NULL, 'Fatturazione Elettronica', 'Fatturazione Elettronica', (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di acquisto'), 'tab_main', 'importPA', 'custom');

UPDATE `zz_emails` SET `main` = 1 WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');

-- Aggiornamento zz_settings
ALTER TABLE `zz_settings` CHANGE `idimpostazione` `id` int(11) NOT NULL AUTO_INCREMENT;
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idimpostazione', 'id');

-- Aggiunta conti in Articoli
ALTER TABLE `mg_articoli` ADD `idconto_vendita` int(11), ADD `idconto_acquisto` int(11);

-- Aggiunta log per invio email
CREATE TABLE IF NOT EXISTS `zz_operations` (
  `id_module` int(11),
  `id_plugin` int(11),
  `id_email` int(11),
  `id_record` int(11),
  `id_utente` int(11) NOT NULL,
  `op` varchar(255) NOT NULL,
  `options` text,
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_plugin`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_email`) REFERENCES `zz_emails`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE `zz_smtp` RENAME `zz_smtps`;

-- Aggiorno tabella zz_smtp in zz_smtps per il modulo Account email
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM zz_smtps WHERE 1=1 AND deleted_at IS NULL HAVING 2=2 ORDER BY `name`' WHERE `zz_modules`.`name` = 'Account email';

-- Ridenominazione enabled in visible su zz_views
ALTER TABLE `zz_views` CHANGE `enabled` `visible` BOOLEAN NOT NULL DEFAULT 1;

-- Rimozione permessi negati (comportamento di default)
DELETE FROM `zz_permissions` WHERE `permessi` = '-';

-- Ridenominazione plugin "Pianificazione interventi"
UPDATE `zz_plugins` SET `title` = 'Pianificazione attività' WHERE `name` = 'Pianificazione interventi';

-- Fix plugin "Pianificazione interventi"
UPDATE `zz_plugins` SET `options` = 'custom', `script` = '', `directory` = 'pianificazione_interventi' WHERE `name` = 'Pianificazione interventi';

-- Ridenominazione tabelle per i promemoria
ALTER TABLE `co_contratti_promemoria` RENAME `co_promemoria`;
ALTER TABLE `co_righe_contratti_materiali` RENAME `co_promemoria_righe`;
ALTER TABLE `co_righe_contratti_articoli` RENAME `co_promemoria_articoli`;
ALTER TABLE `co_promemoria_righe` CHANGE `id_riga_contratto` `id_promemoria` int(11) NOT NULL;
ALTER TABLE `co_promemoria_articoli` CHANGE `id_riga_contratto` `id_promemoria` int(11) NOT NULL;

-- Fix nome in zz_files
ALTER TABLE `zz_files` CHANGE `nome` `name` varchar(255) NOT NULL;
UPDATE `zz_files` SET `id_module` = NULL WHERE `id_plugin` IS NOT NULL;

-- Adeguamento variabili di filtraggio
UPDATE `zz_plugins` SET `options` = REPLACE(`options`, '|idanagrafica|', '|id_anagrafica|'), `options2` = REPLACE(`options2`, '|idanagrafica|', '|id_anagrafica|');
UPDATE `zz_group_module` SET `clause` = REPLACE(`clause`, '|idanagrafica|', '|id_anagrafica|');

UPDATE `zz_plugins` SET `options` = REPLACE(`options`, '|idtecnico|', '|id_anagrafica|'), `options2` = REPLACE(`options2`, '|idtecnico|', '|id_anagrafica|');
UPDATE `zz_group_module` SET `clause` = REPLACE(`clause`, '|idtecnico|', '|id_anagrafica|');

UPDATE `zz_plugins` SET `options` = REPLACE(`options`, '|idagente|', '|id_anagrafica|'), `options2` = REPLACE(`options2`, '|idagente|', '|id_anagrafica|');
UPDATE `zz_group_module` SET `clause` = REPLACE(`clause`, '|idagente|', '|id_anagrafica|');

-- Adeguamento variabili di filtraggio per i plugin Sedi e Referenti in Anagrafiche
UPDATE `zz_plugins` SET `script` = '', `options` = '	{ "main_query": [	{	"type": "table", "fields": "Nome, Indirizzo, Città, CAP, Provincia, Referente", "query": "SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia, an_referenti.nome AS Referente FROM an_sedi LEFT OUTER JOIN an_referenti ON idsede = an_sedi.id WHERE 1=1 AND an_sedi.idanagrafica=|id_parent| HAVING 2=2 ORDER BY an_sedi.id DESC"}	]}', `directory` = 'sedi', `version` = '2.3', `compatibility` = '2.*' WHERE `name` = 'Sedi';
UPDATE `zz_plugins` SET `script` = '', `options` = '	{ "main_query": [	{	"type": "table", "fields": "Nominativo, Mansione, Telefono, Indirizzo email, Sede",	"query": "SELECT an_referenti.id, an_referenti.nome AS Nominativo, mansione AS Mansione, an_referenti.telefono AS Telefono, an_referenti.email AS ''Indirizzo email'', IF(idsede = 0, ''Sede legale'', an_sedi.nomesede) AS Sede FROM an_referenti LEFT OUTER JOIN an_sedi ON idsede = an_sedi.id WHERE 1=1 AND an_referenti.idanagrafica=|id_parent| HAVING 2=2 ORDER BY an_referenti.id DESC"}	]}', `directory` = 'referenti', `version` = '2.3', `compatibility` = '2.*' WHERE `name` = 'Referenti';

UPDATE `an_referenti` SET `idsede` = 0 WHERE `idsede` = -1;

-- Rimozione co_preventivi_interventi
ALTER TABLE `in_interventi` ADD `id_preventivo` int(11), ADD FOREIGN KEY (`id_preventivo`) REFERENCES `co_preventivi`(`id`) ON DELETE CASCADE, ADD `id_contratto` int(11), ADD FOREIGN KEY (`id_contratto`) REFERENCES `co_contratti`(`id`) ON DELETE CASCADE;
UPDATE `in_interventi` SET `id_preventivo` = (SELECT `idpreventivo` FROM `co_preventivi_interventi` WHERE `co_preventivi_interventi`.`idintervento` = `in_interventi`.`id` LIMIT 1);
DROP TABLE `co_preventivi_interventi`;

-- Aggiunto input CKEditor automatico
UPDATE `zz_settings` SET `tipo` = 'ckeditor' WHERE `nome` = 'Dicitura fissa fattura';

-- Miglioramento dell'impostazione "Orario lavorativo"
UPDATE `zz_settings` SET `sezione` = 'Dashboard' WHERE `nome` IN ('Vista dashboard', 'Visualizzare la domenica sul calendario', 'Utilizzare i tooltip sul calendario');
DELETE FROM `zz_settings` WHERE `nome` = 'Abilitare orario lavorativo';
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Inizio orario lavorativo', '00:00:00', 'time', 1, 'Dashboard', 1),
(NULL, 'Fine orario lavorativo', '23:59:00', 'time', 1, 'Dashboard', 2);

-- Notifiche negli stati interventi
ALTER TABLE `in_statiintervento` ADD `notifica` boolean NOT NULL DEFAULT 0, ADD `id_email` int(11), ADD `destinatari` varchar(255);
ALTER TABLE `in_statiintervento` ADD FOREIGN KEY (`id_email`) REFERENCES `zz_emails`(`id`) ON DELETE CASCADE;

-- Email di notifica
INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `main`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 1, 'Notifica intervento', 'fa fa-envelope', 'Notifica intervento numero {numero} del {data}', '', '', '', '<p>Gentile Tecnico,</p>\r\n<p>un nuovo intervento {numero} in {data} è stato aggiunto.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 1, 'Notifica rimozione intervento', 'fa fa-envelope', 'Notifica intervento numero {numero} del {data}', '', '', '', '<p>Gentile Tecnico,</p>\r\n<p>sei stato rimosso dall''intervento {numero} in {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 1, 'Stato intervento', 'fa fa-envelope', 'Intervento numero {numero} del {data}: {stato}.', '', '', '', '<p>Gentile Utente,</p>\r\n<p>l''intervento {numero} in {data} è stato spostato nello stato {stato}.</p>', '0', '0');

INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES
(NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Stato intervento' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Intervento'));

UPDATE `zz_emails` SET `main` = 1 WHERE `name` = 'Rapportino intervento' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');
UPDATE `in_statiintervento` SET `id_email` = (SELECT `id` FROM `zz_emails` WHERE `name` = 'Stato intervento' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'));

-- Ritenuta d'acconto predefinita per anagrafica
ALTER TABLE `an_anagrafiche` ADD `id_ritenuta_acconto_vendite` INT(11) NULL DEFAULT NULL AFTER `idiva_acquisti`;
ALTER TABLE `an_anagrafiche` ADD `id_ritenuta_acconto_acquisti` INT(11) NULL DEFAULT NULL AFTER `id_ritenuta_acconto_vendite`;

-- Correzione partite ive e codici fiscali
UPDATE `an_anagrafiche` SET `piva` = REPLACE(`piva`, ' ', ''), `codice_fiscale` = REPLACE(`codice_fiscale`, ' ', '');

-- Aggiunta impostazione
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Stampa per anteprima e firma', (SELECT id FROM zz_prints WHERE main = 1 AND id_module = (SELECT id FROM zz_modules WHERE name = 'Interventi')), 'query=SELECT id, title AS descrizione FROM zz_prints WHERE id_module = (SELECT id FROM zz_modules WHERE name = ''Interventi'') AND is_record = 1', 1, 'Interventi', 3);

-- Fix nomi campi predefined
ALTER TABLE `zz_smtps` CHANGE `main` `predefined` boolean NOT NULL DEFAULT 0;
ALTER TABLE `zz_prints` CHANGE `main` `predefined` boolean NOT NULL DEFAULT 0;
ALTER TABLE `zz_emails` CHANGE `main` `predefined` boolean NOT NULL DEFAULT 0;
ALTER TABLE `dt_porto` ADD `predefined` boolean NOT NULL DEFAULT 0;
ALTER TABLE `dt_causalet` ADD `predefined` boolean NOT NULL DEFAULT 0;
ALTER TABLE `dt_spedizione` ADD `predefined` boolean NOT NULL DEFAULT 0;

-- Aggiunta tabelle per la gestione dei tipi spedizione
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Tipi di spedizione', 'Tipi di spedizione', 'spedizioni', 'SELECT |select| FROM `dt_spedizione` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.2', '2.4.2', '1', NULL, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Tipi di spedizione' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di spedizione'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di spedizione'), 'id', 'id', 1, 1, 0, 0, 1);

-- Aggiunto flag fiscale nei sezionali
ALTER TABLE `zz_segments` ADD `is_fiscale` boolean NOT NULL DEFAULT 1;

INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`,`note`, `predefined`, `is_fiscale`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Fatture pro-forma', '1=1', 'WHR', 'PRO-###', '', 0, 0);

-- Fix campi di ricerca
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Segmenti'), 'id', 'id', 0, 0, 0, 0, 0, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Segmenti'), 'Nome', 'name', 1, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Segmenti'), 'Modulo', '(SELECT name FROM zz_modules WHERE zz_modules.id = zz_segments.id_module)', 2, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Segmenti'), 'Maschera', 'pattern', 3, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Segmenti'), 'Note', 'note', 4, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Segmenti'), 'Predefinito', 'IF(predefined=1, ''Sì'', ''No'')', 5, 1, 0, 0, 1, 0, 1);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search` = 1 WHERE `zz_modules`.`name` = 'Banche' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search` = 1 WHERE `zz_modules`.`name` = 'Banche' AND `zz_views`.`name` = 'Filiale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search` = 1 WHERE `zz_modules`.`name` = 'Banche' AND `zz_views`.`name` = 'IBAN';
-- Fix Date emissione nello Scadenzario
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'data_emissione', `zz_views`.`format` = 1 WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Data emissione';
-- Normalizzazione default e predefined
ALTER TABLE `zz_views` CHANGE `default` `default` boolean NOT NULL DEFAULT 0;
ALTER TABLE `zz_prints` CHANGE `default` `default` boolean NOT NULL DEFAULT 0;
ALTER TABLE `an_tipianagrafiche` CHANGE `default` `default` boolean NOT NULL DEFAULT 0;
ALTER TABLE `an_zone` CHANGE `default` `default` boolean NOT NULL DEFAULT 0;
ALTER TABLE `zz_modules` CHANGE `default` `default` boolean NOT NULL DEFAULT 0;

ALTER TABLE `zz_segments` CHANGE `predefined` `predefined` boolean NOT NULL DEFAULT 0;

-- Campi per la gestione revisioni
ALTER TABLE `co_preventivi`  ADD `master_revision` INT NOT NULL  AFTER `tipo_sconto_globale`,  ADD `default_revision` TINYINT(1) NOT NULL  AFTER `master_revision`;

-- Plugin revisioni
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Revisioni', 'Revisioni', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'tab', '', 1, 0, 0, '', '', NULL, 'custom', 'revisioni', '');

-- Mi assicuro che non ci siano righe del preventivo collegate a preventivi non più esistenti
DELETE FROM co_righe_preventivi  WHERE  idpreventivo NOT IN (SELECT id FROM co_preventivi);

-- Chiave secondaria per le righe del preventivo
ALTER TABLE `co_righe_preventivi` ADD FOREIGN KEY (`idpreventivo`) REFERENCES `co_preventivi`(`id`) ON DELETE CASCADE;

-- Tabella categorie
CREATE TABLE IF NOT EXISTS `my_impianti_categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `colore` varchar(255) NOT NULL,
  `nota` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Categoria impianto
ALTER TABLE `my_impianti` ADD `id_categoria` INT(11) AFTER `idanagrafica`, ADD FOREIGN KEY (`id_categoria`) REFERENCES `my_impianti_categorie`(`id`) ON DELETE SET NULL;

-- Rinominato Categorie in Categorie articoli
UPDATE `zz_modules` SET `name` = 'Categorie articoli', `title` = 'Categorie articoli', `directory` = 'categorie_articoli' WHERE `zz_modules`.`name` = 'Categorie';

-- Modulo Categorie impianti
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Categorie impianti', 'Categorie impianti', 'categorie_impianti', 'SELECT |select| FROM `my_impianti_categorie` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.2', '2.4.2', '1', NULL, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Categorie impianti' AND `t2`.`name` = 'MyImpianti') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie impianti'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie impianti'), 'Nome', 'nome', 2, 1, 0, 1, 1);

-- Per i preventivi già in essere imposto master_revision = id e default_revision = 1
UPDATE `co_preventivi` SET `master_revision` = `id`, `default_revision` = 1;

-- Colonna natura iva e codice iva
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'IVA'), 'Natura', 'codice_natura_fe', 2, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'IVA'), 'Codice', 'codice', 1, 1, 0, 0, 1, 0, 1);

-- Colonna codice modalità pagamento
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Pagamenti'), 'Codice pagamento', 'CONCAT(codice_modalita_pagamento_fe, '' - '', (SELECT descrizione FROM fe_modalita_pagamento WHERE codice = codice_modalita_pagamento_fe) )', 2, 1, 0, 0, 1, 0, 1);

-- Aggiunto codice cig e codice cup per contratti e interventi
ALTER TABLE `co_contratti` ADD `codice_cig` VARCHAR(15) AFTER `tipo_sconto_globale`, ADD `codice_cup` VARCHAR(15) AFTER `codice_cig`, ADD `id_documento_fe` VARCHAR(20) AFTER `codice_cup`;
ALTER TABLE `in_interventi` ADD `codice_cig` VARCHAR(15) AFTER `tipo_sconto_globale`, ADD `codice_cup` VARCHAR(15) AFTER `codice_cig`, ADD `id_documento_fe` VARCHAR(20) AFTER `codice_cup`;

-- Aggiunta data e ora generazione fattura elettronica
ALTER TABLE `co_documenti` ADD `xml_generated_at` TIMESTAMP NULL AFTER `codice_xml`;

-- Colonna nella vista fatture per indicare se è stato generato o meno il file xml
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'icon_FE', 'IF(xml_generated_at IS NOT NULL, \'fa fa-file-code-o text-success\', \'\')', 10, 1, 0, 0, 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'icon_title_FE', 'IF(xml_generated_at IS NOT NULL, \'Generata\', \'\')', 10, 1, 0, 0, 0, 0, 0);

-- Colonna nella vista fatture per indicare se è stata inviata o meno la mail
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'icon_Inviata', 'IF((SELECT GROUP_CONCAT(DISTINCT`name` SEPARATOR \'\\n \') FROM zz_operations INNER JOIN zz_emails ON zz_operations.id_email = zz_emails.id WHERE zz_operations.id_module = (SELECT id FROM zz_modules WHERE `name` = \'Fatture di vendita\') AND op = \'send-email\' AND id_record = co_documenti.id GROUP BY id_email) IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 11, 1, 0, 0, 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'icon_title_Inviata', '(SELECT GROUP_CONCAT(DISTINCT`name` SEPARATOR \'\n \') FROM zz_operations INNER JOIN zz_emails ON zz_operations.id_email = zz_emails.id WHERE zz_operations.id_module = (SELECT id FROM zz_modules WHERE `name` = \'Fatture di vendita\') AND op = \'send-email\' AND id_record = co_documenti.id GROUP BY id_email)', 12, 1, 0, 0, 0, 0, 0);


-- Colonna codice destinatario
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Codice destinatario', 'codice_destinatario', 4, 1, 0, 0, NULL, NULL, 0, 0, 0);

-- Aggiungo descrizione per filtri
UPDATE `zz_group_module` SET `name` = 'Mostra agli agenti solo le anagrafiche di cui sono agenti' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Agenti') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche');

UPDATE `zz_group_module` SET `name` = 'Mostra ai tecnici solo le anagrafiche in cui sono coinvolti con delle attività' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Tecnici') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche');

UPDATE `zz_group_module` SET `name` = 'Mostra ai clienti solo la propria anagrafica' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche');

UPDATE `zz_group_module` SET `name` = 'Mostra ai clienti solo le proprie fatture' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');

UPDATE `zz_group_module` SET `name` = 'Mostra ai clienti solo le proprie fatture' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');

UPDATE `zz_group_module` SET `name` = 'Mostra agli agenti solo la prima nota delle anagrafiche di cui sono agenti' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Agenti') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota');

UPDATE `zz_group_module` SET `name` = 'Mostra ai clienti solo i propri impianti' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti') AND  `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti');


-- Importazione dell'account email PEC
INSERT INTO `zz_smtps` (`id`, `name`, `note`, `server`, `port`, `username`, `password`, `from_name`, `from_address`, `encryption`, `pec`, `predefined`) VALUES (NULL, 'PEC aziendale', '', '', '', '', '', '', '', '', '1', '0');

INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `predefined`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), (SELECT `id` FROM `zz_smtps` WHERE `pec` = 1 LIMIT 0,1), 'PEC', 'fa fa-file', 'Invio fattura numero {numero} del {data}', '', 'sdi01@pec.fatturapa.it', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato la fattura numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0');

INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES (NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'PEC' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Fattura di vendita'));

-- Ridenominazione "Ddt di vendita" in "Ddt in uscita"
UPDATE `zz_modules` SET `title`='Ddt in uscita' WHERE `name`='Ddt di vendita';
UPDATE `dt_tipiddt` SET `descrizione`='Ddt in uscita' WHERE `descrizione`='Ddt di vendita';

-- Ridenominazione "Ddt di acquisto" in "Ddt in ingresso"
UPDATE `zz_modules` SET `title`='Ddt in entrata' WHERE `name`='Ddt di acquisto';
UPDATE `dt_tipiddt` SET `descrizione`='Ddt in entrata' WHERE `descrizione`='Ddt di acquisto';
