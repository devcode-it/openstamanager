<?php

/*
* Creazione dei campi per l'API (created_at e updated_at)
*/

// I record precedenti vengono impostati a NULL
$tables = [
    'an_anagrafiche',
    'an_anagrafiche_agenti',
    'an_nazioni',
    'an_referenti',
    'an_relazioni',
    'an_sedi',
    'an_tipianagrafiche',
    'an_tipianagrafiche_anagrafiche',
    'an_zone',
    'co_contratti',
    'co_contratti_tipiintervento',
    'co_documenti',
    'co_iva',
    'co_movimenti',
    'co_ordiniservizio',
    'co_ordiniservizio_pianificazionefatture',
    'co_ordiniservizio_vociservizio',
    'co_pagamenti',
    'co_pianodeiconti1',
    'co_pianodeiconti2',
    'co_pianodeiconti3',
    'co_preventivi',
    'co_preventivi_interventi',
    'co_righe2_contratti',
    'co_righe_contratti',
    'co_righe_documenti',
    'co_righe_preventivi',
    'co_ritenutaacconto',
    'co_rivalsainps',
    'co_scadenziario',
    'co_staticontratti',
    'co_statidocumento',
    'co_statipreventivi',
    'co_tipidocumento',
    'dt_aspettobeni',
    'dt_automezzi',
    'dt_automezzi_tecnici',
    'dt_causalet',
    'dt_ddt',
    'dt_porto',
    'dt_righe_ddt',
    'dt_spedizione',
    'dt_statiddt',
    'dt_tipiddt',
    'in_interventi',
    'in_interventi_tecnici',
    'in_righe_interventi',
    'in_statiintervento',
    'in_tariffe',
    'in_tipiintervento',
    'in_vociservizio',
    'mg_articoli',
    'mg_articoli_automezzi',
    'mg_articoli_interventi',
    'mg_categorie',
    'mg_listini',
    'mg_movimenti',
    'mg_prodotti',
    'mg_unitamisura',
    'my_componenti_interventi',
    'my_impianti',
    'my_impianti_contratti',
    'my_impianti_interventi',
    'my_impianto_componenti',
    'or_ordini',
    'or_righe_ordini',
    'or_statiordine',
    'or_tipiordine',
    'zz_tokens',
    'zz_files',
    'zz_groups',
    'zz_group_module',
    'zz_settings',
    'zz_logs',
    'zz_modules',
    'zz_plugins',
    'zz_permissions',
    'zz_users',
    'zz_widgets',
    'zz_views',
    'zz_group_view',
    'zz_semaphores',
];

$pieces = array_chunk($tables, 10);

foreach ($pieces as $piece) {
    $implode = [];
    foreach ($piece as $table) {
        $implode[] = prepare($table);
    }

    $query = 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES T WHERE T.TABLE_SCHEMA = '.prepare($database->getDatabaseName()).' AND
    T.TABLE_NAME IN('.implode(',', $implode).") AND
    NOT EXISTS (
        SELECT table_name
        FROM INFORMATION_SCHEMA.COLUMNS C
        WHERE
            C.TABLE_SCHEMA = T.TABLE_SCHEMA AND
            C.TABLE_NAME = T.TABLE_NAME AND
            C.COLUMN_NAME = '|field|')";

    $created_at = $database->fetchArray(str_replace('|field|', 'created_at', $query));
    foreach ($created_at as $table) {
        $database->query('ALTER TABLE `'.$table['table_name'].'` ADD `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP');
    }

    if (API::isCompatible()) {
        $updated_at = $database->fetchArray(str_replace('|field|', 'updated_at', $query));
        foreach ($updated_at as $table) {
            $database->query('ALTER TABLE `'.$table['table_name'].'` `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }
}
