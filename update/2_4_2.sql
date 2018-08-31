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

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Regime Fiscale', 'RF01', 'query=SELECT codice AS id, descrizione FROM fe_regime_fiscale', 1, 'Generali', 19);

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

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Tipo Cassa', 'TC22', 'query=SELECT codice AS id, descrizione FROM fe_tipo_cassa', 1, 'Fatturazione', 0);

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

ALTER TABLE `co_pagamenti` ADD `codice_modalita_pagemento_fe` varchar(4) NOT NULL;
UPDATE `co_pagamenti` SET `codice_modalita_pagemento_fe` = 'MP01' WHERE `descrizione` IN ('Rimessa diretta', 'Rimessa diretta a 30gg', 'Rimessa diretta 30gg fisso al 15', 'Contanti');
UPDATE `co_pagamenti` SET `codice_modalita_pagemento_fe` = 'MP02' WHERE `descrizione` IN ('Assegno');
UPDATE `co_pagamenti` SET `codice_modalita_pagemento_fe` = 'MP05' WHERE `descrizione` IN ('Bonifico 30gg d.f.', 'Bonifico 60gg d.f.', 'Bonifico 90gg d.f.', 'Bonifico 120gg d.f.', 'Bonifico 150gg d.f.', 'Bonifico 180gg d.f.', 'Bonifico 30/60gg d.f.', 'Bonifico 30/60gg d.f.', 'Bonifico 30/60/90gg d.f.', 'Bonifico 30/60/90gg d.f.', 'Bonifico 30/60/90gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30/60/90/120/150/180gg d.f.', 'Bonifico 30gg d.f.f.m.', 'Bonifico 60gg d.f.f.m.', 'Bonifico 90gg d.f.f.m.', 'Bonifico 120gg d.f.f.m.', 'Bonifico 150gg d.f.f.m.', 'Bonifico 180gg d.f.f.m.', 'Bonifico 30/60gg d.f.f.m.', 'Bonifico 30/60gg d.f.f.m.', 'Bonifico 30/60/90gg d.f.f.m.', 'Bonifico 30/60/90gg d.f.f.m.', 'Bonifico 30/60/90gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', 'Bonifico bancario');
UPDATE `co_pagamenti` SET `codice_modalita_pagemento_fe` = 'MP06' WHERE `descrizione` IN ('Cambiale');
UPDATE `co_pagamenti` SET `codice_modalita_pagemento_fe` = 'MP08' WHERE `descrizione` IN ('Bancomat', 'Visa');
UPDATE `co_pagamenti` SET `codice_modalita_pagemento_fe` = 'MP12' WHERE `descrizione` IN ('Ri.Ba. 30gg d.f.', 'Ri.Ba. 60gg d.f.', 'Ri.Ba. 90gg d.f.', 'Ri.Ba. 120gg d.f.', 'Ri.Ba. 150gg d.f.', 'Ri.Ba. 180gg d.f.', 'Ri.Ba. 30/60gg d.f.', 'Ri.Ba. 30/60gg d.f.', 'Ri.Ba. 30/60/90gg d.f.', 'Ri.Ba. 30/60/90gg d.f.', 'Ri.Ba. 30/60/90gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 'Ri.Ba. 30gg d.f.f.m.', 'Ri.Ba. 60gg d.f.f.m.', 'Ri.Ba. 90gg d.f.f.m.', 'Ri.Ba. 120gg d.f.f.m.', 'Ri.Ba. 150gg d.f.f.m.', 'Ri.Ba. 180gg d.f.f.m.', 'Ri.Ba. 30/60gg d.f.f.m.', 'Ri.Ba. 30/60gg d.f.f.m.', 'Ri.Ba. 30/60/90gg d.f.f.m.', 'Ri.Ba. 30/60/90gg d.f.f.m.', 'Ri.Ba. 30/60/90gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.');
ALTER TABLE `co_pagamenti` ADD FOREIGN KEY (`codice_modalita_pagemento_fe`) REFERENCES `fe_modalita_pagamento`(`codice`) ON DELETE CASCADE;

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

ALTER TABLE `co_iva` DROP `esente`, ADD `codice_natura_fe` varchar(4) NOT NULL;
-- UPDATE `co_iva` SET `codice_natura_fe` = 'TD01' WHERE `descrizione` IN ('Fattura immediata di acquisto', 'Fattura immediata di vendita', 'Fattura differita di acquisto', 'Fattura differita di vendita', 'Fattura accompagnatoria di acquisto', 'Fattura accompagnatoria di vendita');
-- ALTER TABLE `co_iva` ADD FOREIGN KEY (`codice_natura_fe`) REFERENCES `fe_natura`(`codice`) ON DELETE CASCADE;

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

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Causale ritenuta d''acconto', '', 'query=SELECT codice AS id, descrizione FROM fe_causali_pagamento_ritenuta', 1, 'Fatturazione', 0);

INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Authorization ID Indice PA', '', 'string', 1, 'Generali', 0);

ALTER TABLE `an_anagrafiche` ADD `codice_destinatario` varchar(7);

-- Plugin Fatturazione Elettronica
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES (NULL, 'Fatturazione Elettronica', 'Fatturazione Elettronica', (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), 'tab', 'fatturazione', 'custom');

INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `main`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 1, 'Fattura Elettronica', 'fa fa-file', 'Invio fattura numero {numero} del {data}', '', 'sdi01@pec.fatturapa.it', '', '<p>Gentile Cliente,</p>\r\n<p>inviamo in allegato la fattura numero {numero} del {data}.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0', '0');
INSERT INTO `zz_email_print` (`id`, `id_email`, `id_print`) VALUES (NULL, (SELECT `id` FROM `zz_emails` WHERE `name` = 'Fattura Elettronica' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita')), (SELECT `id` FROM `zz_prints` WHERE `name` = 'Fattura di vendita'));
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
UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'co_contratti_promemoria', 'co_promemoria');

-- Fix nome in zz_files
ALTER TABLE `zz_files` CHANGE `nome` `name` varchar(255) NOT NULL;
UPDATE `zz_files` SET `id_module` = NULL WHERE `id_plugin` IS NOT NULL;
