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
    'co_banche',
    'co_contratti',
    'co_contratti_promemoria',
    'co_contratti_tipiintervento',
    'co_documenti',
    'co_iva',
    'co_movimenti',
    'co_movimenti_modelli',
    'co_ordiniservizio',
    'co_ordiniservizio_pianificazionefatture',
    'co_ordiniservizio_vociservizio',
    'co_pagamenti',
    'co_pianodeiconti1',
    'co_pianodeiconti2',
    'co_pianodeiconti3',
    'co_preventivi',
    'co_preventivi_interventi',
    'co_righe_contratti',
    'co_righe_contratti_articoli',
    'co_righe_contratti_materiali',
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
    'fe_causali_pagamento_ritenuta',
    'fe_modalita_pagamento',
    'fe_natura',
    'fe_regime_fiscale',
    'fe_tipi_documento',
    'fe_tipo_cassa',
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
    'zz_documenti',
    'zz_documenti_categorie',
    'zz_email_print',
    'zz_emails',
    'zz_field_record',
    'zz_fields',
    'zz_files',
    'zz_groups',
    'zz_logs',
    'zz_modules',
    'zz_operations',
    'zz_permissions',
    'zz_plugins',
    'zz_prints',
    'zz_segments',
    'zz_semaphores',
    'zz_settings',
    'zz_smtps',
    'zz_tokens',
    'zz_users',
    'zz_views',
    'zz_widgets',
];

foreach ($tables as $table) {
    if ($database->tableExists($table)) {
        $query = 'SHOW COLUMNS FROM `'.$table.'` IN `'.$database->getDatabaseName()."` WHERE Field='|field|'";

        $created_at = $database->fetchArray(str_replace('|field|', 'created_at', $query));
        if (empty($created_at)) {
            $database->query('ALTER TABLE `'.$table.'` ADD `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP');
        }

        if (API::isCompatible()) {
            $updated_at = $database->fetchArray(str_replace('|field|', 'updated_at', $query));
            if (empty($updated_at)) {
                $database->query('ALTER TABLE `'.$table.'` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
            }
        }
    }
}
