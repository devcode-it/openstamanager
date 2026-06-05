<?php

$fk_renames = [
    ['table' => 'an_tipi_anagrafiche_anagrafiche', 'old_fk' => 'an_tipianagrafiche_anagrafiche_ibfk_1', 'new_fk' => 'an_tipi_anagrafiche_anagrafiche_ibfk_1', 'column' => 'id_tipo_anagrafica', 'ref_table' => 'an_tipi_anagrafiche', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'an_tipi_anagrafiche_anagrafiche', 'old_fk' => 'an_tipianagrafiche_anagrafiche_ibfk_2', 'new_fk' => 'an_tipi_anagrafiche_anagrafiche_ibfk_2', 'column' => 'id_anagrafica', 'ref_table' => 'an_anagrafiche', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'an_tipi_anagrafiche_lang', 'old_fk' => 'an_tipianagrafiche_lang_ibfk_1', 'new_fk' => 'an_tipi_anagrafiche_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'an_tipi_anagrafiche', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'an_tipi_anagrafiche_lang', 'old_fk' => 'an_tipianagrafiche_lang_ibfk_2', 'new_fk' => 'an_tipi_anagrafiche_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_contratti_tipi_intervento', 'old_fk' => 'co_contratti_tipiintervento_ibfk_2', 'new_fk' => 'co_contratti_tipi_intervento_ibfk_2', 'column' => 'id_contratto', 'ref_table' => 'co_contratti', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_contratti_tipi_intervento', 'old_fk' => 'co_contratti_tipiintervento_ibfk_1', 'new_fk' => 'co_contratti_tipi_intervento_ibfk_1', 'column' => 'id_tipo_intervento', 'ref_table' => 'in_tipi_intervento', 'ref_column' => 'id', 'on_delete' => 'RESTRICT', 'on_update' => 'RESTRICT'],
    ['table' => 'co_stati_contratti_lang', 'old_fk' => 'co_staticontratti_lang_ibfk_1', 'new_fk' => 'co_stati_contratti_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'co_stati_contratti', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_stati_contratti_lang', 'old_fk' => 'co_staticontratti_lang_ibfk_2', 'new_fk' => 'co_stati_contratti_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_stati_documento_lang', 'old_fk' => 'co_statidocumento_lang_ibfk_1', 'new_fk' => 'co_stati_documento_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'co_stati_documento', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_stati_documento_lang', 'old_fk' => 'co_statidocumento_lang_ibfk_2', 'new_fk' => 'co_stati_documento_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_stati_preventivi_lang', 'old_fk' => 'co_statipreventivi_lang_ibfk_1', 'new_fk' => 'co_stati_preventivi_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'co_stati_preventivi', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_stati_preventivi_lang', 'old_fk' => 'co_statipreventivi_lang_ibfk_2', 'new_fk' => 'co_stati_preventivi_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_tipi_documento', 'old_fk' => 'co_tipidocumento_ibfk_1', 'new_fk' => 'co_tipi_documento_ibfk_1', 'column' => 'codice_tipo_documento_fe', 'ref_table' => 'fe_tipi_documento', 'ref_column' => 'codice', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_tipi_documento_lang', 'old_fk' => 'co_tipidocumento_lang_ibfk_1', 'new_fk' => 'co_tipi_documento_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'co_tipi_documento', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'co_tipi_documento_lang', 'old_fk' => 'co_tipidocumento_lang_ibfk_2', 'new_fk' => 'co_tipi_documento_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_aspetto_beni_lang', 'old_fk' => 'dt_aspettobeni_lang_ibfk_1', 'new_fk' => 'dt_aspetto_beni_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'dt_aspetto_beni', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_aspetto_beni_lang', 'old_fk' => 'dt_aspettobeni_lang_ibfk_2', 'new_fk' => 'dt_aspetto_beni_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_causale_t_lang', 'old_fk' => 'dt_causalet_lang_ibfk_1', 'new_fk' => 'dt_causale_t_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'dt_causale_t', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_causale_t_lang', 'old_fk' => 'dt_causalet_lang_ibfk_2', 'new_fk' => 'dt_causale_t_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_stati_ddt_lang', 'old_fk' => 'dt_statiddt_lang_ibfk_1', 'new_fk' => 'dt_stati_ddt_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'dt_stati_ddt', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_stati_ddt_lang', 'old_fk' => 'dt_statiddt_lang_ibfk_2', 'new_fk' => 'dt_stati_ddt_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_tipi_ddt_lang', 'old_fk' => 'dt_tipiddt_lang_ibfk_1', 'new_fk' => 'dt_tipi_ddt_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'dt_tipi_ddt', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'dt_tipi_ddt_lang', 'old_fk' => 'dt_tipiddt_lang_ibfk_2', 'new_fk' => 'dt_tipi_ddt_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_fasce_orarie_lang', 'old_fk' => 'in_fasceorarie_lang_ibfk_1', 'new_fk' => 'in_fasce_orarie_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'in_fasce_orarie', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_fasce_orarie_lang', 'old_fk' => 'in_fasceorarie_lang_ibfk_2', 'new_fk' => 'in_fasce_orarie_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_fasce_orarie_tipi_intervento', 'old_fk' => 'in_fasceorarie_tipiintervento_ibfk_1', 'new_fk' => 'in_fasce_orarie_tipi_intervento_ibfk_1', 'column' => 'id_fascia_oraria', 'ref_table' => 'in_fasce_orarie', 'ref_column' => 'id', 'on_delete' => 'RESTRICT', 'on_update' => 'RESTRICT'],
    ['table' => 'in_fasce_orarie_tipi_intervento', 'old_fk' => 'in_fasceorarie_tipiintervento_ibfk_2', 'new_fk' => 'in_fasce_orarie_tipi_intervento_ibfk_2', 'column' => 'id_tipo_intervento', 'ref_table' => 'in_tipi_intervento', 'ref_column' => 'id', 'on_delete' => 'RESTRICT', 'on_update' => 'RESTRICT'],
    ['table' => 'in_stati_intervento', 'old_fk' => 'in_statiintervento_ibfk_1', 'new_fk' => 'in_stati_intervento_ibfk_1', 'column' => 'id_email', 'ref_table' => 'em_templates', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_stati_intervento_lang', 'old_fk' => 'in_statiintervento_lang_ibfk_1', 'new_fk' => 'in_stati_intervento_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'in_stati_intervento', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_stati_intervento_lang', 'old_fk' => 'in_statiintervento_lang_ibfk_2', 'new_fk' => 'in_stati_intervento_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_tipi_intervento_lang', 'old_fk' => 'in_tipiintervento_lang_ibfk_1', 'new_fk' => 'in_tipi_intervento_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'in_tipi_intervento', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'in_tipi_intervento_lang', 'old_fk' => 'in_tipiintervento_lang_ibfk_2', 'new_fk' => 'in_tipi_intervento_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'or_stati_ordine_lang', 'old_fk' => 'or_statiordine_lang_ibfk_1', 'new_fk' => 'or_stati_ordine_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'or_stati_ordine', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'or_stati_ordine_lang', 'old_fk' => 'or_statiordine_lang_ibfk_2', 'new_fk' => 'or_stati_ordine_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'or_tipi_ordine_lang', 'old_fk' => 'or_tipiordine_lang_ibfk_1', 'new_fk' => 'or_tipi_ordine_lang_ibfk_1', 'column' => 'id_record', 'ref_table' => 'or_tipi_ordine', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
    ['table' => 'or_tipi_ordine_lang', 'old_fk' => 'or_tipiordine_lang_ibfk_2', 'new_fk' => 'or_tipi_ordine_lang_ibfk_2', 'column' => 'id_lang', 'ref_table' => 'zz_langs', 'ref_column' => 'id', 'on_delete' => 'CASCADE', 'on_update' => 'RESTRICT'],
];

foreach ($fk_renames as $fk) {
    $exists = $database->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.prepare($fk['table']).' AND CONSTRAINT_NAME = '.prepare($fk['old_fk']).' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
    if (!empty($exists)) {
        $on_update = !empty($fk['on_update']) ? ' ON UPDATE '.$fk['on_update'] : '';
        $database->query('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['old_fk'].'`, ADD CONSTRAINT `'.$fk['new_fk'].'` FOREIGN KEY (`'.$fk['column'].'`) REFERENCES `'.$fk['ref_table'].'`(`'.$fk['ref_column'].'`) ON DELETE '.$fk['on_delete'].$on_update);
    }
}

$numeric_fks = $database->fetchArray("
    SELECT tc.TABLE_NAME, tc.CONSTRAINT_NAME
    FROM information_schema.TABLE_CONSTRAINTS tc
    WHERE tc.TABLE_SCHEMA = DATABASE()
    AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND tc.CONSTRAINT_NAME REGEXP '^[0-9]+$'
    ORDER BY tc.TABLE_NAME, CAST(tc.CONSTRAINT_NAME AS UNSIGNED)
");

if (!empty($numeric_fks)) {
    $ibfk_counters = [];
    $all_fks = $database->fetchArray("
        SELECT TABLE_NAME, CONSTRAINT_NAME
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");
    foreach ($all_fks as $afk) {
        $t = $afk['TABLE_NAME'];
        $n = $afk['CONSTRAINT_NAME'];
        if (preg_match('/^'.preg_quote($t, '/').'_ibfk_(\d+)$/', $n, $m)) {
            if (!isset($ibfk_counters[$t])) {
                $ibfk_counters[$t] = 0;
            }
            $ibfk_counters[$t] = max($ibfk_counters[$t], (int) $m[1]);
        }
    }

    foreach ($numeric_fks as $nfk) {
        $table = $nfk['TABLE_NAME'];
        $old_name = $nfk['CONSTRAINT_NAME'];

        $columns = $database->fetchArray('
            SELECT kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, kcu.ORDINAL_POSITION
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = DATABASE()
            AND kcu.TABLE_NAME = '.prepare($table).'
            AND kcu.CONSTRAINT_NAME = '.prepare($old_name).'
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.ORDINAL_POSITION
        ');

        if (empty($columns)) {
            continue;
        }

        $rc = $database->fetchOne('
            SELECT DELETE_RULE, UPDATE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = '.prepare($table).'
            AND CONSTRAINT_NAME = '.prepare($old_name).'
        ');

        $on_delete = !empty($rc) ? $rc['DELETE_RULE'] : 'RESTRICT';
        $on_update_rule = (!empty($rc) && $rc['UPDATE_RULE'] != '') ? $rc['UPDATE_RULE'] : 'RESTRICT';

        if (!isset($ibfk_counters[$table])) {
            $ibfk_counters[$table] = 0;
        }
        ++$ibfk_counters[$table];
        $new_name = $table.'_ibfk_'.$ibfk_counters[$table];

        $fk_cols = [];
        $ref_cols = [];
        $ref_table = $columns[0]['REFERENCED_TABLE_NAME'];
        foreach ($columns as $col) {
            $fk_cols[] = '`'.$col['COLUMN_NAME'].'`';
            $ref_cols[] = '`'.$col['REFERENCED_COLUMN_NAME'].'`';
        }

        $on_update_sql = (!empty($on_update_rule) && $on_update_rule !== 'RESTRICT') ? ' ON UPDATE '.$on_update_rule : '';

        $database->query('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$old_name.'`, ADD CONSTRAINT `'.$new_name.'` FOREIGN KEY ('.implode(', ', $fk_cols).') REFERENCES `'.$ref_table.'`('.implode(', ', $ref_cols).') ON DELETE '.$on_delete.$on_update_sql);
    }
}
