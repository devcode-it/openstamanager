--
-- Struttura della tabella `an_anagrafiche`
--

CREATE TABLE IF NOT EXISTS `an_anagrafiche` (
  `idanagrafica` int(11) NOT NULL AUTO_INCREMENT,
  `codice` varchar(20) NOT NULL,
  `ragione_sociale` varchar(255) NOT NULL,
  `piva` varchar(15) NOT NULL,
  `codice_fiscale` varchar(16) NOT NULL,
  `capitale_sociale` varchar(255) NOT NULL,
  `data_nascita` date NOT NULL,
  `luogo_nascita` varchar(255) NOT NULL,
  `indirizzo` varchar(255) NOT NULL,
  `indirizzo2` varchar(255) NOT NULL,
  `citta` varchar(255) NOT NULL,
  `cap` varchar(10) NOT NULL,
  `provincia` varchar(2) NOT NULL,
  `km` float(10,2) NOT NULL,
  `nazione` varchar(255) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `fax` varchar(50) NOT NULL,
  `cellulare` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sitoweb` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  `codiceri` varchar(15) NOT NULL,
  `codicerea` varchar(15) NOT NULL,
  `appoggiobancario` varchar(255) NOT NULL,
  `filiale` varchar(100) NOT NULL,
  `codiceiban` varchar(40) NOT NULL,
  `diciturafissafattura` varchar(255) NOT NULL,
  `idpagamento` int(11) NOT NULL,
  `idlistino` int(11) NOT NULL,
  `idiva` int(11) NOT NULL,
  `idsede_fatturazione` int(11) NOT NULL,
  `settore` varchar(255) NOT NULL,
  `marche` varchar(5000) NOT NULL,
  `dipendenti` int(11) NOT NULL,
  `macchine` int(11) NOT NULL,
  `idagente` int(11) NOT NULL,
  `idrelazione` int(11) NOT NULL,
  `agentemaster` tinyint(1) NOT NULL,
  `idzona` int(11) NOT NULL,
  `foro_competenza` varchar(255) NOT NULL,
  `nome_cognome` varchar(255) NOT NULL,
  `iscrizione_tribunale` varchar(2) NOT NULL,
  `cciaa` varchar(25) NOT NULL,
  `cciaa_citta` varchar(100) NOT NULL,
  `n_alboartigiani` varchar(25) DEFAULT NULL,
  `colore` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `deleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`idanagrafica`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `an_nazioni`
--

CREATE TABLE IF NOT EXISTS `an_nazioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `an_nazioni`
--

INSERT INTO `an_nazioni` (`id`, `nome`) VALUES
(2, 'ALGERIA'),
(3, 'ANDORRA'),
(4, 'ANGOLA'),
(5, 'ANGUILLA'),
(6, 'ANTIGUA AND BARBUDA'),
(7, 'ARGENTINA'),
(8, 'ARMENIA'),
(9, 'ARUBA'),
(10, 'AUSTRALIA'),
(11, 'AUSTRIA'),
(12, 'AZERBAIJAN REPUBLIC'),
(13, 'BAHAMAS'),
(14, 'BAHRAIN'),
(15, 'BARBADOS'),
(16, 'BELGIO'),
(17, 'BELIZE'),
(18, 'BENIN'),
(19, 'BERMUDA'),
(20, 'BHUTAN'),
(21, 'BOLIVIA'),
(22, 'BOSNIA AND HERZEGOVINA'),
(23, 'BOTSWANA'),
(24, 'BRASILE'),
(25, 'BRITISH VIRGIN ISLANDS'),
(26, 'BRUNEI'),
(27, 'BULGARIA'),
(28, 'BURKINA FASO'),
(29, 'BURUNDI'),
(30, 'CAMBOGIA'),
(31, 'CANADA'),
(32, 'CAPO VERDE'),
(33, 'CAYMAN ISLANDS'),
(34, 'CHAD'),
(35, 'CHILE'),
(36, 'CHINA WORLDWIDE'),
(37, 'COLOMBIA'),
(38, 'COMOROS'),
(39, 'COOK ISLANDS'),
(40, 'COSTA RICA'),
(41, 'CROAZIA'),
(42, 'CIPRO'),
(43, 'REPUBBLICA CECA'),
(44, 'DEMOCRATIC REPUBLIC OF THE CONGO'),
(45, 'DANIMARCA'),
(46, 'DJIBOUTI'),
(47, 'DOMINICA'),
(48, 'REPUBBLICA DOMINICANA'),
(49, 'ECUADOR'),
(50, 'EL SALVADOR'),
(51, 'ERITREA'),
(52, 'ESTONIA'),
(53, 'ETHIOPIA'),
(54, 'FALKLAND ISLANDS'),
(55, 'FAROE ISLANDS'),
(56, 'FEDERATED STATES OF MICRONESIA'),
(57, 'FIJI'),
(58, 'FINLANDIA'),
(59, 'FRANCIA'),
(60, 'FRENCH GUIANA'),
(61, 'FRENCH POLYNESIA'),
(62, 'GABON REPUBLIC'),
(63, 'GAMBIA'),
(64, 'GERMANIA'),
(65, 'GIBRALTAR'),
(66, 'GRECIA'),
(67, 'GREENLAND'),
(68, 'GRENADA'),
(69, 'GUADELOUPE'),
(70, 'GUATEMALA'),
(71, 'GUINEA'),
(72, 'GUINEA BISSAU'),
(73, 'GUYANA'),
(74, 'HONDURAS'),
(75, 'HONG KONG'),
(76, 'UNGHERIA'),
(77, 'ISLANDA'),
(78, 'INDIA'),
(79, 'INDONESIA'),
(80, 'IRLANDA'),
(81, 'ISRAELE'),
(82, 'ITALIA'),
(83, 'JAMAICA'),
(84, 'GIAPPONE'),
(85, 'JORDAN'),
(86, 'KAZAKHSTAN'),
(87, 'KENYA'),
(88, 'KIRIBATI'),
(89, 'KUWAIT'),
(90, 'KYRGYZSTAN'),
(91, 'LAOS'),
(92, 'LATVIA'),
(93, 'LESOTHO'),
(94, 'LIECHTENSTEIN'),
(95, 'LITUANIA'),
(96, 'LUSSEMBURGO'),
(97, 'MADAGASCAR'),
(98, 'MALAWI'),
(99, 'MALESIA'),
(100, 'MALDIVE'),
(101, 'MALI'),
(102, 'MALTA'),
(103, 'MARSHALL ISLANDS'),
(104, 'MARTINIQUE'),
(105, 'MAURITANIA'),
(106, 'MAURITIUS'),
(107, 'MAYOTTE'),
(108, 'MESSICO'),
(109, 'MONGOLIA'),
(110, 'MONTSERRAT'),
(111, 'MAROCCO'),
(112, 'MOZAMBICO'),
(113, 'NAMIBIA'),
(114, 'NAURU'),
(115, 'NEPAL'),
(116, 'OLANDA'),
(117, 'NETHERLANDS ANTILLES'),
(118, 'NUOVA CALEDONIA'),
(119, 'NUOVA ZELANDA'),
(120, 'NICARAGUA'),
(121, 'NIGERIA'),
(122, 'NIUE'),
(123, 'NORFOLK ISLAND'),
(124, 'NORWEGIA'),
(125, 'OMAN'),
(126, 'PALAU'),
(127, 'PANAMA'),
(128, 'PAPUA NUOVA GUINEA'),
(129, 'PERU'),
(130, 'FILIPPINE'),
(131, 'PITCAIRN ISLANDS'),
(132, 'POLONIA'),
(133, 'PORTOGALLO'),
(134, 'QATAR'),
(135, 'REPUBBLICA DEL CONGO'),
(136, 'REUNION'),
(137, 'ROMANIA'),
(138, 'RUSSIA'),
(139, 'RUANDA'),
(140, 'SAINT VINCENT AND THE GRENADINES'),
(141, 'SAMOA'),
(142, 'SAN MARINO'),
(144, 'SAUDI ARABIA'),
(145, 'SENEGAL'),
(146, 'SEYCHELLES'),
(147, 'SIERRA LEONE'),
(148, 'SINGAPORE'),
(149, 'SLOVACCHIA'),
(150, 'SLOVENIA'),
(151, 'SOLOMON ISLANDS'),
(152, 'SOMALIA'),
(153, 'SUD AFRICA'),
(154, 'SUD KOREA'),
(155, 'SPAGNA'),
(156, 'SRI LANKA'),
(157, 'ST. HELENA'),
(158, 'ST. KITTS AND NEVIS'),
(159, 'ST. LUCIA'),
(160, 'ST. PIERRE AND MIQUELON'),
(161, 'SURINAME'),
(162, 'SVALBARD AND JAN MAYEN ISLANDS'),
(163, 'SWAZILAND'),
(164, 'SVEZIA'),
(165, 'SVIZZERA'),
(166, 'TAIWAN'),
(167, 'TAJIKISTAN'),
(168, 'TANZANIA'),
(169, 'THAILAND'),
(170, 'TOGO'),
(171, 'TONGA'),
(172, 'TRINIDAD E TOBAGO'),
(173, 'TUNISIA'),
(174, 'TURCHIA'),
(175, 'TURKMENISTAN'),
(176, 'TURKS AND CAICOS ISLANDS'),
(177, 'TUVALU'),
(178, 'UGANDA'),
(179, 'UCRAINA'),
(180, 'EMIRATI ARABI UNITI'),
(181, 'REGNO UNITO'),
(182, 'STATI UNITI'),
(183, 'URUGUAY'),
(184, 'VANUATU'),
(185, 'CITTÀ DEL VATICANO'),
(186, 'VENEZUELA'),
(187, 'VIETNAM'),
(188, 'WALLIS AND FUTUNA ISLANDS'),
(189, 'YEMEN'),
(190, 'ZAMBIA'),
(193, 'ALBANIA ');

-- --------------------------------------------------------

--
-- Struttura della tabella `an_referenti`
--

CREATE TABLE IF NOT EXISTS `an_referenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `mansione` varchar(255) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idsede` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `an_relazioni`
--

CREATE TABLE IF NOT EXISTS `an_relazioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `colore` varchar(7) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `an_relazioni`
--

INSERT INTO `an_relazioni` (`id`, `descrizione`, `colore`) VALUES
(1, 'Da contattare', '#caffb7'),
(2, 'Da richiamare', '#8fbafd'),
(3, 'Da non richiamare', '#ff908c'),
(4, 'Appuntamento fissato', '#ffc400'),
(5, 'Attivo', '#00b913'),
(6, 'Dormiente', '#a2a2a2');

-- --------------------------------------------------------

--
-- Struttura della tabella `an_sedi`
--

CREATE TABLE IF NOT EXISTS `an_sedi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomesede` varchar(255) NOT NULL COMMENT 'Nome sede',
  `piva` varchar(15) NOT NULL COMMENT 'P.Iva',
  `codice_fiscale` varchar(16) NOT NULL COMMENT 'Codice Fiscale',
  `indirizzo` varchar(255) NOT NULL COMMENT 'Indirizzo',
  `indirizzo2` varchar(255) NOT NULL COMMENT 'Indirizzo2',
  `citta` varchar(255) NOT NULL COMMENT 'Citt&agrave;',
  `cap` varchar(10) NOT NULL COMMENT 'C.A.P.',
  `provincia` varchar(2) NOT NULL COMMENT 'Provincia',
  `km` float(10,2) NOT NULL,
  `nazione` varchar(255) NOT NULL COMMENT 'Nazione',
  `telefono` varchar(20) NOT NULL COMMENT 'Telefono',
  `fax` varchar(20) NOT NULL COMMENT 'Fax',
  `cellulare` varchar(20) NOT NULL COMMENT 'Cellulare',
  `email` varchar(255) NOT NULL COMMENT 'Email',
  `idanagrafica` int(11) NOT NULL,
  `idzona` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `an_tipianagrafiche`
--

CREATE TABLE IF NOT EXISTS `an_tipianagrafiche` (
  `idtipoanagrafica` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `default` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`idtipoanagrafica`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `an_tipianagrafiche`
--

INSERT INTO `an_tipianagrafiche` (`idtipoanagrafica`, `descrizione`, `default`) VALUES
(1, 'Cliente', 1),
(2, 'Tecnico', 1),
(3, 'Azienda', 1),
(4, 'Fornitore', 1),
(5, 'Vettore', 1),
(6, 'Agente', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `an_tipianagrafiche_anagrafiche`
--

CREATE TABLE IF NOT EXISTS `an_tipianagrafiche_anagrafiche` (
  `idtipoanagrafica` int(11) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  PRIMARY KEY (`idtipoanagrafica`,`idanagrafica`),
  KEY `idanagrafica` (`idanagrafica`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `an_zone`
--

CREATE TABLE IF NOT EXISTS `an_zone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descrizione` varchar(2000) NOT NULL,
  `default` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_contratti`
--

CREATE TABLE IF NOT EXISTS `co_contratti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `idagente` int(11) NOT NULL,
  `data_bozza` datetime DEFAULT NULL,
  `data_accettazione` datetime DEFAULT NULL,
  `data_rifiuto` datetime DEFAULT NULL,
  `data_conclusione` datetime DEFAULT NULL,
  `rinnovabile` tinyint(1) NOT NULL,
  `giorni_preavviso_rinnovo` smallint(6) NOT NULL,
  `budget` float(12,4) NOT NULL,
  `descrizione` text,
  `idstato` tinyint(4) DEFAULT NULL,
  `idreferente` int(11) DEFAULT NULL,
  `validita` int(11) DEFAULT NULL,
  `esclusioni` text NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idpagamento` int(11) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `costo_diritto_chiamata` float(12,4) NOT NULL,
  `ore_lavoro` float(12,4) NOT NULL,
  `costo_orario` float(12,4) NOT NULL,
  `costo_km` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_contratti_interventi`
--

CREATE TABLE IF NOT EXISTS `co_contratti_interventi` (
  `idcontratto` int(11) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  PRIMARY KEY (`idcontratto`,`idintervento`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_documenti`
--

CREATE TABLE IF NOT EXISTS `co_documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL,
  `numero_esterno` varchar(100) NOT NULL,
  `data` datetime NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idcausalet` int(11) NOT NULL,
  `idspedizione` int(11) NOT NULL,
  `idporto` int(11) NOT NULL,
  `idaspettobeni` int(11) NOT NULL,
  `idvettore` int(11) NOT NULL,
  `n_colli` int(11) NOT NULL,
  `idsede` int(11) NOT NULL,
  `idtipodocumento` tinyint(4) NOT NULL,
  `idstatodocumento` tinyint(4) NOT NULL,
  `idpagamento` int(11) NOT NULL,
  `idconto` int(11) NOT NULL,
  `idrivalsainps` int(11) NOT NULL,
  `idritenutaacconto` int(11) NOT NULL,
  `rivalsainps` float(12,4) NOT NULL,
  `iva_rivalsainps` float(7,3) NOT NULL,
  `ritenutaacconto` float(12,4) NOT NULL,
  `bollo` float(12,4) NOT NULL,
  `note` text NOT NULL,
  `note_aggiuntive` text NOT NULL,
  `buono_ordine` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_iva`
--

CREATE TABLE IF NOT EXISTS `co_iva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `percentuale` float(5,2) NOT NULL,
  `descrizione2` varchar(200) NOT NULL,
  `indetraibile` float(5,2) NOT NULL,
  `esente` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_iva`
--

INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `descrizione2`, `indetraibile`, `esente`) VALUES
(1, 'Corrispettivi in ventilazione', 0.00, '', 0.00, 0),
(2, 'Iva 4% indetraibile al 50%', 4.00, '', 50.00, 0),
(3, 'Iva 04% indetraibile al 100%', 4.00, '', 100.00, 0),
(4, 'Iva 04% Intra', 4.00, '', 0.00, 0),
(5, 'Iva 8', 8.00, '', 0.00, 0),
(6, 'Iva 9% ', 9.00, '', 0.00, 0),
(7, 'Esente art.10', 0.00, '', 0.00, 1),
(8, 'Iva 10%', 10.00, '', 0.00, 0),
(9, 'Iva 10% indetraibile al 50%', 10.00, '', 50.00, 0),
(10, 'Iva 10% indetraibile al 100%', 10.00, '', 100.00, 0),
(11, 'Iva 10% Intra', 10.00, '', 0.00, 0),
(12, 'Iva 10% indetraibile', 10.00, '', 100.00, 0),
(13, 'Esente art. 2 DPR 633/72', 0.00, '', 0.00, 1),
(14, 'N.S. iva art.4 D.P.R.633/72', 0.00, '', 0.00, 0),
(15, 'Iva 20% in reverse charge', 20.00, '', 0.00, 0),
(16, 'Esente art.15', 0.00, '', 0.00, 1),
(17, 'Non imponibile art. 7', 0.00, '', 0.00, 1),
(18, 'Iva 19%', 19.00, '', 0.00, 0),
(19, 'Iva 2%', 2.00, '', 0.00, 0),
(20, 'Iva 20%', 20.00, '', 0.00, 0),
(21, 'Iva 20% indetraibile al 90%', 20.00, '', 90.00, 0),
(22, 'Iva 20% esente prorata', 20.00, '', 100.00, 0),
(23, 'Iva 20% Intra', 20.00, '', 0.00, 0),
(24, 'Iva 20% indetraibile', 20.00, '', 100.00, 0),
(25, 'Iva 21% indetraibile 50%', 21.00, '', 50.00, 0),
(26, 'Non imponibile art.72', 0.00, '', 0.00, 1),
(27, 'Esente art. 1', 0.00, '', 0.00, 1),
(28, 'Non imponibile art.26 C.2', 0.00, '', 0.00, 1),
(29, 'Non imponibile art.74', 0.00, '', 0.00, 1),
(30, 'Non imponibile art. 41', 0.00, '', 0.00, 1),
(31, 'Fuori campo iva', 0.00, '', 0.00, 0),
(32, 'Iva 21%', 21.00, '', 0.00, 0),
(33, 'Iva 21% S.Marino', 21.00, '', 0.00, 0),
(34, 'Iva 4%', 4.00, '', 0.00, 0),
(35, 'Esente art. 74', 0.00, '', 0.00, 1),
(36, 'Iva 2% indetraibile', 2.00, '', 100.00, 0),
(37, 'Iva 4% indetraibile', 4.00, '', 100.00, 0),
(38, 'Esente art.6', 0.00, '', 0.00, 1),
(39, 'Esente art. 5', 0.00, '', 0.00, 1),
(40, 'Art. 74 ter 10% indetraibile', 10.00, '', 100.00, 0),
(41, 'Art. 74 ter 4% indetraibile', 4.00, '', 100.00, 0),
(42, 'Art. 74 ter 20% iva indetraibile', 20.00, '', 100.00, 0),
(43, 'Non imponibile art. 74 ter', 0.00, '', 0.00, 0),
(44, 'Non imponibile art. 8/C', 0.00, '', 0.00, 1),
(45, 'Esente art.10 C.27Q', 0.00, '', 0.00, 1),
(46, 'Escluso art. 2', 0.00, '', 0.00, 1),
(47, 'Non soggetto art. 7', 0.00, '', 0.00, 0),
(48, 'Non imponibile art. 8', 0.00, '', 0.00, 1),
(49, 'Non imponibile art. 9', 0.00, '', 0.00, 1),
(50, 'Esente art. 10', 0.00, '', 0.00, 1),
(51, 'Esente art. 10 n. 11', 0.00, '', 0.00, 1),
(52, 'Escluso art. 15', 0.00, '', 0.00, 1),
(53, 'Non sogg  art. 17 c.5', 0.00, '', 0.00, 1),
(54, 'Esente art. 10 n. 18', 0.00, '', 0.00, 1),
(55, 'Esente art. 10 n.1 a n.9', 0.00, '', 0.00, 1),
(56, 'Art. 36 D.L. 41/95 acq.', 0.00, '', 0.00, 0),
(57, 'Non imponibile art. 40 D.L. 331 c. 5', 0.00, '', 0.00, 1),
(58, 'Non imponibile art. 41 D.L. 331/93', 0.00, '', 0.00, 1),
(59, 'Non imponibile art. 40 D.L. 331 c. 4 bis', 0.00, '', 0.00, 1),
(60, 'Non imponibile art. 40 D.L. 331 c. 6/8', 0.00, '', 0.00, 1),
(61, 'Non imponibile art. 40 D.L. 331 c. 4 bis', 0.00, '', 0.00, 1),
(62, 'Non imponibile art. 58 D.L. 331', 0.00, '', 0.00, 1),
(63, 'Non imponibile art. 71 e 72', 0.00, '', 0.00, 1),
(64, 'Non imponibile art. 74', 0.00, '', 0.00, 1),
(65, 'Non imponibile art. 8 lett. a)', 0.00, '', 0.00, 1),
(66, 'Non imponibile art. 8 lett. c)', 0.00, '', 0.00, 1),
(67, 'Non imponibile art. 9 c. 2', 0.00, '', 0.00, 1),
(68, 'Non imponibile art. 9 punto 9)', 0.00, '', 0.00, 1),
(69, 'Art. 17 comma 6 DPR 633/72 10%', 10.00, '', 0.00, 0),
(70, 'Art. 17 comma 6 DPR 633/72 20%', 20.00, '', 0.00, 0),
(71, 'Art. 17 comma 6 DPR 633/72 4%', 4.00, '', 0.00, 0),
(72, 'Acquisti da soggetti minimi', 0.00, '', 0.00, 0),
(73, 'Cess. fabbr. strum. art.10 n.8', 0.00, '', 0.00, 0),
(74, 'Art. 74 c. 7 e 8', 0.00, '', 0.00, 0),
(75, 'Fuori campo Iva', 0.00, '', 0.00, 0),
(76, 'Non Imponibile San Marino', 0.00, '', 0.00, 0),
(77, 'Esente art. 10 27 quinquies', 0.00, '', 0.00, 1),
(78, 'Autofatture 10% subappalto', 10.00, '', 0.00, 0),
(79, 'Autofatture 20% subappalto', 20.00, '', 0.00, 0),
(80, 'Autofatture 4% subappalto', 4.00, '', 0.00, 0),
(81, 'Operaz. ag. viag. normale 4%', 4.00, '', 0.00, 0),
(82, 'Operaz. ag. viag. normale 10%', 10.00, '', 0.00, 0),
(83, 'Autof. acq. fabbr. strum. 10%', 10.00, '', 0.00, 0),
(84, 'Autof. acq. fabbr. strum. 4%', 4.00, '', 0.00, 0),
(85, 'Operaz. ag. viag. normale 20%', 20.00, '', 0.00, 0),
(86, 'Autof. acq. fabbr. strum. 20%', 20.00, '', 0.00, 0),
(87, 'Art. 36 D.L. 41/95 vend.', 0.00, '', 0.00, 0),
(88, 'Art. 17 comma 6 DPR 633/72', 0.00, '', 0.00, 0),
(89, 'Iva 21% indetraibile', 21.00, '', 100.00, 0),
(90, 'Iva in reverse charge indetraibile', 0.00, '', 100.00, 0),
(91, 'Iva 22%', 22.00, '', 0.00, 0),
(92, 'Iva 22% indetraibile', 22.00, '', 100.00, 0),
(93, 'Iva 22% indetraibile al 50%', 22.00, '', 50.00, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_movimenti`
--

CREATE TABLE IF NOT EXISTS `co_movimenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idmastrino` int(11) NOT NULL,
  `data` datetime NOT NULL,
  `data_documento` datetime NOT NULL,
  `iddocumento` varchar(10) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `descrizione` text NOT NULL,
  `idconto` int(11) NOT NULL,
  `totale` float(12,4) DEFAULT NULL,
  `primanota` float(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_ordiniservizio`
--

CREATE TABLE IF NOT EXISTS `co_ordiniservizio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcontratto` int(11) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  `data_scadenza` datetime NOT NULL,
  `matricola` varchar(25) NOT NULL,
  `copia_centrale` tinyint(1) NOT NULL,
  `copia_cliente` tinyint(1) NOT NULL,
  `copia_amministratore` tinyint(1) NOT NULL,
  `funzionamento_in_sicurezza` tinyint(1) NOT NULL,
  `stato` enum('aperto','chiuso') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_ordiniservizio_pianificazionefatture`
--

CREATE TABLE IF NOT EXISTS `co_ordiniservizio_pianificazionefatture` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcontratto` int(11) NOT NULL,
  `data_scadenza` datetime NOT NULL,
  `idzona` int(11) NOT NULL,
  `iddocumento` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_ordiniservizio_vociservizio`
--

CREATE TABLE IF NOT EXISTS `co_ordiniservizio_vociservizio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idordineservizio` int(11) NOT NULL,
  `voce` varchar(255) NOT NULL,
  `categoria` varchar(255) NOT NULL,
  `note` varchar(2000) NOT NULL,
  `eseguito` tinyint(1) NOT NULL,
  `presenza` tinyint(1) NOT NULL,
  `esito` tinyint(1) NOT NULL,
  `priorita` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_pagamenti`
--

CREATE TABLE IF NOT EXISTS `co_pagamenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(50) NOT NULL,
  `giorno` tinyint(4) NOT NULL,
  `num_giorni` varchar(100) NOT NULL,
  `prc` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_pagamenti`
--

INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`) VALUES
(1, 'Rimessa diretta', 0, '0', 100),
(2, 'Rimessa diretta a 30gg', 0, '30', 100),
(3, 'Rimessa diretta 30gg fisso al 15', 15, '30', 100),
(4, 'Ri.Ba. 30gg d.f.', 0, '30', 100),
(5, 'Ri.Ba. 60gg d.f.', 0, '60', 100),
(6, 'Ri.Ba. 90gg d.f.', 0, '90', 100),
(7, 'Ri.Ba. 120gg d.f.', 0, '120', 100),
(8, 'Ri.Ba. 150gg d.f.', 0, '150', 100),
(9, 'Ri.Ba. 180gg d.f.', 0, '180', 100),
(10, 'Ri.Ba. 30/60gg d.f.', 0, '30', 50),
(11, 'Ri.Ba. 30/60gg d.f.', 0, '60', 50),
(12, 'Ri.Ba. 30/60/90gg d.f.', 0, '30', 33),
(13, 'Ri.Ba. 30/60/90gg d.f.', 0, '60', 33),
(14, 'Ri.Ba. 30/60/90gg d.f.', 0, '90', 34),
(15, 'Ri.Ba. 30/60/90/120gg d.f.', 0, '30', 25),
(16, 'Ri.Ba. 30/60/90/120gg d.f.', 0, '60', 25),
(17, 'Ri.Ba. 30/60/90/120gg d.f.', 0, '90', 25),
(18, 'Ri.Ba. 30/60/90/120gg d.f.', 0, '120', 25),
(19, 'Ri.Ba. 30/60/90/120/150gg d.f.', 0, '30', 20),
(20, 'Ri.Ba. 30/60/90/120/150gg d.f.', 0, '60', 20),
(21, 'Ri.Ba. 30/60/90/120/150gg d.f.', 0, '90', 20),
(22, 'Ri.Ba. 30/60/90/120/150gg d.f.', 0, '120', 20),
(23, 'Ri.Ba. 30/60/90/120/150gg d.f.', 0, '150', 20),
(24, 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 0, '30', 16),
(25, 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 0, '60', 16),
(26, 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 0, '90', 16),
(27, 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 0, '120', 16),
(28, 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 0, '150', 16),
(29, 'Ri.Ba. 30/60/90/120/150/180gg d.f.', 0, '180', 20),
(30, 'Ri.Ba. 30gg d.f.f.m.', -1, '30', 100),
(31, 'Ri.Ba. 60gg d.f.f.m.', -1, '60', 100),
(32, 'Ri.Ba. 90gg d.f.f.m.', -1, '90', 100),
(33, 'Ri.Ba. 120gg d.f.f.m.', -1, '120', 100),
(34, 'Ri.Ba. 150gg d.f.f.m.', -1, '150', 100),
(35, 'Ri.Ba. 180gg d.f.f.m.', -1, '180', 100),
(36, 'Ri.Ba. 30/60gg d.f.f.m.', -1, '30', 50),
(37, 'Ri.Ba. 30/60gg d.f.f.m.', -1, '60', 50),
(38, 'Ri.Ba. 30/60/90gg d.f.f.m.', -1, '30', 33),
(39, 'Ri.Ba. 30/60/90gg d.f.f.m.', -1, '60', 33),
(40, 'Ri.Ba. 30/60/90gg d.f.f.m.', -1, '90', 34),
(41, 'Ri.Ba. 30/60/90/120gg d.f.f.m.', -1, '30', 25),
(42, 'Ri.Ba. 30/60/90/120gg d.f.f.m.', -1, '60', 25),
(43, 'Ri.Ba. 30/60/90/120gg d.f.f.m.', -1, '90', 25),
(44, 'Ri.Ba. 30/60/90/120gg d.f.f.m.', -1, '120', 25),
(45, 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', -1, '30', 20),
(46, 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', -1, '60', 20),
(47, 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', -1, '90', 20),
(48, 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', -1, '120', 20),
(49, 'Ri.Ba. 30/60/90/120/150gg d.f.f.m.', -1, '150', 20),
(50, 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', -1, '30', 16),
(51, 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', -1, '60', 16),
(52, 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', -1, '90', 16),
(53, 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', -1, '120', 16),
(54, 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', -1, '150', 16),
(55, 'Ri.Ba. 30/60/90/120/150/180gg d.f.f.m.', -1, '180', 20),
(56, 'Bonifico 30gg d.f.', 0, '30', 100),
(57, 'Bonifico 60gg d.f.', 0, '60', 100),
(58, 'Bonifico 90gg d.f.', 0, '90', 100),
(59, 'Bonifico 120gg d.f.', 0, '120', 100),
(60, 'Bonifico 150gg d.f.', 0, '150', 100),
(61, 'Bonifico 180gg d.f.', 0, '180', 100),
(62, 'Bonifico 30/60gg d.f.', 0, '30', 50),
(63, 'Bonifico 30/60gg d.f.', 0, '60', 50),
(64, 'Bonifico 30/60/90gg d.f.', 0, '30', 33),
(65, 'Bonifico 30/60/90gg d.f.', 0, '60', 33),
(66, 'Bonifico 30/60/90gg d.f.', 0, '90', 34),
(67, 'Bonifico 30/60/90/120gg d.f.', 0, '30', 25),
(68, 'Bonifico 30/60/90/120gg d.f.', 0, '60', 25),
(69, 'Bonifico 30/60/90/120gg d.f.', 0, '90', 25),
(70, 'Bonifico 30/60/90/120gg d.f.', 0, '120', 25),
(71, 'Bonifico 30/60/90/120/150gg d.f.', 0, '30', 20),
(72, 'Bonifico 30/60/90/120/150gg d.f.', 0, '60', 20),
(73, 'Bonifico 30/60/90/120/150gg d.f.', 0, '90', 20),
(74, 'Bonifico 30/60/90/120/150gg d.f.', 0, '120', 20),
(75, 'Bonifico 30/60/90/120/150gg d.f.', 0, '150', 20),
(76, 'Bonifico 30/60/90/120/150/180gg d.f.', 0, '30', 16),
(77, 'Bonifico 30/60/90/120/150/180gg d.f.', 0, '60', 16),
(78, 'Bonifico 30/60/90/120/150/180gg d.f.', 0, '90', 16),
(79, 'Bonifico 30/60/90/120/150/180gg d.f.', 0, '120', 16),
(80, 'Bonifico 30/60/90/120/150/180gg d.f.', 0, '150', 16),
(81, 'Bonifico 30/60/90/120/150/180gg d.f.', 0, '180', 20),
(82, 'Bonifico 30gg d.f.f.m.', -1, '30', 100),
(83, 'Bonifico 60gg d.f.f.m.', -1, '60', 100),
(84, 'Bonifico 90gg d.f.f.m.', -1, '90', 100),
(85, 'Bonifico 120gg d.f.f.m.', -1, '120', 100),
(86, 'Bonifico 150gg d.f.f.m.', -1, '150', 100),
(87, 'Bonifico 180gg d.f.f.m.', -1, '180', 100),
(88, 'Bonifico 30/60gg d.f.f.m.', -1, '30', 50),
(89, 'Bonifico 30/60gg d.f.f.m.', -1, '60', 50),
(90, 'Bonifico 30/60/90gg d.f.f.m.', -1, '30', 33),
(91, 'Bonifico 30/60/90gg d.f.f.m.', -1, '60', 33),
(92, 'Bonifico 30/60/90gg d.f.f.m.', -1, '90', 34),
(93, 'Bonifico 30/60/90/120gg d.f.f.m.', -1, '30', 25),
(94, 'Bonifico 30/60/90/120gg d.f.f.m.', -1, '60', 25),
(95, 'Bonifico 30/60/90/120gg d.f.f.m.', -1, '90', 25),
(96, 'Bonifico 30/60/90/120gg d.f.f.m.', -1, '120', 25),
(97, 'Bonifico 30/60/90/120/150gg d.f.f.m.', -1, '30', 20),
(98, 'Bonifico 30/60/90/120/150gg d.f.f.m.', -1, '60', 20),
(99, 'Bonifico 30/60/90/120/150gg d.f.f.m.', -1, '90', 20),
(100, 'Bonifico 30/60/90/120/150gg d.f.f.m.', -1, '120', 20),
(101, 'Bonifico 30/60/90/120/150gg d.f.f.m.', -1, '150', 20),
(102, 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', -1, '30', 16),
(103, 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', -1, '60', 16),
(104, 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', -1, '90', 16),
(105, 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', -1, '120', 16),
(106, 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', -1, '150', 16),
(107, 'Bonifico 30/60/90/120/150/180gg d.f.f.m.', -1, '180', 20),
(108, 'Cambiale', 0, '0', 100),
(109, 'Assegno', 0, '0', 100),
(110, 'Bancomat', 0, '0', 100),
(111, 'Contanti', 0, '0', 100),
(112, 'Visa', 0, '0', 100);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_pianodeiconti1`
--

CREATE TABLE IF NOT EXISTS `co_pianodeiconti1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `descrizione` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_pianodeiconti1`
--

INSERT INTO `co_pianodeiconti1` (`id`, `numero`, `descrizione`) VALUES
(1, '01', 'Patrimoniale'),
(2, '02', 'Economico');

-- --------------------------------------------------------

--
-- Struttura della tabella `co_pianodeiconti2`
--

CREATE TABLE IF NOT EXISTS `co_pianodeiconti2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `descrizione` varchar(100) NOT NULL,
  `idpianodeiconti1` int(11) NOT NULL,
  `dir` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_pianodeiconti2`
--

INSERT INTO `co_pianodeiconti2` (`id`, `numero`, `descrizione`, `idpianodeiconti1`, `dir`) VALUES
(1, '100', 'Cassa e banche', 1, ''),
(2, '110', 'Crediti clienti e crediti diversi', 1, ''),
(3, '120', 'Effetti attivi', 1, ''),
(4, '130', 'Ratei e risconti attivi', 1, ''),
(5, '200', 'Erario iva, INPS, IRPEF, INAIL, ecc', 1, ''),
(6, '220', 'Immobilizzazioni', 1, ''),
(7, '230', 'Rimanente magazzino', 1, ''),
(8, '240', 'Debiti fornitori e debiti diversi', 1, ''),
(9, '250', 'Ratei e risconti passivi', 1, ''),
(10, '300', 'Fondi ammortamento', 1, ''),
(11, '310', 'Altri fondi', 1, ''),
(12, '400', 'Capitale', 1, ''),
(14, '600', 'Costi merci c/acquisto', 2, 'uscita'),
(15, '610', 'Costi generali', 2, 'uscita'),
(16, '620', 'Costi diversi', 2, ''),
(17, '630', 'Costi del personale', 2, ''),
(18, '640', 'Costi ammortamenti', 2, ''),
(19, '650', 'Costi accantonamenti', 2, ''),
(20, '700', 'Ricavi', 2, 'entrata'),
(21, '810', 'Perdite e profitti', 2, ''),
(22, '900', 'Conti transitori', 2, '');

-- --------------------------------------------------------

--
-- Struttura della tabella `co_pianodeiconti3`
--

CREATE TABLE IF NOT EXISTS `co_pianodeiconti3` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `descrizione` varchar(100) NOT NULL,
  `idpianodeiconti2` int(11) NOT NULL,
  `dir` varchar(15) NOT NULL,
  `can_delete` tinyint(1) NOT NULL,
  `can_edit` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_pianodeiconti3`
--

INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `can_delete`, `can_edit`) VALUES
(1, '000010', 'Cassa', 1, '', 0, 0),
(2, '000020', 'Banca C/C', 1, '', 0, 0),
(3, '000030', 'Effetti in portafoglio', 1, '', 0, 0),
(4, '000040', 'Banca effetti all''incasso', 1, '', 0, 0),
(5, '000050', 'Titoli', 1, '', 0, 0),
(6, '000010', 'Riepilogativo clienti', 2, '', 0, 0),
(7, '000030', 'Clienti per fatture da emettere', 2, '', 0, 0),
(8, '000040', 'Crediti imposte', 2, '', 0, 0),
(9, '000050', 'Crediti diversi', 2, '', 0, 0),
(10, '000060', 'Ri.Ba in portafoglio', 2, '', 0, 0),
(11, '000080', 'Dipendenti c/stipendi', 2, '', 0, 0),
(12, '000090', 'Amministratori c/emolumenti', 2, '', 0, 0),
(13, '000010', 'Effetti allo sconto', 3, '', 0, 0),
(14, '000020', 'Effetti all''incasso', 3, '', 0, 0),
(15, '000030', 'Effetti insoluti', 3, '', 0, 0),
(16, '000010', 'Risconti attivi', 4, '', 0, 0),
(17, '000020', 'Ratei attivi', 4, '', 0, 0),
(18, '000005', 'Erario c/to iva', 5, '', 0, 0),
(19, '000010', 'Erario c/INPS', 5, '', 0, 0),
(20, '000030', 'Erario c/IRPEF', 5, '', 0, 0),
(21, '000040', 'Erario c/INAIL', 5, '', 0, 0),
(22, '000050', 'Erario c/acconto TFR', 5, '', 0, 0),
(23, '000060', 'Erario c/ritenute d''acconto', 5, '', 0, 0),
(24, '000070', 'Erario c/enasarco', 5, '', 0, 0),
(25, '000080', 'Erario c/varie', 5, '', 0, 0),
(26, '000010', 'Fabbricati', 6, '', 1, 0),
(27, '000020', 'Mobili e macchine da ufficio', 6, '', 0, 0),
(28, '000030', 'Automezzi', 6, '', 0, 0),
(29, '000040', 'Impianti e attrezzature', 6, '', 0, 0),
(30, '000060', 'Manutenzione da ammortizzare', 6, '', 0, 0),
(31, '000070', 'Costi pluriennali', 6, '', 0, 0),
(32, '000010', 'Merci c/to rimanenze materie prime', 7, '', 0, 0),
(33, '000020', 'Merci c/to rimanenze semilavorati', 7, '', 0, 0),
(34, '000010', 'Riepilogativo fornitori', 8, '', 0, 0),
(35, '000020', 'Cambiali passive', 8, '', 0, 0),
(36, '000030', 'Mutui passivi', 8, '', 0, 0),
(37, '000040', 'Debiti verso banche', 8, '', 0, 0),
(38, '000050', 'Fornitori per fatture da ricevere', 8, '', 0, 0),
(39, '000060', 'Debiti diversi', 8, '', 0, 0),
(40, '000070', 'Finanziamenti vari', 8, '', 0, 0),
(41, '000200', 'Riepilogativo fornitori contabilit&agrave; semplificata', 8, '', 0, 0),
(42, '000010', 'Risconti passivi', 9, '', 0, 0),
(43, '000020', 'Ratei passivi', 9, '', 0, 0),
(44, '000010', 'Fondi ammortamento fabbricati', 10, '', 0, 0),
(45, '000020', 'Fondi ammortamento mobili e macchine da ufficio', 10, '', 0, 0),
(46, '000030', 'Fondi ammortamento automezzi', 10, '', 0, 0),
(47, '000040', 'Fondi ammortamento impianti e attrezzature', 10, '', 0, 0),
(48, '000060', 'Fondi ammortamento manutenzione da ammortizzare', 10, '', 0, 0),
(49, '000070', 'Fondi ammortamento costi pluriennali', 10, '', 0, 0),
(50, '000010', 'Fondo imposte e tasse', 11, '', 0, 0),
(51, '000020', 'Fondo TFR liquidazione personale', 11, '', 0, 0),
(52, '000010', 'Capitale sociale o netto', 12, '', 0, 0),
(53, '000020', 'Riserve', 12, '', 0, 0),
(55, '000010', 'Costi merci c/acquisto di rivendita', 14, 'uscita', 0, 0),
(56, '000020', 'Costi merci c/acquisto di produzione', 14, 'uscita', 0, 0),
(57, '000030', 'Costi merci c/acquisto intracomunitario', 14, 'uscita', 0, 0),
(58, '000040', 'Costi merci c/acquisto importazioni', 14, 'uscita', 0, 0),
(59, '000010', 'Spese telefoniche', 15, 'uscita', 1, 1),
(60, '000020', 'Spese postali', 15, 'uscita', 1, 1),
(61, '000030', 'Spese cancelleria', 15, 'uscita', 1, 1),
(62, '000040', 'Spese locomozione e carburante', 15, 'uscita', 0, 0),
(63, '000050', 'Spese software', 15, 'uscita', 1, 1),
(64, '000060', 'Spese energia elettrica', 15, 'uscita', 1, 1),
(65, '000070', 'Spese consulenze', 15, 'uscita', 1, 1),
(66, '000080', 'Spese varie', 15, 'uscita', 1, 1),
(67, '000090', 'Spese assicurazioni', 15, 'uscita', 1, 1),
(68, '000100', 'Spese bancarie', 15, 'uscita', 1, 1),
(69, '000110', 'Spese fitti passivi', 15, 'uscita', 1, 1),
(70, '000120', 'Spese ristoranti e alberghi', 15, 'uscita', 1, 1),
(71, '000130', 'Spese manutenzione e riparazione', 15, 'uscita', 1, 1),
(72, '000140', 'Spese canoni leasing', 15, 'uscita', 1, 1),
(73, '000150', 'Spese acquisto beni strumentali non ammortizzabilii', 15, 'uscita', 0, 0),
(74, '000010', 'Costi interessi passivi', 16, '', 0, 0),
(75, '000020', 'Costi abbuoni passivi', 16, '', 0, 0),
(76, '000030', 'Costi imposte e tasse', 16, '', 0, 0),
(77, '000040', 'Costi imposta IRA', 16, '', 0, 0),
(78, '000050', 'Costi minusvalenze', 16, '', 0, 0),
(79, '000060', 'Costi perdite su crediti', 16, '', 0, 0),
(80, '000070', 'Costi sopravvenienze passive', 16, '', 0, 0),
(81, '000080', 'Costi perdite da operazioni finanziarie', 16, '', 0, 0),
(82, '000010', 'Costi salari e stipendi', 17, '', 0, 0),
(83, '000020', 'Costi contributi sociali', 17, '', 0, 0),
(84, '000040', 'Costi TFR', 17, '', 0, 0),
(85, '000050', 'Costi contributi dipendenti', 17, '', 0, 0),
(86, '000060', 'Costi contributi assicurazione lavoro', 17, '', 0, 0),
(87, '000010', 'Ammortamento fabbricati', 18, '', 0, 0),
(88, '000020', 'Ammortamento mobili e macchine ufficio', 18, '', 0, 0),
(89, '000030', 'Ammortamento automezzi', 18, '', 0, 0),
(90, '000040', 'Ammortamento impianti e attrezzature', 18, '', 0, 0),
(91, '000060', 'Ammortamento manutenzioni', 18, '', 0, 0),
(92, '000070', 'Ammortamento costi pluriennali', 18, '', 0, 0),
(93, '000010', 'Accantonamento TFR', 19, '', 0, 0),
(94, '000010', 'Ricavi merci c/to vendite', 20, 'entrata', 0, 0),
(95, '000020', 'Ricavi vendita prestazione servizi', 20, 'entrata', 0, 0),
(96, '000030', 'Ricavi interessi attivi', 20, 'entrata', 0, 0),
(97, '000040', 'Ricavi fitti attivi', 20, 'entrata', 0, 0),
(98, '000050', 'Ricavi vari', 20, 'entrata', 0, 0),
(99, '000051', 'Rimborso spese marche da bollo', 20, '', 0, 0),
(100, '000060', 'Ricavi abbuoni attivi', 20, '', 0, 0),
(101, '000070', 'Ricavi sopravvenienze attive', 20, '', 0, 0),
(102, '000080', 'Ricavi plusvalenze', 20, '', 0, 0),
(103, '000020', 'Perdite e profitti', 21, '', 0, 0),
(104, '000010', 'Apertura conti patrimoniali', 21, '', 0, 0),
(105, '000900', 'Chiusura conti patrimoniali', 21, '', 0, 0),
(106, '000010', 'Iva su vendite', 22, '', 0, 0),
(107, '000020', 'Iva su acquisti', 22, '', 0, 0),
(108, '000030', 'Iva indetraibile', 22, '', 0, 0),
(109, '000200', 'Intra UE: riepilogativo fornitori', 22, '', 0, 0),
(110, '000210', 'Intra UE: transitorio iva', 22, '', 0, 0),
(111, '000220', 'Intra UE: transitorio per movimento iva', 22, '', 0, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_preventivi`
--

CREATE TABLE IF NOT EXISTS `co_preventivi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `idagente` int(11) NOT NULL,
  `data_bozza` datetime NOT NULL,
  `data_accettazione` datetime NOT NULL,
  `data_rifiuto` datetime NOT NULL,
  `data_conclusione` datetime NOT NULL,
  `data_pagamento` datetime NOT NULL,
  `budget` float(12,4) NOT NULL,
  `descrizione` text NOT NULL,
  `idstato` tinyint(4) NOT NULL,
  `validita` int(11) NOT NULL,
  `tempi_consegna` varchar(255) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `esclusioni` text NOT NULL,
  `idreferente` int(11) NOT NULL,
  `idpagamento` int(11) NOT NULL,
  `idporto` int(11) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `costo_diritto_chiamata` float(12,4) NOT NULL,
  `ore_lavoro` float(12,4) NOT NULL,
  `costo_orario` float(12,4) NOT NULL,
  `costo_km` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_preventivi_interventi`
--

CREATE TABLE IF NOT EXISTS `co_preventivi_interventi` (
  `idpreventivo` int(11) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  `costo_orario` float(12,4) NOT NULL,
  `costo_km` float(12,4) NOT NULL,
  PRIMARY KEY (`idpreventivo`,`idintervento`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_righe2_contratti`
--

CREATE TABLE IF NOT EXISTS `co_righe2_contratti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcontratto` int(11) NOT NULL,
  `descrizione` text NOT NULL,
  `subtotale` float(12,4) NOT NULL,
  `um` varchar(20) NOT NULL,
  `qta` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_righe_contratti`
--

CREATE TABLE IF NOT EXISTS `co_righe_contratti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcontratto` int(11) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `data_richiesta` datetime NOT NULL,
  `richiesta` varchar(8000) NOT NULL,
  `idsede` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_righe_documenti`
--

CREATE TABLE IF NOT EXISTS `co_righe_documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iddocumento` int(11) NOT NULL,
  `idordine` int(11) NOT NULL,
  `idddt` int(11) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  `idarticolo` int(11) NOT NULL,
  `idpreventivo` int(11) NOT NULL,
  `idcontratto` int(11) NOT NULL,
  `idtecnico` int(11) NOT NULL,
  `idagente` int(11) NOT NULL,
  `idautomezzo` int(11) NOT NULL,
  `idiva` int(11) NOT NULL,
  `desc_iva` varchar(255) NOT NULL,
  `iva` float(12,4) NOT NULL,
  `iva_indetraibile` float(12,4) NOT NULL,
  `descrizione` text NOT NULL,
  `subtotale` float(12,4) NOT NULL,
  `sconto` float(12,4) NOT NULL,
  `um` varchar(20) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `lotto` varchar(50) NOT NULL,
  `serial` varchar(50) NOT NULL,
  `altro` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_righe_preventivi`
--

CREATE TABLE IF NOT EXISTS `co_righe_preventivi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_evasione` datetime NOT NULL,
  `idpreventivo` int(11) NOT NULL,
  `idarticolo` int(11) NOT NULL,
  `idiva` int(11) NOT NULL,
  `iva` float(12,4) NOT NULL,
  `iva_indetraibile` float(12,4) NOT NULL,
  `descrizione` text NOT NULL,
  `lotto` varchar(50) NOT NULL,
  `serial` varchar(50) NOT NULL,
  `altro` varchar(50) NOT NULL,
  `subtotale` float(12,4) NOT NULL,
  `um` varchar(20) NOT NULL,
  `qta` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_ritenutaacconto`
--

CREATE TABLE IF NOT EXISTS `co_ritenutaacconto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `percentuale` float(5,2) NOT NULL,
  `indetraibile` float(5,2) NOT NULL,
  `esente` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_ritenutaacconto`
--

INSERT INTO `co_ritenutaacconto` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`) VALUES
(1, 'Ritenuta d''acconto 20%', 20.00, 0.00, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_rivalsainps`
--

CREATE TABLE IF NOT EXISTS `co_rivalsainps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `percentuale` float(5,2) NOT NULL,
  `indetraibile` float(5,2) NOT NULL,
  `esente` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_rivalsainps`
--

INSERT INTO `co_rivalsainps` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`) VALUES
(1, 'Rivalsa INPS 4%', 4.00, 0.00, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_scadenziario`
--

CREATE TABLE IF NOT EXISTS `co_scadenziario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iddocumento` int(11) NOT NULL,
  `data_emissione` datetime NOT NULL,
  `scadenza` datetime NOT NULL,
  `da_pagare` float(12,4) DEFAULT NULL,
  `pagato` float(12,4) DEFAULT NULL,
  `data_pagamento` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `co_staticontratti`
--

CREATE TABLE IF NOT EXISTS `co_staticontratti` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) DEFAULT NULL,
  `icona` varchar(255) NOT NULL,
  `completato` tinyint(1) NOT NULL DEFAULT '0',
  `annullato` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_staticontratti`
--

INSERT INTO `co_staticontratti` (`id`, `descrizione`, `icona`, `completato`, `annullato`) VALUES
(1, 'Bozza', 'fa fa-2x fa-file-text-o text-muted', 0, 0),
(2, 'In attesa di conferma', 'fa fa-2x fa-clock-o text-warning', 0, 0),
(3, 'Accettato', 'fa fa-2x fa-thumbs-up text-success', 0, 0),
(4, 'Rifiutato', 'fa fa-2x fa-thumbs-down text-danger', 0, 1),
(5, 'In lavorazione', 'fa fa-2x fa-gear text-warning', 1, 0),
(6, 'In attesa di pagamento', 'fa fa-2x fa-money text-primary', 0, 0),
(7, 'Pagato', 'fa fa-2x fa-check-circle text-success', 0, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_statidocumento`
--

CREATE TABLE IF NOT EXISTS `co_statidocumento` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `icona` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_statidocumento`
--

INSERT INTO `co_statidocumento` (`id`, `descrizione`, `icona`) VALUES
(1, 'Pagato', 'fa fa-2x fa-check-circle text-success'),
(2, 'Bozza', 'fa fa-2x fa-file-text-o text-muted'),
(3, 'Emessa', 'fa fa-2x fa-clock-o text-info'),
(4, 'Annullata', 'fa fa-2x fa-times text-danger');

-- --------------------------------------------------------

--
-- Struttura della tabella `co_statipreventivi`
--

CREATE TABLE IF NOT EXISTS `co_statipreventivi` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `icona` varchar(255) NOT NULL,
  `completato` tinyint(1) NOT NULL DEFAULT '0',
  `annullato` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_statipreventivi`
--

INSERT INTO `co_statipreventivi` (`id`, `descrizione`, `icona`, `completato`, `annullato`) VALUES
(1, 'Bozza', 'fa fa-2x fa-file-text-o text-muted', 0, 0),
(2, 'In attesa di conferma', 'fa fa-2x fa-clock-o text-warning', 0, 0),
(3, 'Accettato', 'fa fa-2x fa-thumbs-up text-success', 0, 0),
(4, 'Rifiutato', 'fa fa-2x fa-thumbs-down text-danger', 0, 1),
(5, 'In lavorazione', 'fa fa-2x fa-gear text-warning', 1, 0),
(6, 'Concluso', 'fa fa-2x fa-check text-success', 0, 0),
(7, 'Pagato', 'fa fa-2x fa-check-circle text-success', 0, 0),
(8, 'In attesa di pagamento', 'fa fa-2x fa-money text-primary', 0, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `co_tipidocumento`
--

CREATE TABLE IF NOT EXISTS `co_tipidocumento` (
  `id` tinyint(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `dir` enum('entrata','uscita') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `co_tipidocumento`
--

INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`) VALUES
(1, 'Fattura immediata di acquisto', 'uscita'),
(2, 'Fattura immediata di vendita', 'entrata'),
(3, 'Fattura differita di acquisto', 'uscita'),
(4, 'Fattura differita di vendita', 'entrata'),
(5, 'Fattura accompagnatoria di acquisto', 'uscita'),
(6, 'Fattura accompagnatoria di vendita', 'entrata'),
(7, 'Nota di accredito', 'uscita'),
(8, 'Nota di addebito', 'uscita'),
(9, 'Nota di accredito', 'entrata'),
(10, 'Nota di addebito', 'entrata');

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_aspettobeni`
--

CREATE TABLE IF NOT EXISTS `dt_aspettobeni` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `dt_aspettobeni`
--

INSERT INTO `dt_aspettobeni` (`id`, `descrizione`) VALUES
(1, 'A vista'),
(2, 'Cartoni'),
(3, 'Sacchi'),
(4, 'Scatola');

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_automezzi`
--

CREATE TABLE IF NOT EXISTS `dt_automezzi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) NOT NULL,
  `descrizione` varchar(1000) NOT NULL,
  `targa` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_automezzi_tagliandi`
--

CREATE TABLE IF NOT EXISTS `dt_automezzi_tagliandi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idautomezzo` int(11) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `km` int(11) NOT NULL,
  `data_emissione` datetime NOT NULL,
  `validita` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_automezzi_tecnici`
--

CREATE TABLE IF NOT EXISTS `dt_automezzi_tecnici` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idautomezzo` int(11) NOT NULL,
  `idtecnico` int(11) NOT NULL,
  `data_inizio` datetime NOT NULL,
  `data_fine` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_causalet`
--

CREATE TABLE IF NOT EXISTS `dt_causalet` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `dt_causalet`
--

INSERT INTO `dt_causalet` (`id`, `descrizione`) VALUES
(1, 'Vendita'),
(2, 'Noleggio'),
(3, 'Reso');

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_ddt`
--

CREATE TABLE IF NOT EXISTS `dt_ddt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL,
  `numero_esterno` varchar(100) NOT NULL,
  `data` datetime NOT NULL,
  `idagente` int(11) NOT NULL,
  `idiva` int(11) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idcausalet` int(11) NOT NULL,
  `idspedizione` tinyint(4) NOT NULL,
  `idporto` tinyint(4) NOT NULL,
  `idaspettobeni` tinyint(4) NOT NULL,
  `idvettore` int(11) NOT NULL,
  `idtipoddt` tinyint(4) NOT NULL,
  `idstatoddt` tinyint(4) NOT NULL,
  `idpagamento` int(11) NOT NULL,
  `idconto` int(11) NOT NULL,
  `idrivalsainps` int(11) NOT NULL,
  `idritenutaacconto` int(11) NOT NULL,
  `idsede` int(11) NOT NULL,
  `rivalsainps` float(12,4) NOT NULL,
  `iva_rivalsainps` float(12,4) NOT NULL,
  `ritenutaacconto` float(12,4) NOT NULL,
  `bollo` float(12,4) NOT NULL,
  `n_colli` int(11) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_porto`
--

CREATE TABLE IF NOT EXISTS `dt_porto` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `dt_porto`
--

INSERT INTO `dt_porto` (`id`, `descrizione`) VALUES
(1, 'Franco'),
(2, 'Assegnato');

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_righe_ddt`
--

CREATE TABLE IF NOT EXISTS `dt_righe_ddt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idddt` int(11) NOT NULL,
  `idordine` int(11) NOT NULL,
  `idarticolo` int(11) NOT NULL,
  `idiva` int(11) NOT NULL,
  `iva` float(12,4) NOT NULL,
  `iva_indetraibile` float(12,4) NOT NULL,
  `descrizione` text NOT NULL,
  `lotto` varchar(50) NOT NULL,
  `serial` varchar(50) NOT NULL,
  `altro` varchar(50) NOT NULL,
  `subtotale` float(12,4) NOT NULL,
  `sconto` float(12,4) NOT NULL,
  `um` varchar(20) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `qta_evasa` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_spedizione`
--

CREATE TABLE IF NOT EXISTS `dt_spedizione` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `esterno` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `dt_spedizione`
--

INSERT INTO `dt_spedizione` (`id`, `descrizione`, `esterno`) VALUES
(1, 'A nostro carico', 0),
(2, 'Vettore', 1),
(3, 'A carico del cliente', 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_statiddt`
--

CREATE TABLE IF NOT EXISTS `dt_statiddt` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `icona` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `dt_statiddt`
--

INSERT INTO `dt_statiddt` (`id`, `descrizione`, `icona`) VALUES
(1, 'Bozza', 'fa fa-2x fa-file-text-o text-muted'),
(2, 'Evaso', 'fa fa-2x fa-clock-o text-info'),
(3, 'Pagato', 'fa fa-2x fa-check-circle text-success');

-- --------------------------------------------------------

--
-- Struttura della tabella `dt_tipiddt`
--

CREATE TABLE IF NOT EXISTS `dt_tipiddt` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) DEFAULT NULL,
  `dir` enum('entrata','uscita') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `dt_tipiddt`
--

INSERT INTO `dt_tipiddt` (`id`, `descrizione`, `dir`) VALUES
(1, 'Ddt di acquisto', 'uscita'),
(2, 'Ddt di vendita', 'entrata');

-- --------------------------------------------------------

--
-- Struttura della tabella `in_interventi`
--

CREATE TABLE IF NOT EXISTS `in_interventi` (
  `idintervento` varchar(25) NOT NULL,
  `data_richiesta` datetime NOT NULL,
  `richiesta` text NOT NULL,
  `descrizione` text NOT NULL,
  `km` float(7,2) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `nomefile` varchar(255) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idreferente` int(11) NOT NULL,
  `idstatointervento` varchar(10) NOT NULL,
  `informazioniaggiuntive` text NOT NULL,
  `prezzo_ore_unitario` float(10,2) NOT NULL,
  `idsede` int(11) NOT NULL,
  `idautomezzo` int(11) NOT NULL,
  `idclientefinale` int(11) NOT NULL,
  `info_sede` varchar(255) NOT NULL,
  `data_sla` date NOT NULL,
  `ora_sla` time NOT NULL,
  PRIMARY KEY (`idintervento`),
  KEY `in_interventi_ibfk_1` (`idanagrafica`),
  KEY `in_interventi_ibfk_2` (`idtipointervento`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `in_interventi_tecnici`
--

CREATE TABLE IF NOT EXISTS `in_interventi_tecnici` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idintervento` varchar(25) NOT NULL,
  `idtecnico` int(11) NOT NULL,
  `orario_inizio` datetime NOT NULL,
  `orario_fine` datetime NOT NULL,
  `km` float(12,4) NOT NULL,
  `prezzo_ore_unitario` float(12,4) NOT NULL,
  `prezzo_km_unitario` float(12,4) NOT NULL,
  `prezzo_ore_consuntivo` float(12,4) NOT NULL,
  `prezzo_km_consuntivo` float(12,4) NOT NULL,
  `prezzo_dirittochiamata` float(12,4) NOT NULL,
  `prezzo_ore_unitario_tecnico` float(12,4) NOT NULL,
  `prezzo_km_unitario_tecnico` float(12,4) NOT NULL,
  `prezzo_ore_consuntivo_tecnico` float(12,4) NOT NULL,
  `prezzo_km_consuntivo_tecnico` float(12,4) NOT NULL,
  `prezzo_dirittochiamata_tecnico` float(12,4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `in_interventi_tecnici_ibfk_1` (`idintervento`),
  KEY `in_interventi_tecnici_ibfk_2` (`idtecnico`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `in_righe_interventi`
--

CREATE TABLE IF NOT EXISTS `in_righe_interventi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `um` varchar(25) NOT NULL,
  `prezzo` float(12,4) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `in_statiintervento`
--

CREATE TABLE IF NOT EXISTS `in_statiintervento` (
  `idstatointervento` varchar(10) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `colore` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `default` tinyint(1) NOT NULL,
  `completato` tinyint(1) NOT NULL,
  PRIMARY KEY (`idstatointervento`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `in_statiintervento`
--

INSERT INTO `in_statiintervento` (`idstatointervento`, `descrizione`, `colore`, `default`, `completato`) VALUES
('CALL', 'Chiamata', '#96c0ff', 1, 0),
('FAT', 'Fatturato', '#55FF55', 1, 0),
('OK', 'Completato', '#a3ff82', 1, 1),
('WIP', 'In programmazione', '#ffc400', 1, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `in_tariffe`
--

CREATE TABLE IF NOT EXISTS `in_tariffe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idtecnico` int(11) NOT NULL,
  `idtipointervento` varchar(25) NOT NULL,
  `costo_ore` float(12,4) NOT NULL,
  `costo_km` float(12,4) NOT NULL,
  `costo_dirittochiamata` float(12,4) NOT NULL,
  `costo_ore_tecnico` float(12,4) NOT NULL,
  `costo_km_tecnico` float(12,4) NOT NULL,
  `costo_dirittochiamata_tecnico` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `in_tipiintervento`
--

CREATE TABLE IF NOT EXISTS `in_tipiintervento` (
  `idtipointervento` varchar(25) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `costo_orario` float(12,4) NOT NULL,
  `costo_km` float(12,4) NOT NULL,
  `costo_diritto_chiamata` float(12,4) NOT NULL,
  `costo_orario_tecnico` float(12,4) NOT NULL,
  `costo_km_tecnico` float(12,4) NOT NULL,
  `costo_diritto_chiamata_tecnico` float(12,4) NOT NULL,
  PRIMARY KEY (`idtipointervento`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `in_tipiintervento`
--

INSERT INTO `in_tipiintervento` (`idtipointervento`, `descrizione`, `costo_orario`, `costo_km`, `costo_diritto_chiamata`, `costo_orario_tecnico`, `costo_km_tecnico`, `costo_diritto_chiamata_tecnico`) VALUES
('GEN', 'Generico', 30.0000, 0.5000, 0.0000, 0.0000, 0.0000, 0.0000),
('ODS', 'Ordine di servizio', 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000);

-- --------------------------------------------------------

--
-- Struttura della tabella `in_vociservizio`
--

CREATE TABLE IF NOT EXISTS `in_vociservizio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `categoria` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `in_vociservizio`
--

INSERT INTO `in_vociservizio` (`id`, `descrizione`, `categoria`) VALUES
(1, 'Manutenzione programmata', 'Intervento generico');

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_articoli`
--

CREATE TABLE IF NOT EXISTS `mg_articoli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codice` varchar(255) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `idum` tinyint(11) NOT NULL,
  `categoria` varchar(255) NOT NULL,
  `subcategoria` varchar(255) NOT NULL,
  `immagine01` varchar(255) NOT NULL,
  `note` varchar(1000) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `threshold_qta` float(12,4) NOT NULL,
  `prezzo_acquisto` float(12,4) NOT NULL,
  `prezzo_vendita` float(12,4) NOT NULL,
  `idiva_vendita` int(11) NOT NULL,
  `gg_garanzia` int(11) NOT NULL,
  `componente_filename` varchar(255) NOT NULL,
  `contenuto` text NOT NULL,
  `attivo` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_articoli_automezzi`
--

CREATE TABLE IF NOT EXISTS `mg_articoli_automezzi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idarticolo` int(11) NOT NULL,
  `idautomezzo` int(11) NOT NULL,
  `qta` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_articoli_interventi`
--

CREATE TABLE IF NOT EXISTS `mg_articoli_interventi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idarticolo` int(11) NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  `matricola` varchar(25) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `lotto` varchar(50) NOT NULL,
  `serial` varchar(50) NOT NULL,
  `altro` varchar(50) NOT NULL,
  `prezzo_vendita` float(12,4) NOT NULL,
  `sconto` float(12,4) NOT NULL,
  `idiva_vendita` float(10,2) NOT NULL,
  `idautomezzo` int(11) NOT NULL,
  `qta` float(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_listini`
--

CREATE TABLE IF NOT EXISTS `mg_listini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `prc_guadagno` float(5,2) NOT NULL,
  `note` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_movimenti`
--

CREATE TABLE IF NOT EXISTS `mg_movimenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idarticolo` int(11) NOT NULL,
  `descrizione_articolo` varchar(255) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `movimento` varchar(255) NOT NULL,
  `data` datetime NOT NULL,
  `idintervento` varchar(25) NOT NULL,
  `idddt` int(11) NOT NULL,
  `iddocumento` int(11) NOT NULL,
  `idautomezzo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_prodotti`
--

CREATE TABLE IF NOT EXISTS `mg_prodotti` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `idarticolo` int(11) NOT NULL,
  `lotto` varchar(50) NOT NULL,
  `serial` varchar(50) NOT NULL,
  `altro` varchar(50) NOT NULL,
  `data` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mg_unitamisura`
--

CREATE TABLE IF NOT EXISTS `mg_unitamisura` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `valore` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `mg_unitamisura`
--

INSERT INTO `mg_unitamisura` (`id`, `valore`) VALUES
(1, 'nr'),
(2, 'kg'),
(3, 'pz'),
(4, 'litri'),
(5, 'ore');

-- --------------------------------------------------------

--
-- Struttura della tabella `mk_allegati`
--

CREATE TABLE IF NOT EXISTS `mk_allegati` (
  `idallegato` int(11) NOT NULL AUTO_INCREMENT,
  `idcliente` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  PRIMARY KEY (`idallegato`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mk_attivita`
--

CREATE TABLE IF NOT EXISTS `mk_attivita` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstato` int(11) NOT NULL,
  `data` date NOT NULL,
  `ora_dal` time NOT NULL,
  `ora_al` time NOT NULL,
  `infogiorno` varchar(255) NOT NULL,
  `descrizione` text NOT NULL,
  `idagente` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `idreferente` int(11) NOT NULL,
  `luogo` varchar(500) NOT NULL,
  `idtipo` int(11) NOT NULL,
  `datanotifica` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mk_email`
--

CREATE TABLE IF NOT EXISTS `mk_email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_invio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `da` varchar(255) NOT NULL,
  `a` varchar(500) NOT NULL,
  `cc` varchar(500) NOT NULL,
  `bcc` varchar(500) NOT NULL,
  `oggetto` varchar(255) NOT NULL,
  `idallegato` varchar(255) NOT NULL,
  `confermalettura` tinyint(1) NOT NULL,
  `confermarecapito` tinyint(1) NOT NULL,
  `testo` text NOT NULL,
  `presentazione` tinyint(1) NOT NULL,
  `idcliente` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `mk_statoattivita`
--

CREATE TABLE IF NOT EXISTS `mk_statoattivita` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `colore` varchar(7) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `mk_statoattivita`
--

INSERT INTO `mk_statoattivita` (`id`, `descrizione`, `colore`) VALUES
(1, 'Completato', '#a3ff82'),
(2, 'In programmazione', '#ffc400'),
(3, 'Annullato', '#fa6161');

-- --------------------------------------------------------

--
-- Struttura della tabella `mk_tipoattivita`
--

CREATE TABLE IF NOT EXISTS `mk_tipoattivita` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `mk_tipoattivita`
--

INSERT INTO `mk_tipoattivita` (`id`, `descrizione`) VALUES
(1, 'Appuntamento'),
(2, 'Chiamata');

-- --------------------------------------------------------

--
-- Struttura della tabella `my_impianti`
--

CREATE TABLE IF NOT EXISTS `my_impianti` (
  `matricola` varchar(25) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descrizione` varchar(5000) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idsede` int(11) NOT NULL,
  `data` date NOT NULL,
  `idtecnico` int(11) NOT NULL,
  `ubicazione` varchar(255) NOT NULL,
  `scala` varchar(50) NOT NULL,
  `piano` varchar(50) NOT NULL,
  `occupante` varchar(255) NOT NULL,
  `proprietario` varchar(255) NOT NULL,
  `palazzo` varchar(255) NOT NULL,
  `interno` varchar(255) NOT NULL,
  `immagine` varchar(255) NOT NULL,
  PRIMARY KEY (`matricola`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `my_impianti_interventi`
--

CREATE TABLE IF NOT EXISTS `my_impianti_interventi` (
  `idintervento` varchar(25) NOT NULL,
  `matricola` varchar(25) NOT NULL,
  PRIMARY KEY (`idintervento`,`matricola`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `my_impianto_componenti`
--

CREATE TABLE IF NOT EXISTS `my_impianto_componenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idsostituto` int(11) NOT NULL,
  `matricola` varchar(25) NOT NULL,
  `idintervento` varchar(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data` datetime NOT NULL,
  `data_sostituzione` datetime NOT NULL,
  `filename` varchar(255) NOT NULL,
  `contenuto` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `or_ordini`
--

CREATE TABLE IF NOT EXISTS `or_ordini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(100) NOT NULL,
  `numero_esterno` varchar(100) NOT NULL,
  `data` datetime NOT NULL,
  `idagente` int(11) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idsede` int(11) NOT NULL,
  `idtipoordine` tinyint(4) NOT NULL,
  `idstatoordine` tinyint(4) NOT NULL,
  `idpagamento` int(11) NOT NULL,
  `idconto` int(11) NOT NULL,
  `idrivalsainps` int(11) NOT NULL,
  `idritenutaacconto` int(11) NOT NULL,
  `rivalsainps` float(12,4) NOT NULL,
  `iva_rivalsainps` float(12,4) NOT NULL,
  `ritenutaacconto` float(12,4) NOT NULL,
  `bollo` float(10,2) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `or_righe_ordini`
--

CREATE TABLE IF NOT EXISTS `or_righe_ordini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_evasione` datetime NOT NULL,
  `idordine` int(11) NOT NULL,
  `idarticolo` int(11) NOT NULL,
  `idiva` int(11) NOT NULL,
  `idagente` int(11) NOT NULL,
  `iva` float(12,4) NOT NULL,
  `iva_indetraibile` float(12,4) NOT NULL,
  `descrizione` text NOT NULL,
  `lotto` varchar(50) NOT NULL,
  `serial` varchar(50) NOT NULL,
  `altro` varchar(50) NOT NULL,
  `subtotale` float(12,4) NOT NULL,
  `sconto` float(12,4) NOT NULL,
  `um` varchar(20) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `qta_evasa` float(12,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `or_statiordine`
--

CREATE TABLE IF NOT EXISTS `or_statiordine` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `annullato` tinyint(1) NOT NULL,
  `icona` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `or_statiordine`
--

INSERT INTO `or_statiordine` (`id`, `descrizione`, `annullato`, `icona`) VALUES
(1, 'Non evaso', 0, 'fa fa-2x fa-file-text-o text-muted'),
(2, 'Evaso', 1, 'fa fa-2x fa-check-circle text-success'),
(3, 'Parzialmente evaso', 1, 'fa fa-2x fa-gear text-warning');

-- --------------------------------------------------------

--
-- Struttura della tabella `or_tipiordine`
--

CREATE TABLE IF NOT EXISTS `or_tipiordine` (
  `id` tinyint(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `dir` enum('entrata','uscita') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `or_tipiordine`
--

INSERT INTO `or_tipiordine` (`id`, `descrizione`, `dir`) VALUES
(1, 'Ordine fornitore', 'uscita'),
(2, 'Ordine cliente', 'entrata');

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_files`
--

CREATE TABLE IF NOT EXISTS `zz_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` datetime NOT NULL,
  `nome` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `externalid` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_gruppi`
--

CREATE TABLE IF NOT EXISTS `zz_gruppi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `editable` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_gruppi`
--

INSERT INTO `zz_gruppi` (`id`, `nome`, `editable`) VALUES
(1, 'Amministratori', 0),
(2, 'Tecnici', 0),
(3, 'Agenti', 0),
(4, 'Clienti', 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_gruppi_modules`
--

CREATE TABLE IF NOT EXISTS `zz_gruppi_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idgruppo` int(11) NOT NULL,
  `idmodule` int(11) NOT NULL,
  `clause` varchar(5000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_gruppi_modules`
--

INSERT INTO `zz_gruppi_modules` (`id`, `idgruppo`, `idmodule`, `clause`) VALUES
(1, 2, 3, ' AND in_interventi.idintervento IN (SELECT idintervento FROM in_interventi_tecnici WHERE idintervento=in_interventi.idintervento AND idtecnico=|idtecnico|)'),
(2, 2, 2, ' AND an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.idintervento WHERE in_interventi.idanagrafica=an_anagrafiche.idanagrafica AND idtecnico=|idtecnico|)'),
(3, 3, 2, ' AND an_anagrafiche.idagente=|idagente|'),
(4, 4, 2, ' AND an_anagrafiche.idanagrafica=|idanagrafica|'),
(5, 4, 3, ' AND in_interventi.idanagrafica=|idanagrafica|'),
(6, 4, 14, ' AND co_documenti.idanagrafica=|idanagrafica|');

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_impostazioni`
--

CREATE TABLE IF NOT EXISTS `zz_impostazioni` (
  `idimpostazione` smallint(6) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `valore` varchar(50) NOT NULL,
  `tipo` varchar(1000) NOT NULL,
  `editable` tinyint(1) NOT NULL,
  `sezione` varchar(100) NOT NULL,
  PRIMARY KEY (`idimpostazione`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_impostazioni`
--

INSERT INTO `zz_impostazioni` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
(1, 'Righe per pagina', '20', 'integer', 1, 'Generali'),
(2, 'Azienda predefinita', '0', 'query=SELECT an_anagrafiche.idanagrafica, ragione_sociale FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE idtipoanagrafica=(SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione=''Azienda'') AND deleted=0', 1, 'Generali'),
(3, 'max_idintervento', '0', 'string', 0, 'Generali'),
(5, 'Formato report', 'pdf', 'list[html,pdf]', 1, 'Generali'),
(6, 'Iva predefinita', '91', 'query=SELECT id, descrizione FROM `co_iva` ORDER BY descrizione ASC', 1, 'Fatturazione'),
(7, 'Tipo di pagamento predefinito', '20', 'query=SELECT id, descrizione FROM `co_pagamenti` ORDER BY descrizione ASC', 1, 'Fatturazione'),
(8, 'Percentuale ritenuta d''acconto', '0', 'query=SELECT id, descrizione FROM `co_ritenutaacconto` ORDER BY descrizione ASC', 1, 'Fatturazione'),
(9, 'Percentuale rivalsa INPS', '0', 'query=SELECT id, descrizione FROM `co_rivalsainps` ORDER BY descrizione ASC', 1, 'Fatturazione'),
(10, 'Importo marca da bollo', '0.00', 'string', 1, 'Fatturazione'),
(11, 'Soglia minima per l''applicazione della marca da bollo', '77.47', 'string', 1, 'Fatturazione'),
(12, 'Conto aziendale predefinito', '', 'query=SELECT id,descrizione FROM co_pianodeiconti3 WHERE idpianodeiconti2=(SELECT id FROM co_pianodeiconti2 WHERE descrizione=''Cassa e banche'')', 1, 'Fatturazione'),
(13, 'Indirizzo per le email in uscita', '', 'string', 1, 'Email'),
(14, 'Server SMTP', 'localhost', 'string', 1, 'Email'),
(15, 'Username SMTP', '', 'string', 1, 'Email'),
(16, 'Password SMTP', '', 'string', 1, 'Email'),
(17, 'Visualizza i costi sulle stampe degli interventi', '1', 'boolean', 1, 'Interventi'),
(19, 'Stampa i prezzi sui ddt', '1', 'boolean', 1, 'Ddt'),
(20, 'Stampa i prezzi sugli ordini', '1', 'boolean', 1, 'Ordini'),
(21, 'Movimenta il magazzino durante l''inserimento o eliminazione dei lotti/serial number', '1', 'boolean', 1, 'Magazzino'),
(22, 'Formato numero secondario ddt', '##', 'string', 1, 'Ddt'),
(23, 'Formato numero secondario fattura', '##', 'string', 1, 'Fatturazione'),
(24, 'Formato numero secondario ordine', '##', 'string', 1, 'Ordini'),
(25, 'Formato codice intervento', '#', 'string', 1, 'Interventi'),
(26, 'Formato codice preventivi', '#', 'string', 1, 'Preventivi'),
(27, 'Stampa i prezzi sui preventivi', '1', 'boolean', 1, 'Preventivi'),
(28, 'Mostra i prezzi al tecnico', '1', 'boolean', 1, 'Interventi'),
(29, 'Formato codice anagrafica', '########', 'string', 1, 'Anagrafiche'),
(30, 'Numero di mesi prima da cui iniziare a visualizzare gli interventi', '12', 'integer', 1, 'Interventi'),
(31, 'Formato codice contratti', '#', 'string', 1, 'Contratti'),
(32, 'Stampa i prezzi sui contratti', '1', 'boolean', 1, 'Contratti'),
(33, 'osmcloud_username', '', 'string', 0, 'CLOUD'),
(34, 'osmcloud_password', '', 'string', 0, 'CLOUD'),
(35, 'osm_installed', '1', 'string', 0, 'INSTALL'),
(36, 'Conto predefinito fatture di vendita', '', 'query=SELECT id, CONCAT_WS('' - '', numero, descrizione) AS descrizione FROM co_pianodeiconti3 WHERE dir=''entrata''', 1, 'Fatturazione'),
(37, 'Conto predefinito fatture di acquisto', '', 'query=SELECT id, CONCAT_WS('' - '', numero, descrizione) AS descrizione FROM co_pianodeiconti3 WHERE dir=''uscita''', 1, 'Fatturazione'),
(38, 'Porta SMTP', '25', 'string', 1, 'Email'),
(39, 'Destinatario', 'info@openstamanager.com', 'string', 1, 'Email'),
(40, 'Numero di backup da mantenere', '7', 'integer', 1, 'Generali'),
(41, 'Backup automatico', '0', 'boolean', 1, 'Generali'),
(42, 'Usa tabelle avanzate', '1', 'boolean', 1, 'Generali'),
(43, 'Utilizzare i tooltip sul calendario', '0', 'boolean', 1, 'Generali'),
(44, 'Visualizzare la domenica sul calendario', '1', 'boolean', 1, 'Generali'),
(45, 'Nascondere la barra sinistra di default', '0', 'boolean', 1, 'Generali'),
(46, 'Abilitare orario lavorativo', '0', 'boolean', 1, 'Generali'),
(47, 'Cifre decimali', '2', 'list[1,2,3,4]', 1, 'Generali'),
(48, 'CSS Personalizzato', '', 'textarea', 1, 'Generali'),
(49, 'Attiva aggiornamenti', '1', 'boolean', '0', 'Generali');
-- --------------------------------------------------------

--
-- Struttura della tabella `zz_modules`
--

CREATE TABLE IF NOT EXISTS `zz_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name2` varchar(255) NOT NULL,
  `module_dir` varchar(50) NOT NULL,
  `options` text NOT NULL,
  `options2` text NOT NULL,
  `icon` varchar(255) NOT NULL,
  `version` varchar(15) NOT NULL,
  `compatibility` varchar(1000) NOT NULL,
  `order` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL,
  `parent` int(11) NOT NULL,
  `default` tinyint(1) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `type` varchar(20) NOT NULL,
  `new` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_modules`
--

INSERT INTO `zz_modules` (`id`, `name`, `name2`, `module_dir`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`, `type`, `new`) VALUES
(1, 'Dashboard', '', 'dashboard', '{	"main_query": [	{ "type": "custom" }	]}', '', 'fa fa-dashboard', '2.0', '2.0', 0, 0, 0, 1, 1, 'menu', 0),
(2, 'Anagrafiche', '', 'anagrafiche', '{	"main_query": [	{	"type": "table", "fields": "Ragione sociale, Tipologia, Città, Telefono, color_Rel.",	"query": "SELECT `idanagrafica` AS `id`, ragione_sociale AS `Ragione sociale`, (SELECT GROUP_CONCAT(descrizione SEPARATOR '', '') FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica GROUP BY idanagrafica HAVING idanagrafica=an_anagrafiche.idanagrafica) AS `Tipologia`, citta AS `Città`, telefono AS `Telefono`, an_relazioni.colore AS `color_Rel.`, an_relazioni.descrizione AS `color_title_Rel.` FROM an_anagrafiche LEFT OUTER JOIN an_relazioni ON an_anagrafiche.idrelazione=an_relazioni.id WHERE 1=1 AND deleted=0 ORDER BY `ragione_sociale`"}	]}', '', 'fa fa-users', '2.0', '2.0', 1, 0, 0, 1, 1, 'menu', 0),
(3, 'Interventi', 'Attivit&agrave;', 'interventi', '{	"main_query": [	{	"type": "table", "fields": "ID intervento, Ragione sociale, Data inizio, Data fine, _print_",	"query": "SELECT `in_interventi`.`idanagrafica`, `in_interventi`.`idintervento` AS `id`, `in_interventi`.`idintervento` AS `ID intervento`, `ragione_sociale` AS `Ragione sociale`, MIN( DATE_FORMAT( `orario_inizio`, ''%d/%m/%Y'' ) ) AS `Data inizio`, MAX( DATE_FORMAT( `orario_fine`, ''%d/%m/%Y'' ) ) AS `Data fine`, `data_richiesta`, (SELECT `colore` FROM `in_statiintervento` WHERE `idstatointervento`=`in_interventi`.`idstatointervento`) AS `_bg_`, ''pdfgen.php?ptype=interventi&idintervento=$id$&mode=single'' AS `_print_`, `orario_inizio`, `orario_fine` FROM (`in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento`=`in_interventi`.`idintervento` GROUP BY `in_interventi`.`idintervento` HAVING 1=1 AND ( ( DATE_FORMAT( `orario_inizio`, ''%Y-%m-%d'' ) >= ''|period_start|'' AND DATE_FORMAT( `orario_fine`, ''%Y-%m-%d'' ) <= ''|period_end|'' )  OR  ( DATE_FORMAT( `data_richiesta`, ''%Y-%m-%d'' ) >= ''|period_start|'' AND DATE_FORMAT( `data_richiesta`, ''%Y-%m-%d'' ) <= ''|period_end|'' ) ) ORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC"}	]}', '', 'fa fa-wrench', '2.0', '2.0', 2, 0, 0, 1, 1, 'menu', 0),
(6, 'Aggiornamenti', '', 'aggiornamenti', '{ "main_query": [ { "type": "custom" } ]}', '', 'fa fa-download', '2.0', '2.0', 4, 0, 0, 1, 1, 'menu', 0),
(7, 'Backup', '', 'backup', '{ "main_query": [ { "type": "custom" } ]}', '', 'fa fa-archive', '2.0', '2.0', 5, 0, 0, 1, 1, 'menu', 0),
(8, 'Tipi di anagrafiche', '', 'tipi_anagrafiche', '{	"main_query": [	{	"type": "table", "fields": "Descrizione", "query": "SELECT `idtipoanagrafica` AS `id`, `descrizione` AS `Descrizione` FROM `an_tipianagrafiche` WHERE 1=1"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 0, 1, 2, 1, 0, 'menu', 0),
(9, 'Tipi di intervento', 'Tipi di attivit&agrave;', 'tipi_intervento', '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, Costo orario, Costo al km, Diritto di chiamata, Costo orario tecnico, Costo al km tecnico, Diritto di chiamata tecnico",	"query": "SELECT `idtipointervento` AS `id`, `idtipointervento` AS `Codice`, `descrizione` AS `Descrizione`, REPLACE( FORMAT(`costo_orario`,2), ''.'', '','' ) AS `Costo orario`, REPLACE( FORMAT(`costo_km`,2), ''.'', '','' ) AS `Costo al km`, REPLACE( FORMAT(`costo_diritto_chiamata`,2), ''.'', '','' ) AS `Diritto di chiamata`, REPLACE( FORMAT(`costo_orario_tecnico`,2), ''.'', '','' ) AS `Costo orario tecnico`, REPLACE( FORMAT(`costo_km_tecnico`,2), ''.'', '','' ) AS `Costo al km tecnico`, REPLACE( FORMAT(`costo_diritto_chiamata_tecnico`,2), ''.'', '','' ) AS `Diritto di chiamata tecnico` FROM `in_tipiintervento` WHERE 1=1"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 0, 1, 3, 1, 1, 'menu', 0),
(10, 'Stati di intervento', 'Stati di attivit&agrave;', 'stati_intervento', '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, color_Colore",	"query": "SELECT `idstatointervento` AS `Codice`, `idstatointervento` AS `id`, `descrizione` AS `Descrizione`, `colore` AS `color_Colore` FROM `in_statiintervento` WHERE 1=1"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 1, 1, 3, 1, 1, 'menu', 0),
(12, 'Contabilit&agrave;', '', 'contabilita', '', '', 'fa fa-eur', '2.0', '2.0', 3, 0, 0, 1, 1, 'menu', 0),
(13, 'Preventivi', '', 'preventivi', '{	"main_query": [	{	"type": "table", "fields": "Numero, Nome, Cliente, icon_Stato",	"query": "SELECT `id`, `numero` AS `Numero`, `nome` AS `Nome`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_preventivi`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `co_statipreventivi` WHERE `id`=`idstato`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_statipreventivi` WHERE `id`=`idstato`) AS `icon_title_Stato` FROM `co_preventivi` WHERE 1=1 AND (''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`) OR (''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`) OR (`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'') OR (`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'') OR (`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'') ORDER BY `id` DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 0, 1, 12, 1, 1, 'menu', 0),
(14, 'Fatture di vendita', '', 'fatture', '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Cliente, Totale, icon_Stato",	"query": "SELECT `co_documenti`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_documenti`.`idanagrafica`) AS `Cliente`, REPLACE( REPLACE( REPLACE( FORMAT(((SELECT SUM(subtotale-sconto+iva) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), '','', ''#''), ''.'', '',''), ''#'', ''.'') AS Totale, (SELECT `icona` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_title_Stato` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` WHERE `dir`=''entrata'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY DATE_FORMAT( `data`, ''%Y%m%d'' ) DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 3, 1, 12, 1, 1, 'menu', 0),
(15, 'Fatture di acquisto', '', 'fatture', '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Cliente, Totale, icon_Stato",	"query": "SELECT `co_documenti`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_documenti`.`idanagrafica`) AS `Cliente`, REPLACE( REPLACE( REPLACE( FORMAT(((SELECT SUM(subtotale-sconto+iva) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), '','', ''#''), ''.'', '',''), ''#'', ''.'') AS Totale, (SELECT `icona` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_statidocumento` WHERE `id`=`idstatodocumento`) AS `icon_title_Stato` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` WHERE `dir`=''uscita'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY DATE_FORMAT( `data`, ''%Y%m%d'' ) DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 4, 1, 12, 1, 1, 'menu', 0),
(16, 'Prima nota', '', 'primanota', '{	"main_query": [	{	"type": "table", "fields": "Data, Causale, Controparte, Conto dare, Conto avere, Dare, Avere",	"query": "SELECT `co_movimenti`.`id` AS `id`, DATE_FORMAT(`data`, ''%d/%m/%Y'') AS `Data`, `co_movimenti`.`descrizione` AS `Causale`, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_documenti WHERE id=iddocumento)) AS `Controparte`, GROUP_CONCAT(CASE WHEN  totale>0 THEN co_pianodeiconti3.descrizione ELSE NULL END) AS `Conto dare`, GROUP_CONCAT(CASE WHEN  totale<0 THEN co_pianodeiconti3.descrizione ELSE NULL END) AS `Conto avere`, FORMAT( SUM(CASE WHEN totale>0 THEN ABS(totale) ELSE 0 END), 2, ''de_DE'' ) AS Dare, FORMAT( SUM(CASE WHEN totale<0 THEN ABS(totale) ELSE 0 END), 2, ''de_DE'' ) AS Avere FROM co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id GROUP BY `idmastrino`, `primanota`, `co_movimenti`.`data` HAVING 1=1 AND primanota=1 AND `co_movimenti`.`data`>=''|period_start|'' AND `co_movimenti`.`data`<=''|period_end|'' ORDER BY `co_movimenti`.`data` DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 5, 1, 12, 1, 1, 'menu', 0),
(17, 'Partitario', '', 'partitario', '{	"main_query": [	{ "type": "custom" }	]}', '', 'fa fa-external-link', '2.0', '2.0', 6, 1, 12, 1, 1, 'menu', 0),
(18, 'Scadenzario', '', 'scadenziario', '{	"main_query": [	{	"type": "table", "fields": "Documento, Cliente, Tipo di pagamento, Data emissione, Data scadenza, Importo, Pagato", "query": "SELECT co_scadenziario.id AS id, ragione_sociale AS `Cliente`, co_pagamenti.descrizione AS `Tipo di pagamento`, CONCAT( co_tipidocumento.descrizione, CONCAT( '' numero '', IF(numero_esterno<>'''', numero_esterno, numero) ) ) AS `Documento`, DATE_FORMAT(data_emissione, ''%d/%m/%Y'') AS `Data emissione`, DATE_FORMAT(scadenza, ''%d/%m/%Y'') AS `Data scadenza`, REPLACE(da_pagare, ''.'', '','') AS `Importo`, REPLACE(pagato, ''.'', '','') AS `Pagato`, IF(scadenza<NOW(), ''#ff7777'', '''') AS _bg_, IF( dir=''entrata'', CONCAT( CONCAT( CONCAT( ''/editor.php?id_module='', (SELECT id FROM zz_modules WHERE name=''Fatture di vendita'') ), ''&id_record=''), co_scadenziario.iddocumento), CONCAT( CONCAT( CONCAT( ''/editor.php?id_module='', (SELECT id FROM zz_modules WHERE name=''Fatture di acquisto'') ), ''&id_record=''), co_scadenziario.iddocumento) ) AS _link_ FROM co_scadenziario INNER JOIN (((co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) ON co_scadenziario.iddocumento=co_documenti.id WHERE ABS(pagato) < ABS(da_pagare) AND idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione=''Emessa'') ORDER BY scadenza ASC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 7, 1, 12, 1, 1, 'menu', 0),
(20, 'Magazzino', '', 'magazzino', '', '', 'fa fa-truck', '2.0', '2.0', 4, 0, 0, 1, 1, 'menu', 0),
(21, 'Articoli', '', 'articoli', '{	"main_query": [	{	"type": "table", "fields": "Codice, Descrizione, Categoria, Subcategoria, Q.tà",	"query": "SELECT `id`, `codice` AS `Codice`, `descrizione` AS `Descrizione`, `categoria` AS `Categoria`, `subcategoria` AS `Subcategoria`, CONCAT_WS( '' '', REPLACE( FORMAT( `qta`, 2 ), ''.'', '','' ), (SELECT `valore` FROM `mg_unitamisura` WHERE `id`=`idum`) ) AS `Q.tà` FROM `mg_articoli` WHERE 1=1 ORDER BY `descrizione`"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 0, 1, 20, 1, 1, 'menu', 0),
(22, 'Listini', '', 'listini', '{	"main_query": [	{	"type": "table", "fields": "Nome, Percentuale guadagno o sconto,Note", "query": "SELECT `id`, `nome` AS `Nome`, `prc_guadagno` AS `Percentuale guadagno o sconto`,`note` AS `Note`  FROM `mg_listini` ORDER BY `nome`"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 1, 1, 20, 1, 1, 'menu', 0),
(23, 'Automezzi', '', 'automezzi', '{	"main_query": [	{	"type": "table", "fields": "Targa,Nome,Descrizione", "query": "SELECT `id`, `targa` AS `Targa`, `nome` AS `Nome`,`descrizione` AS `Descrizione`  FROM `dt_automezzi` ORDER BY `targa`"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 2, 1, 20, 1, 1, 'menu', 0),
(24, 'Ordini cliente', '', 'ordini', '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Ragione sociale, icon_Stato",	"query": "SELECT `or_ordini`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`or_ordini`.`idanagrafica`) AS `Ragione sociale`, (SELECT `icona` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_Stato`, (SELECT `descrizione` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_title_Stato` FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id` WHERE 1=1 AND `dir`=''entrata'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY `id` DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 1, 1, 12, 1, 1, 'menu', 0),
(25, 'Ordini fornitore', '', 'ordini', '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Ragione sociale, icon_Stato",	"query": "SELECT `or_ordini`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`or_ordini`.`idanagrafica`) AS `Ragione sociale`, (SELECT `icona` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_Stato`, (SELECT `descrizione` FROM `or_statiordine` WHERE `id`=`idstatoordine`) AS `icon_title_Stato` FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id` WHERE 1=1 AND `dir`=''uscita'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY `id` DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 2, 1, 12, 1, 1, 'menu', 0),
(26, 'Ddt di vendita', '', 'ddt', '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Cliente, icon_Stato",	"query": "SELECT `dt_ddt`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`dt_ddt`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_Stato`, (SELECT `descrizione` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_title_Stato` FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id` WHERE 1=1 AND `dir`=''entrata'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY DATE_FORMAT( `data`, ''%Y%m%d'' ) DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 3, 1, 20, 1, 1, 'menu', 0),
(27, 'Ddt di acquisto', '', 'ddt', '{	"main_query": [	{	"type": "table", "fields": "Numero, Data, Cliente, icon_Stato",	"query": "SELECT `dt_ddt`.`id`, IF(`numero_esterno`='''', `numero`, `numero_esterno`) AS `Numero`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`dt_ddt`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_Stato`, (SELECT `descrizione` FROM `dt_statiddt` WHERE `id`=`idstatoddt`) AS `icon_title_Stato` FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id` WHERE 1=1 AND `dir`=''uscita'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' ORDER BY DATE_FORMAT( `data`, ''%Y%m%d'' ) DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 4, 1, 20, 1, 1, 'menu', 0),
(28, 'Zone', '', 'zone', '{	"main_query": [	{	"type": "table", "fields": "Nome, Descrizione",	"query": "SELECT `id`, `nome` AS `Nome`, `descrizione` AS `Descrizione` FROM `an_zone` WHERE 1=1 ORDER BY `id`"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 2, 1, 2, 1, 1, 'menu', 0),
(29, 'Tecnici e tariffe', '', 'tecnici_tariffe', '{	"main_query": [	{	"type": "table", "fields": "Tipo intervento, Tecnico, Costo orario, Costo al km, Diritto di chiamata, Costo orario tecnico, Costo al km tecnico, Diritto di chiamata tecnico",	"query": "SELECT `id`, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=in_tariffe.idtipointervento) AS `Tipo intervento`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`idtecnico`) AS `Tecnico`, REPLACE( FORMAT(`costo_ore`,2), ''.'', '','' ) AS `Costo orario`, REPLACE( FORMAT(`costo_km`,2), ''.'', '','' ) AS `Costo al km`, REPLACE( FORMAT(`costo_dirittochiamata`,2), ''.'', '','' ) AS `Diritto di chiamata`, REPLACE( FORMAT(`costo_ore_tecnico`,2), ''.'', '','' ) AS `Costo orario tecnico`, REPLACE( FORMAT(`costo_km_tecnico`,2), ''.'', '','' ) AS `Costo al km tecnico`, REPLACE( FORMAT(`costo_dirittochiamata_tecnico`,2), ''.'', '','' ) AS `Diritto di chiamata tecnico` FROM `in_tariffe` UNION SELECT CONCAT(`an_anagrafiche`.`idanagrafica`,''|'',`in_tipiintervento`.`idtipointervento`) AS `id`, `in_tipiintervento`.`descrizione` AS `Tipo intervento`, `ragione_sociale` AS `Tecnico`, ''0,00'' AS `Costo orario`, ''0,00'' AS `Costo al km`, ''0,00'' AS `Diritto di chiamata`, ''0,00'' AS `Costo orario tecnico`, ''0,00'' AS `Costo al km tecnico`, ''0,00'' AS `Diritto di chiamata tecnico`  FROM ((`an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`idtipoanagrafica`) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` ) LEFT OUTER JOIN `in_tipiintervento` ON 1=1)  WHERE 1=1 AND `an_tipianagrafiche`.`descrizione`=''Tecnico'' AND CONCAT_WS( ''-'', `an_anagrafiche`.`idanagrafica`, `in_tipiintervento`.`idtipointervento`) NOT IN( SELECT CONCAT_WS( ''-'', `in_tariffe`.`idtecnico`, `in_tariffe`.`idtipointervento` ) FROM `in_tariffe` WHERE `idtecnico`=`an_anagrafiche`.`idanagrafica`) ORDER BY `Tipo intervento`, `Tecnico`"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 3, 1, 3, 1, 1, 'menu', 0),
(30, 'MyImpianti', '', 'my_impianti', '{	"main_query": [	{	"type": "table", "fields": "Matricola, Nome, Cliente, Data, Tecnico", "query": "SELECT `matricola` AS `id`, `matricola` AS `Matricola`, `nome` AS `Nome`, DATE_FORMAT( `data`, ''%d/%m/%Y'' ) AS `Data`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`my_impianti`.`idanagrafica`) AS `Cliente`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`my_impianti`.`idtecnico`) AS `Tecnico` FROM `my_impianti` WHERE 1=1 ORDER BY `matricola`"}	]}', '', 'fa fa-puzzle-piece', '0.1', '2.0', 8, 0, 0, 0, 1, 'menu', 0),
(31, 'Contratti', '', 'contratti', '{	"main_query": [	{	"type": "table", "fields": "Numero, Nome, Cliente, icon_Stato",	"query": "SELECT `id`, `numero` AS `Numero`, `nome` AS `Nome`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`co_contratti`.`idanagrafica`) AS `Cliente`, (SELECT `icona` FROM `co_staticontratti` WHERE `id`=`idstato`) AS `icon_Stato`, (SELECT `descrizione` FROM `co_staticontratti` WHERE `id`=`idstato`) AS `icon_title_Stato` FROM `co_contratti` WHERE 1=1 AND (''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`) OR (''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`) OR (`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'') OR (`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'') OR (`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'') ORDER BY `id` DESC"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 0, 1, 12, 1, 1, 'menu', 0),
(32, 'Voci di servizio', '', 'voci_servizio', '{	"main_query": [	{	"type": "table", "fields": "Categoria, Descrizione",	"query": "SELECT `id`, `descrizione` AS `Descrizione`, `categoria` AS `Categoria` FROM `in_vociservizio` WHERE 1=1 ORDER BY `categoria`, `descrizione`"}	]}', '', 'fa fa-external-link', '2.0', '2.0', 3, 1, 3, 1, 1, 'menu', 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_modules_plugins`
--

CREATE TABLE IF NOT EXISTS `zz_modules_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `idmodule_from` int(11) NOT NULL,
  `idmodule_to` int(11) NOT NULL,
  `position` varchar(50) NOT NULL,
  `script` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_modules_plugins`
--

INSERT INTO `zz_modules_plugins` (`id`, `name`, `idmodule_from`, `idmodule_to`, `position`, `script`) VALUES
(1, 'Impianti del cliente', 30, 2, 'tab', 'my_impianti.anagrafiche.php'),
(2, 'Impianti', 30, 3, 'tab', 'my_impianti.interventi.php'),
(3, 'Referenti', 2, 2, 'tab', 'ajax_referente.php'),
(4, 'Sedi', 2, 2, 'tab', 'ajax_sedi.php'),
(7, 'Statistiche', 2, 2, 'tab', 'statistiche.php'),
(8, 'Interventi svolti', 3, 30, 'tab', 'my_impianti.interventi.php'),
(9, 'Componenti', 30, 30, 'tab', 'my_impianti.componenti.php'),
(10, 'Movimenti', 21, 21, 'tab', 'articoli.movimenti.php'),
(11, 'Lotti', 21, 21, 'tab', 'articoli.lotti.php'),
(12, 'Consuntivo', 13, 13, 'tab', 'preventivi.consuntivo.php'),
(13, 'Consuntivo', 31, 31, 'tab', 'contratti.consuntivo.php'),
(14, 'Pianificazione interventi', 31, 31, 'tab', 'contratti.pianificazioneinterventi.php'),
(15, 'Pianificazione ordini di servizio', 31, 31, 'tab', 'contratti.ordiniservizio.php'),
(16, 'Pianificazione fatturazione', 31, 31, 'tab', 'contratti.fatturaordiniservizio.php');

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_permessi`
--

CREATE TABLE IF NOT EXISTS `zz_permessi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idgruppo` int(11) NOT NULL,
  `idmodule` int(11) NOT NULL,
  `permessi` enum('-','r','rw') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_utenti`
--

CREATE TABLE IF NOT EXISTS `zz_utenti` (
  `idutente` smallint(6) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `idanagrafica` int(11) NOT NULL,
  `idtipoanagrafica` int(11) NOT NULL,
  `idgruppo` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`idutente`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Struttura della tabella `zz_widget_modules`
--

CREATE TABLE IF NOT EXISTS `zz_widget_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `type` enum('stats','chart','custom','print') DEFAULT NULL,
  `id_module` int(11) NOT NULL,
  `location` enum('controller_top','controller_right','editor_top','editor_right') DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `query` text,
  `bgcolor` varchar(7) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `print_link` varchar(255) DEFAULT NULL,
  `more_link` varchar(5000) DEFAULT NULL,
  `more_link_type` enum('link','popup','javascript') DEFAULT NULL,
  `php_include` varchar(255) DEFAULT NULL,
  `text` text,
  `enabled` tinyint(1) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_widget_modules`
--

INSERT INTO `zz_widget_modules` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES
(1, 'Numero di clienti', 'stats', 2, 'controller_top', 'col-md-2', 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione="Cliente" AND deleted=0', '#37a02d', 'fa fa-user', '', '$(''#th_Tipologia input'').val( ''Cliente'' ).trigger( ''keyup'' );', 'javascript', '', 'Clienti', 1, 0),
(2, 'Numero di tecnici', 'stats', 2, 'controller_top', 'col-md-2', 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione="Tecnico" AND deleted=0', '#ff7e00', 'fa fa-cog', '', '$(''#th_Tipologia input'').val( ''Tecnico'' ).trigger( ''keyup'' );', 'javascript', '', 'Tecnici', 1, 1),
(3, 'Numero di fornitori', 'stats', 2, 'controller_top', 'col-md-2', 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione="Fornitore" AND deleted=0', '#a15d2d', 'fa fa-truck', '', '$(''#th_Tipologia input'').val( ''Fornitore'' ).trigger( ''keyup'' );', 'javascript', '', 'Fornitori', 1, 3),
(4, 'Numero di agenti', 'stats', 2, 'controller_top', 'col-md-2', 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione="Agente" AND deleted=0', '#2d70a1', 'fa fa-briefcase', '', '$(''#th_Tipologia input'').val( ''Agente'' ).trigger( ''keyup'' );', 'javascript', '', 'Agenti', 1, 3),
(5, 'Interventi da pianificare', 'stats', 1, 'controller_right', 'col-md-3', 'SELECT COUNT(id) AS dato FROM co_righe_contratti WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND idintervento=""', '#ff7e00', 'fa fa-cog', '', './modules/contratti/widgets/contratti.pianificazionedashboard.interventi.php', 'popup', '', 'Interventi da pianificare', 1, 0),
(6, 'Ordini di servizio da impostare', 'stats', 1, 'controller_right', 'col-md-3', 'SELECT COUNT(id) AS dato FROM co_ordiniservizio WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND idintervento=""', '#45a9f1', 'fa fa-gears', '', './modules/contratti/widgets/contratti.pianificazionedashboard.php', 'popup', '', 'Ordini di servizio da impostare', 1, 1),
(7, 'Scadenze', 'stats', 1, 'controller_right', 'col-md-3', 'SELECT COUNT(co_documenti.id) AS dato FROM co_scadenziario INNER JOIN (((co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) ON co_scadenziario.iddocumento=co_documenti.id WHERE ABS(pagato) < ABS(da_pagare) AND idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione="Emessa") AND scadenza >= "|period_start|" AND scadenza <= "|period_end|" ORDER BY scadenza ASC', '#c62f2a', 'fa fa-money', '', './controller.php?id_module=18', 'link', '', 'Scadenze', 1, 2),
(8, 'Articoli in esaurimento', 'stats', 1, 'controller_right', 'col-md-3', 'SELECT COUNT(id) AS dato FROM mg_articoli WHERE qta < threshold_qta AND attivo=1', '#a15d2d', 'fa fa-truck', '', './modules/articoli/widgets/articoli.dashboard.php', 'popup', '', 'Articoli in esaurimento', 1, 3),
(9, 'Preventivi in lavorazione', 'stats', 1, 'controller_right', 'col-md-12', 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE idstato=(SELECT id FROM co_statipreventivi WHERE descrizione="In lavorazione")', '#44aae4', 'fa fa-tasks', '', './modules/preventivi/widgets/preventivi.dashboard.php', 'popup', '', 'Preventivi in lavorazione', 1, 4),
(10, 'Contratti in scadenza', 'stats', 1, 'controller_right', 'col-md-12', 'SELECT COUNT(id) AS dato FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione="Accettato" OR descrizione="In lavorazione" OR descrizione="In attesa di pagamento") AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL -ABS(giorni_preavviso_rinnovo) DAY)', '#c62f2a', 'fa fa-edit', '', './modules/contratti/widgets/contratti_scadenza.dashboard.php', 'popup', '', 'Contratti in scadenza', 1, 5),
(11, 'Rate contrattuali', 'stats', 1, 'controller_right', 'col-md-12', 'SELECT COUNT(id) AS dato FROM co_ordiniservizio_pianificazionefatture WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND co_ordiniservizio_pianificazionefatture.iddocumento=0', '#4ccc4c', 'fa fa-folder-open', '', './modules/contratti/widgets/contratti.ratecontrattuali.php', 'popup', '', 'Rate contrattuali', 1, 6),
(12, 'Stampa inventario', 'print', 21, 'controller_right', 'col-md-12', '', '#45a9f1', 'fa fa-print', '', 'if( confirm(''Stampare l\\''inventario?'') ){ window.open(''templates/pdfgen.php?ptype=magazzino_inventario&search_codice=''+$(''#th_Codice input'').val()+''&search_descrizione=''+$(''#th_Descrizione input'').val()+''&search_categoria=''+$(''#th_Categoria input'').val()+''&search_subcategoria=''+$(''#th_Subcategoria input'').val()+''&search_tipo=solo prodotti attivi''); }', 'javascript', '', 'Stampa inventario', 1, 1),
(13, 'Fatturato', 'stats', 14, 'controller_top', 'col-md-6', 'SELECT CONCAT_WS( " ", REPLACE( REPLACE( REPLACE( FORMAT( SUM((SELECT SUM(subtotale+iva-sconto) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), ",", "#"), ".", "," ), "#", "."), "&euro;" ) AS dato FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="entrata") AND data >= "|period_start|" AND data <= "|period_end|"', '#4dc347', 'fa fa-money', '', '', '', '', 'Fatturato', 1, 1),
(14, 'Acquisti', 'stats', 15, 'controller_top', 'col-md-6', 'SELECT CONCAT_WS( " ", REPLACE( REPLACE( REPLACE( FORMAT( SUM((SELECT SUM(subtotale+iva-sconto) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), ",", "#"), ".", "," ), "#", "."), "&euro;" ) AS dato FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita") AND data >= "|period_start|" AND data <= "|period_end|"', '#c2464c', 'fa fa-money', '', '', '', '', 'Acquisti', 1, 1),
(15, 'Crediti da clienti', 'stats', 14, 'controller_top', 'col-md-6', 'SELECT CONCAT_WS( " ", REPLACE( REPLACE( REPLACE( FORMAT( SUM((SELECT SUM(subtotale+iva-sconto) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), ",", "#"), ".", "," ), "#", "."), "&euro;" ) AS dato FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="entrata") AND idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione="Emessa") AND data >= "|period_start|" AND data <= "|period_end|"', '#f4af1b', 'fa fa-warning', '', '', '', '', 'Crediti da clienti', 1, 2),
(16, 'Debiti verso fornitori', 'stats', 15, 'controller_top', 'col-md-6', 'SELECT CONCAT_WS( " ", REPLACE( REPLACE( REPLACE( FORMAT( SUM((SELECT SUM(subtotale+iva-sconto) FROM co_righe_documenti WHERE iddocumento=co_documenti.id)+iva_rivalsainps+rivalsainps+bollo-ritenutaacconto), 2), ",", "#"), ".", "," ), "#", "."), "&euro;" ) AS dato FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita") AND idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione="Emessa") AND data >= "|period_start|" AND data <= "|period_end|"', '#f4af1b', 'fa fa-warning', '', '', '', '', 'Debiti verso fornitori', 1, 2),
(17, 'Numero di vettori', 'stats', 2, 'controller_top', 'col-md-2', 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione="Vettore" AND deleted=0', '#00C0EF', 'fa fa-truck', '', '$(''#th_Tipologia input'').val( ''Vettore'' ).trigger( ''keyup'' );', 'javascript', '', 'Vettori', 1, 4),
(18, 'Tutte le anagrafiche', 'stats', 2, 'controller_top', 'col-md-2', 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE deleted=0', '#CCCCCC', 'fa fa-users', '', '$(''#th_Tipologia input'').val( '''' ).trigger( ''keyup'' );', 'javascript', '', 'Tutti', 1, 5),
(19, 'Stampa riepilogo', 'print', 3, 'controller_right', 'col-md-12', '', '#45a9f1', 'fa fa-print', '', 'if( confirm(''Stampare il riepilogo?'') ){ window.open(''templates/pdfgen.php?ptype=riepilogo_interventi&search_idintervento=''+$(''#th_ID_intervento input'').val()+''&search_ragionesociale=''+$(''#th_Ragione_sociale input'').val()+''&search_datastart=''+$(''#th_Data_inizio input'').val()+''&search_dataend=''+$(''#th_Data_fine input'').val()); }', 'javascript', '', 'Stampa riepilogo', 1, 1);

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `an_tipianagrafiche_anagrafiche`
--
ALTER TABLE `an_tipianagrafiche_anagrafiche`
  ADD CONSTRAINT `an_tipianagrafiche_anagrafiche_ibfk_1` FOREIGN KEY (`idtipoanagrafica`) REFERENCES `an_tipianagrafiche` (`idtipoanagrafica`) ON DELETE CASCADE,
  ADD CONSTRAINT `an_tipianagrafiche_anagrafiche_ibfk_2` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche` (`idanagrafica`) ON DELETE CASCADE;

--
-- Limiti per la tabella `in_interventi`
--
ALTER TABLE `in_interventi`
  ADD CONSTRAINT `in_interventi_ibfk_1` FOREIGN KEY (`idanagrafica`) REFERENCES `an_anagrafiche` (`idanagrafica`) ON DELETE CASCADE,
  ADD CONSTRAINT `in_interventi_ibfk_2` FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento` (`idtipointervento`) ON DELETE CASCADE;

--
-- Limiti per la tabella `in_interventi_tecnici`
--
ALTER TABLE `in_interventi_tecnici`
  ADD CONSTRAINT `in_interventi_tecnici_ibfk_1` FOREIGN KEY (`idintervento`) REFERENCES `in_interventi` (`idintervento`) ON DELETE CASCADE,
  ADD CONSTRAINT `in_interventi_tecnici_ibfk_2` FOREIGN KEY (`idtecnico`) REFERENCES `an_anagrafiche` (`idanagrafica`) ON DELETE CASCADE;
