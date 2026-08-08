<?php

include_once __DIR__.'/../../core.php';

function noteSpeseValidateBaseData($data, $id_tipologia, $descrizione, $importo)
{
    return !empty(noteSpeseParseDate($data)) && !empty($id_tipologia) && trim((string) $descrizione) !== '' && $importo !== null && $importo > 0;
}

switch (post('op')) {
    case 'inline_update':
        Permissions::check('rw');
        header('Content-Type: application/json; charset=UTF-8');

        $record_id = (int) $id_record;
        $field = trim((string) post('field'));
        $value = post('value');
        if ($record_id <= 0 || !in_array($field, ['data', 'descrizione', 'controparte', 'importo'], true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => tr('Modifica rapida non valida.')]);
            break;
        }

        $current = $dbo->fetchOne('SELECT * FROM `co_note_spese` WHERE `id` = '.prepare($record_id).' LIMIT 1');
        if (empty($current)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => tr('Nota spesa non trovata.')]);
            break;
        }

        $update = [];
        $outside_period = false;
        if ($field === 'data') {
            $parsed = noteSpeseParseDate($value);
            if (empty($parsed)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Data non valida.')]);
                break;
            }
            $update['data'] = $parsed;
            $outside_period = !noteSpeseIsDateInPeriod($parsed, $_SESSION['period_start'] ?? date('Y-01-01'), $_SESSION['period_end'] ?? date('Y-12-31'));
        } elseif ($field === 'importo') {
            $parsed = noteSpeseParseAmount($value);
            if ($parsed === null || $parsed <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Importo non valido.')]);
                break;
            }
            $update['importo'] = number_format($parsed, 2, '.', '');
        } else {
            $parsed = trim(strip_tags((string) $value));
            if ($field === 'descrizione' && $parsed === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Descrizione non valida.')]);
                break;
            }
            if ((function_exists('mb_strlen') ? mb_strlen($parsed, 'UTF-8') : strlen($parsed)) > 255) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Valore troppo lungo.')]);
                break;
            }
            $update[$field] = $parsed !== '' ? $parsed : null;
        }

        $new_value = reset($update);
        $old_value = $current[$field] ?? null;
        $changed = $field === 'importo'
            ? number_format((float) $old_value, 2, '.', '') !== number_format((float) $new_value, 2, '.', '')
            : trim((string) $old_value) !== trim((string) ($new_value ?? ''));

        $requires_review = false;
        if ($changed) {
            $confirmed = noteSpeseGetStatusId($dbo, 'confermato');
            $review = noteSpeseGetStatusId($dbo, 'da_verificare');
            if (!empty($confirmed) && !empty($review) && (int) $current['id_stato'] === $confirmed) {
                $update['id_stato'] = $review;
                $requires_review = true;
            }
            $dbo->update('co_note_spese', $update, ['id' => $record_id]);
        }

        echo json_encode([
            'success' => true,
            'outside_period' => $outside_period,
            'requires_review' => $requires_review,
            'message' => $requires_review ? tr('Nota spesa modificata e riportata Da verificare.') : tr('Nota spesa aggiornata.'),
        ]);
        break;

    case 'add':
        Permissions::check('rw');

        $data = noteSpeseParseDate(post('data'));
        $id_tipologia = (int) post('id_tipologia');
        $descrizione = trim((string) post('descrizione'));
        $importo = noteSpeseParseAmount(post('importo'));
        $controparte = trim((string) post('controparte'));
        $id_operatore = (int) post('id_operatore') ?: null;
        $note = trim((string) post('note'));
        $confirmed = noteSpeseGetStatusId($dbo, 'confermato');
        $review = noteSpeseGetStatusId($dbo, 'da_verificare');
        $category = $dbo->fetchOne('SELECT `id` FROM `co_note_spese_tipologie` WHERE `id` = '.prepare($id_tipologia).' AND `enabled` = 1 LIMIT 1');

        if (!noteSpeseValidateBaseData($data, $id_tipologia, $descrizione, $importo) || empty($confirmed) || empty($review) || empty($category) || !noteSpeseOperatorExists($dbo, $id_operatore)) {
            flash()->error(tr('Compilare correttamente data, tipologia, descrizione e importo.'));
            break;
        }

        $duplicate = noteSpeseFindDuplicate($dbo, $data, $importo, $descrizione, $controparte, null, $id_operatore);
        if (!empty($duplicate)) {
            $note = noteSpeseAppendNote($note, tr('Possibile duplicato della spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]));
        }

        $dbo->insert('co_note_spese', [
            'data' => $data,
            'id_tipologia' => $id_tipologia,
            'id_stato' => !empty($duplicate) ? $review : $confirmed,
            'descrizione' => $descrizione,
            'importo' => $importo,
            'id_anagrafica' => null,
            'id_operatore' => $id_operatore,
            'controparte' => $controparte ?: null,
            'origine' => 'manuale',
            'id_origine' => null,
            'note' => $note ?: null,
        ]);
        $id_record = $dbo->lastInsertedID();

        if (!empty($duplicate)) {
            flash()->warning(tr('Spesa aggiunta come Da verificare: esiste una possibile duplicazione con la spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]));
        } else {
            flash()->info(tr('Spesa aggiunta come Confermata.'));
        }
        break;

    case 'update':
        Permissions::check('rw');
        if (empty($id_record)) {
            break;
        }

        $current = $dbo->fetchOne('SELECT * FROM `co_note_spese` WHERE `id` = '.prepare($id_record).' LIMIT 1');
        if (empty($current)) {
            break;
        }

        $data = noteSpeseParseDate(post('data'));
        $id_tipologia = (int) post('id_tipologia');
        $id_stato = (int) post('id_stato');
        $descrizione = trim((string) post('descrizione'));
        $importo = noteSpeseParseAmount(post('importo'));
        $controparte = trim((string) post('controparte'));
        $id_operatore = (int) post('id_operatore') ?: null;
        $note = trim((string) post('note'));

        $valid_status = $dbo->fetchOne('SELECT `id` FROM `co_note_spese_stati` WHERE `id` = '.prepare($id_stato).' LIMIT 1');
        $valid_category = $dbo->fetchOne('SELECT `id` FROM `co_note_spese_tipologie` WHERE `id` = '.prepare($id_tipologia).' AND (`enabled` = 1 OR `id` = '.prepare((int) $current['id_tipologia']).') LIMIT 1');
        if (!noteSpeseValidateBaseData($data, $id_tipologia, $descrizione, $importo) || empty($valid_status) || empty($valid_category) || !noteSpeseOperatorExists($dbo, $id_operatore, $current['id_operatore'] ?? null)) {
            flash()->error(tr('Compilare correttamente i dati della spesa.'));
            break;
        }

        $substantive_changed = (string) $current['data'] !== (string) $data
            || (int) $current['id_tipologia'] !== $id_tipologia
            || trim((string) $current['descrizione']) !== $descrizione
            || number_format((float) $current['importo'], 2, '.', '') !== number_format((float) $importo, 2, '.', '')
            || trim((string) ($current['controparte'] ?? '')) !== $controparte
            || (int) ($current['id_operatore'] ?? 0) !== (int) ($id_operatore ?? 0);

        $confirmed = noteSpeseGetStatusId($dbo, 'confermato');
        $review = noteSpeseGetStatusId($dbo, 'da_verificare');
        $reset_to_review = $substantive_changed && (int) $current['id_stato'] === (int) $confirmed && $id_stato === (int) $confirmed && !empty($review);
        if ($reset_to_review) {
            $id_stato = $review;
        }

        $dbo->update('co_note_spese', [
            'data' => $data,
            'id_tipologia' => $id_tipologia,
            'id_stato' => $id_stato,
            'descrizione' => $descrizione,
            'importo' => $importo,
            'controparte' => $controparte ?: null,
            'id_operatore' => $id_operatore,
            'note' => $note ?: null,
        ], ['id' => $id_record]);

        if ($reset_to_review) {
            flash()->warning(tr('La spesa era Confermata: dopo la modifica è stata riportata Da verificare.'));
        } else {
            flash()->info(tr('Spesa aggiornata correttamente.'));
        }
        break;

    case 'delete':
        Permissions::check('rw');
        if (!empty($id_record) && noteSpeseDeleteRecord($dbo, $id_module, (int) $id_record)) {
            flash()->info(tr('Spesa eliminata correttamente.'));
        }
        break;

    case 'import_rifornimenti':
        Permissions::check('rw');
        $source_module = Models\Module::where('name', 'Automezzi')->first();
        if (empty($source_module)) {
            flash()->error(tr('Modulo origine non disponibile.'));
            break;
        }
        Permissions::addModule($source_module->id);
        Permissions::check(['r', 'rw']);

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) post('rifornimenti')))));
        $category = noteSpeseGetCategory($dbo, 'carburante');
        $review = noteSpeseGetStatusId($dbo, 'da_verificare');
        if (empty($ids) || empty($category) || empty($review)) {
            flash()->warning(tr('Selezionare almeno un rifornimento da importare.'));
            break;
        }

        $period_start = $_SESSION['period_start'] ?? date('Y-01-01');
        $period_end_ts = ($_SESSION['period_end'] ?? date('Y-12-31')).' 23:59:59';
        $imported = 0;
        $skipped = 0;
        $dbo->beginTransaction();
        try {
            foreach ($ids as $id) {
                if (!empty($dbo->fetchOne('SELECT `id` FROM `co_note_spese` WHERE `origine` = '.prepare('automezzi_rifornimento').' AND `id_origine` = '.prepare($id).' LIMIT 1'))) {
                    ++$skipped;
                    continue;
                }

                $source = $dbo->fetchOne(
                    'SELECT r.*, v.`id_tecnico`, v.`id_sede`, s.`nome` AS automezzo_nome, s.`targa`, g.`descrizione` AS gestore, a.`ragione_sociale` AS tecnico '
                    .'FROM `an_automezzi_rifornimenti` r '
                    .'LEFT JOIN `an_automezzi_viaggi` v ON v.`id` = r.`id_viaggio` '
                    .'LEFT JOIN `an_sedi` s ON s.`id` = v.`id_sede` '
                    .'LEFT JOIN `an_automezzi_gestori` g ON g.`id` = r.`id_gestore` '
                    .'LEFT JOIN `an_anagrafiche` a ON a.`id` = v.`id_tecnico` '
                    .'WHERE r.`id` = '.prepare($id).' AND r.`data` >= '.prepare($period_start).' AND r.`data` <= '.prepare($period_end_ts).' LIMIT 1'
                );
                if (empty($source)) {
                    ++$skipped;
                    continue;
                }

                $date = noteSpeseParseDate(substr((string) $source['data'], 0, 10));
                $amount = noteSpeseParseAmount($source['costo']);
                if (empty($date) || $amount === null || $amount <= 0) {
                    ++$skipped;
                    continue;
                }

                $vehicle = trim(($source['automezzo_nome'] ?: '').(!empty($source['targa']) ? ' - '.$source['targa'] : ''));
                $description = tr('Rifornimento').($vehicle !== '' ? ' - '.$vehicle : '');
                $counterparty = trim((string) ($source['gestore'] ?: $source['luogo'] ?: ''));
                $id_operatore = !empty($source['id_tecnico']) && noteSpeseAnagraficaExists($dbo, $source['id_tecnico']) ? (int) $source['id_tecnico'] : null;
                $notes = [tr('Importato dal registro rifornimenti.')];
                $duplicate = noteSpeseFindDuplicate($dbo, $date, $amount, $description, $counterparty, null, $id_operatore);
                if (!empty($duplicate)) {
                    $notes[] = tr('Possibile duplicato della spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]);
                }

                $dbo->insert('co_note_spese', [
                    'data' => $date,
                    'id_tipologia' => $category['id'],
                    'id_stato' => $review,
                    'descrizione' => $description,
                    'importo' => $amount,
                    'id_anagrafica' => null,
                    'id_operatore' => $id_operatore,
                    'controparte' => $counterparty ?: null,
                    'origine' => 'automezzi_rifornimento',
                    'id_origine' => $id,
                    'note' => implode("\n", $notes),
                ]);
                ++$imported;
            }
            $dbo->commitTransaction();
        } catch (Throwable $e) {
            $dbo->rollbackTransaction();
            throw $e;
        }
        flash()->info(tr('Importazione completata: _IMPORTED_ importate, _SKIPPED_ ignorate.', ['_IMPORTED_' => $imported, '_SKIPPED_' => $skipped]));
        break;

    case 'import_scadenzario':
        Permissions::check('rw');
        $source_module = Models\Module::where('name', 'Scadenzario')->first();
        if (empty($source_module)) {
            flash()->error(tr('Modulo origine non disponibile.'));
            break;
        }
        Permissions::addModule($source_module->id);
        Permissions::check(['r', 'rw']);

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) post('scadenze')))));
        $review = noteSpeseGetStatusId($dbo, 'da_verificare');
        $period_start = $_SESSION['period_start'] ?? date('Y-01-01');
        $period_end = $_SESSION['period_end'] ?? date('Y-12-31');
        $imported = 0;
        $skipped = 0;
        if (empty($ids) || empty($review)) {
            flash()->warning(tr('Selezionare almeno una scadenza da importare.'));
            break;
        }

        $dbo->beginTransaction();
        try {
            foreach ($ids as $id) {
                if (!empty($dbo->fetchOne('SELECT `id` FROM `co_note_spese` WHERE `origine` = '.prepare('scadenzario_generico').' AND `id_origine` = '.prepare($id).' LIMIT 1'))) {
                    ++$skipped;
                    continue;
                }

                $source = $dbo->fetchOne(
                    'SELECT s.*, a.`ragione_sociale` FROM `co_scadenzario` s '
                    .'LEFT JOIN `an_anagrafiche` a ON a.`id` = s.`id_anagrafica` '
                    .'WHERE s.`id` = '.prepare($id).' AND (s.`id_documento` IS NULL OR s.`id_documento` = 0) '
                    .'AND s.`da_pagare` < 0 AND s.`scadenza` >= '.prepare($period_start).' AND s.`scadenza` <= '.prepare($period_end).' LIMIT 1'
                );
                if (empty($source)) {
                    ++$skipped;
                    continue;
                }

                $date = noteSpeseParseDate(substr((string) $source['scadenza'], 0, 10));
                $amount = abs((float) $source['da_pagare']);
                $category = noteSpeseGetCategory($dbo, trim((string) $source['descrizione']).' '.trim((string) $source['tipo']).' '.trim((string) $source['ragione_sociale']));
                if (empty($date) || $amount <= 0 || empty($category)) {
                    ++$skipped;
                    continue;
                }

                $description = trim((string) $source['descrizione']) ?: tr('Scadenza generica');
                $counterparty = trim((string) $source['ragione_sociale']);
                $notes = [tr('Importato da Scadenzario generico. Verificare competenza e documentazione prima della conferma.')];
                $duplicate = noteSpeseFindDuplicate($dbo, $date, $amount, $description, $counterparty);
                if (!empty($duplicate)) {
                    $notes[] = tr('Possibile duplicato della spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]);
                }

                $id_anagrafica = !empty($source['id_anagrafica']) && noteSpeseAnagraficaExists($dbo, $source['id_anagrafica']) ? (int) $source['id_anagrafica'] : null;
                $dbo->insert('co_note_spese', [
                    'data' => $date,
                    'id_tipologia' => $category['id'],
                    'id_stato' => $review,
                    'descrizione' => $description,
                    'importo' => $amount,
                    'id_anagrafica' => $id_anagrafica,
                    'id_operatore' => null,
                    'controparte' => $counterparty ?: null,
                    'origine' => 'scadenzario_generico',
                    'id_origine' => $id,
                    'note' => implode("\n", $notes),
                ]);
                ++$imported;
            }
            $dbo->commitTransaction();
        } catch (Throwable $e) {
            $dbo->rollbackTransaction();
            throw $e;
        }
        flash()->info(tr('Importazione Scadenzario completata: _IMPORTED_ importate, _SKIPPED_ ignorate.', ['_IMPORTED_' => $imported, '_SKIPPED_' => $skipped]));
        break;

    case 'import_excel':
        Permissions::check('rw');
        $raw = trim((string) post('righe_excel'));
        if ($raw === '') {
            flash()->warning(tr('Incollare almeno una riga.'));
            break;
        }

        $review = noteSpeseGetStatusId($dbo, 'da_verificare');
        $rows = preg_split('/\R/u', $raw);
        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $dbo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $row = trim($row);
                if ($row === '') {
                    continue;
                }
                $columns = strpos($row, "\t") !== false ? explode("\t", $row) : str_getcsv($row, ';');
                $columns = array_map(static fn ($item) => trim((string) $item), $columns);
                if (!empty($columns[0]) && in_array(noteSpeseLower($columns[0]), ['data', 'date'], true)) {
                    continue;
                }

                $category_raw = '';
                $counterparty = '';
                $user_notes = '';
                if (count($columns) === 3) {
                    [$date_raw, $description, $amount_raw] = $columns;
                    $category = noteSpeseGetCategory($dbo, $description);
                } elseif (count($columns) >= 4) {
                    [$date_raw, $category_raw, $description, $amount_raw] = array_slice($columns, 0, 4);
                    $counterparty = $columns[4] ?? '';
                    $user_notes = $columns[5] ?? '';
                    $category = noteSpeseGetCategory($dbo, $category_raw);
                } else {
                    ++$skipped;
                    continue;
                }

                $date = noteSpeseParseDate($date_raw);
                $amount = noteSpeseParseAmount($amount_raw);
                $amount = $amount !== null ? abs($amount) : null;
                if (empty($date) || empty($category) || trim($description) === '' || $amount === null || $amount <= 0 || empty($review)) {
                    ++$skipped;
                    continue;
                }

                $duplicate = noteSpeseFindDuplicate($dbo, $date, $amount, $description, $counterparty);
                if (!empty($duplicate) && ($duplicate['origine'] ?? '') === 'excel') {
                    ++$duplicates;
                    continue;
                }

                $notes = [];
                if ($category_raw !== '' && strcasecmp($category_raw, (string) $category['descrizione']) !== 0 && strcasecmp($category_raw, (string) $category['codice']) !== 0) {
                    $notes[] = tr('Categoria originale: _CATEGORY_', ['_CATEGORY_' => $category_raw]);
                }
                if ($user_notes !== '') {
                    $notes[] = $user_notes;
                }
                if (!empty($duplicate)) {
                    $notes[] = tr('Possibile duplicato della spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]);
                }

                $dbo->insert('co_note_spese', [
                    'data' => $date,
                    'id_tipologia' => $category['id'],
                    'id_stato' => $review,
                    'descrizione' => trim($description),
                    'importo' => $amount,
                    'id_anagrafica' => null,
                    'id_operatore' => null,
                    'controparte' => trim($counterparty) ?: null,
                    'origine' => 'excel',
                    'id_origine' => null,
                    'note' => !empty($notes) ? implode("\n", $notes) : null,
                ]);
                ++$imported;
            }
            $dbo->commitTransaction();
        } catch (Throwable $e) {
            $dbo->rollbackTransaction();
            throw $e;
        }
        flash()->info(tr('Importazione completata: _IMPORTED_ importate, _SKIPPED_ non valide, _DUPLICATES_ duplicate già importate ignorate.', [
            '_IMPORTED_' => $imported,
            '_SKIPPED_' => $skipped,
            '_DUPLICATES_' => $duplicates,
        ]));
        break;
}
