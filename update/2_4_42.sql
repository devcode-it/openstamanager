-- Ampliamento decimali fattore moltiplicativo per unità di misura secondaria
ALTER TABLE `mg_articoli` CHANGE `fattore_um_secondaria` `fattore_um_secondaria` DECIMAL(19,10) NULL DEFAULT NULL; 

-- Correzione dimensione widget
UPDATE `zz_settings` SET `valore` = 'col-md-3', `tipo` = 'list[col-md-1,col-md-2,col-md-3,col-md-4,col-md-6]', `nome` = 'Dimensione widget predefinita' WHERE `nome` = 'Numero massimo Widget per riga';

-- Reset class widget
UPDATE `zz_widgets` SET `class` = NULL;

-- Aggiunto available_options per zz_prints
ALTER TABLE `zz_prints` ADD `available_options` TEXT NULL AFTER `enabled`;

-- Opzioni possibili per stampa "Preventivo"
UPDATE `zz_prints` SET `available_options` = '{\"pricing\":\"Visualizzare i prezzi\", \"hide-total\": \"Nascondere i totali delle righe\", \"show-only-total\": \"Visualizzare solo i totali del documento\", \"hide-header\": \"Nascondere intestazione\", \"hide-footer\": \"Nascondere footer\", \"last-page-footer\": \"Visualizzare footer solo su ultima pagina\", \"hide-item-number\": \"Nascondere i codici degli articoli\"}' WHERE `zz_prints`.`name` = 'Preventivo';

UPDATE `zz_prints` SET `options` = '{\"pricing\": true, \"last-page-footer\": true, \"hide-item-number\": true}' WHERE `zz_prints`.`name` = 'Ordine cliente (senza codici)';

UPDATE `zz_prints` SET `options` = '{\"pricing\":true, \"hide-total\":true}' WHERE `zz_prints`.`name` = 'Preventivo (senza totali)'; 
UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"show-only-total\":true}' WHERE `zz_prints`.`name` = 'Preventivo (solo totale)'; 