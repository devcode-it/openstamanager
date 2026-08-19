<?php

include_once __DIR__.'/../../core.php';

$ids = array_values(array_unique(array_filter(array_map('intval', (array) $id_records))));

switch (post('op')) {
    case 'confirm_bulk':
        Permissions::check('rw');
        if (empty($ids)) {
            break;
        }

        $id_stato = noteSpeseGetStatusId($dbo, 'confermato');
        if (empty($id_stato)) {
            flash()->error(tr('Stato di conferma non disponibile.'));
            break;
        }

        // Una Nota spesa 1.9.0 è confermabile solo se identifica l'Operatore
        // che ha anticipato personalmente il costo.
        $missing_operator_rows = $dbo->fetchArray(
            'SELECT `id` FROM `co_note_spese` WHERE `id` IN ('.implode(',', $ids).') '
            .'AND COALESCE(`id_operatore`, 0) = 0'
        );
        $missing_operator_ids = array_map('intval', array_column($missing_operator_rows, 'id'));

        $inactive_category_rows = $dbo->fetchArray(
            'SELECT n.`id` FROM `co_note_spese` n '
            .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
            .'WHERE n.`id` IN ('.implode(',', $ids).') AND COALESCE(t.`enabled`, 0) != 1'
        );
        $inactive_category_ids = array_map('intval', array_column($inactive_category_rows, 'id'));

        // I duplicati esatti restano da verificare: per confermarli è necessario
        // aprire la singola spesa e verificarne la correttezza.
        $duplicate_rows = $dbo->fetchArray(
            'SELECT DISTINCT a.`id` FROM `co_note_spese` a '
            .'INNER JOIN `co_note_spese` b ON b.`id` != a.`id` '
            .'AND b.`data` = a.`data` '
            .'AND b.`importo` = a.`importo` '
            .'AND LOWER(TRIM(b.`descrizione`)) = LOWER(TRIM(a.`descrizione`)) '
            .'AND LOWER(TRIM(COALESCE(b.`controparte`, ""))) = LOWER(TRIM(COALESCE(a.`controparte`, ""))) '
            .'AND COALESCE(b.`id_operatore`, 0) = COALESCE(a.`id_operatore`, 0) '
            .'INNER JOIN `co_note_spese_stati` bst ON bst.`id` = b.`id_stato` AND bst.`name` != '.prepare('escluso').' '
            .'WHERE a.`id` IN ('.implode(',', $ids).')'
        );
        $duplicate_ids = array_map('intval', array_column($duplicate_rows, 'id'));

        $confirm_ids = array_values(array_diff($ids, $duplicate_ids, $missing_operator_ids, $inactive_category_ids));

        if (!empty($confirm_ids)) {
            $dbo->query('UPDATE `co_note_spese` SET `id_stato` = '.prepare($id_stato).' WHERE `id` IN ('.implode(',', $confirm_ids).')');
            flash()->info(tr('_COUNT_ spese confermate.', ['_COUNT_' => count($confirm_ids)]));
        }
        if (!empty($duplicate_ids)) {
            flash()->warning(tr('_COUNT_ possibili duplicati non sono stati confermati: aprire le singole righe per verificarli.', ['_COUNT_' => count($duplicate_ids)]));
        }
        if (!empty($missing_operator_ids)) {
            flash()->warning(tr('_COUNT_ spese senza Operatore non sono state confermate: associare prima la persona che ha anticipato il costo.', ['_COUNT_' => count($missing_operator_ids)]));
        }
        if (!empty($inactive_category_ids)) {
            flash()->warning(tr('_COUNT_ spese con Tipologia non più attiva non sono state confermate: riclassificarle prima della conferma.', ['_COUNT_' => count($inactive_category_ids)]));
        }
        break;

    case 'review_bulk':
    case 'exclude_bulk':
        Permissions::check('rw');
        if (empty($ids)) {
            break;
        }

        $status_name = post('op') === 'review_bulk' ? 'da_verificare' : 'escluso';
        $id_stato = noteSpeseGetStatusId($dbo, $status_name);
        if (!empty($id_stato)) {
            $dbo->query('UPDATE `co_note_spese` SET `id_stato` = '.prepare($id_stato).' WHERE `id` IN ('.implode(',', $ids).')');
            flash()->info(tr('_COUNT_ spese aggiornate.', ['_COUNT_' => count($ids)]));
        }
        break;

    case 'duplicate_bulk':
        Permissions::check('rw');
        if (empty($ids)) {
            break;
        }

        $id_stato = noteSpeseGetStatusId($dbo, 'da_verificare');
        if (empty($id_stato)) {
            flash()->error(tr('Stato Da verificare non disponibile.'));
            break;
        }

        $rows = $dbo->fetchArray(
            'SELECT n.`id`, n.`data`, n.`id_tipologia`, n.`descrizione`, n.`importo`, n.`id_anagrafica`, n.`id_operatore`, n.`controparte`, n.`note`, t.`enabled` AS `categoria_attiva` '
            .'FROM `co_note_spese` n LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
            .'WHERE n.`id` IN ('.implode(',', $ids).') ORDER BY n.`id` ASC'
        );

        $duplicated = 0;
        $skipped_without_operator = 0;
        $skipped_inactive_category = 0;
        foreach ($rows as $row) {
            if (empty($row['id_operatore'])) {
                ++$skipped_without_operator;
                continue;
            }
            if (empty($row['categoria_attiva'])) {
                ++$skipped_inactive_category;
                continue;
            }

            $dbo->insert('co_note_spese', [
                'data' => $row['data'],
                'id_tipologia' => (int) $row['id_tipologia'],
                'id_stato' => $id_stato,
                'descrizione' => $row['descrizione'],
                'importo' => number_format((float) $row['importo'], 2, '.', ''),
                'id_anagrafica' => !empty($row['id_anagrafica']) ? (int) $row['id_anagrafica'] : null,
                'id_operatore' => (int) $row['id_operatore'],
                'controparte' => $row['controparte'] ?: null,
                'origine' => 'manuale',
                'id_origine' => null,
                'note' => $row['note'] ?: null,
            ]);
            ++$duplicated;
        }

        if ($duplicated > 0) {
            flash()->info(tr('_COUNT_ note spese duplicate. Le copie sono Da verificare e senza allegati.', ['_COUNT_' => $duplicated]));
        }
        if ($skipped_without_operator > 0) {
            flash()->warning(tr('_COUNT_ registrazioni storiche senza Operatore non sono state duplicate.', ['_COUNT_' => $skipped_without_operator]));
        }
        if ($skipped_inactive_category > 0) {
            flash()->warning(tr('_COUNT_ registrazioni con Tipologia non più attiva non sono state duplicate.', ['_COUNT_' => $skipped_inactive_category]));
        }
        if ($duplicated === 0 && $skipped_without_operator === 0 && $skipped_inactive_category === 0) {
            flash()->warning(tr('Nessuna nota spesa duplicata.'));
        }
        break;

    case 'delete_bulk':
        Permissions::check('rw');
        if (empty($ids)) {
            break;
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $exists = $dbo->fetchOne('SELECT `id` FROM `co_note_spese` WHERE `id` = '.prepare($id).' LIMIT 1');
            if (!empty($exists) && noteSpeseDeleteRecord($dbo, $id_module, $id)) {
                ++$deleted;
            }
        }

        if ($deleted > 0) {
            flash()->info(tr('_COUNT_ spese eliminate.', ['_COUNT_' => $deleted]));
        } else {
            flash()->warning(tr('Nessuna spesa eliminata.'));
        }
        break;
}

return [
    'confirm_bulk' => [
        'text' => tr('Conferma'),
        'data' => [
            'title' => tr('Confermare le spese selezionate?'),
            'msg' => tr('Saranno confermate solo le Note spese con Operatore, Tipologia attiva e senza duplicazioni rilevate.'),
            'button' => tr('Conferma'),
            'class' => 'btn btn-lg btn-success',
        ],
    ],
    'review_bulk' => [
        'text' => tr('Segna da verificare'),
        'data' => [
            'title' => tr('Segnare le spese come da verificare?'),
            'msg' => tr('Le spese selezionate non saranno incluse nella stampa, nel CSV e nei totali finché non verranno confermate.'),
            'button' => tr('Segna da verificare'),
            'class' => 'btn btn-lg btn-warning',
        ],
    ],
    'exclude_bulk' => [
        'text' => tr('Escludi'),
        'data' => [
            'title' => tr('Escludere le spese selezionate?'),
            'msg' => tr('Le spese resteranno registrate ma saranno escluse dalla stampa, dal CSV e dai totali.'),
            'button' => tr('Escludi'),
            'class' => 'btn btn-lg btn-secondary',
        ],
    ],
    'duplicate_bulk' => [
        'text' => tr('Duplica'),
        'data' => [
            'title' => tr('Duplicare le note spese selezionate?'),
            'msg' => tr('Verrà creata una copia Da verificare per ogni Nota spesa valida. Le registrazioni senza Operatore o con Tipologia non attiva vengono ignorate.'),
            'button' => tr('Duplica'),
            'class' => 'btn btn-lg btn-primary',
        ],
    ],
    'delete_bulk' => [
        'text' => tr('Elimina'),
        'data' => [
            'title' => tr('Eliminare le spese selezionate?'),
            'msg' => tr('Le spese selezionate e i relativi allegati saranno eliminati definitivamente.'),
            'button' => tr('Elimina'),
            'class' => 'btn btn-lg btn-danger',
        ],
    ],
];
