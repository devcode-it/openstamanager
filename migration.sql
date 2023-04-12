INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES
('Piano dei conti associato spese di incasso',	'0',	'query=SELECT co_pianodeiconti3.id, CONCAT( co_pianodeiconti2.numero, \'.\', co_pianodeiconti3.numero, \' \', co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE (co_pianodeiconti2.dir=\'entrata\' OR co_pianodeiconti2.dir=\'entrata uscita\') ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC',	1,	'Anagrafiche',	'2023-01-12 11:03:05',	'2023-02-16 11:18:32',	1,	NULL),
('Piano dei conti associato spese di trasporto',	'0',	'query=SELECT co_pianodeiconti3.id, CONCAT( co_pianodeiconti2.numero, \'.\', co_pianodeiconti3.numero, \' \', co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE (co_pianodeiconti2.dir=\'entrata\' OR co_pianodeiconti2.dir=\'entrata uscita\') ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC',	1,	'Anagrafiche',	'2023-01-12 11:03:05',	'2023-02-16 11:18:30',	1,	NULL),
('Spese di incasso default',	'0',	'float',	1,	'Anagrafiche',	'2022-12-21 14:51:03',	'2023-02-16 11:18:29',	NULL,	NULL),
('Spese di trasporto default',	'0',	'float',	1,	'Anagrafiche',	'2022-12-21 14:51:03',	'2023-02-16 11:18:26',	NULL,	NULL);

ALTER TABLE `an_anagrafiche`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `spese_di_trasporto` tinyint(1) NULL DEFAULT '0',
ADD `importo_spese_di_trasporto` float NULL DEFAULT '0' AFTER `spese_di_trasporto`,
ADD `spese_di_incasso` tinyint(1) NULL DEFAULT '0' AFTER `importo_spese_di_trasporto`,
ADD `importo_spese_di_incasso` float NULL DEFAULT '0' AFTER `spese_di_incasso`;

ALTER TABLE `dt_righe_ddt`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `is_spesa_incasso` tinyint(1) NULL DEFAULT '0' AFTER `id_dettaglio_fornitore`,
ADD `is_spesa_trasporto` tinyint(1) NULL DEFAULT '0' AFTER `is_spesa_incasso`;

ALTER TABLE `co_righe_documenti`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `is_spesa_incasso` tinyint(1) NULL DEFAULT '0',
ADD `is_spesa_trasporto` tinyint(1) NULL DEFAULT '0' AFTER `is_spesa_incasso`;

ALTER TABLE `co_righe_preventivi`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `is_spesa_incasso` tinyint(1) NULL DEFAULT '0',
ADD `is_spesa_trasporto` tinyint(1) NULL DEFAULT '0' AFTER `is_spesa_incasso`;

ALTER TABLE `or_righe_ordini`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `is_spesa_incasso` tinyint(1) NULL DEFAULT '0',
ADD `is_spesa_trasporto` tinyint(1) NULL DEFAULT '0' AFTER `is_spesa_incasso`;
