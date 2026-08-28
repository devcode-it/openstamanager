<?php

include_once __DIR__.'/../../core.php';

function noteSpeseStringLength($value)
{
    $value = (string) $value;

    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);

    return $count !== false ? $count : strlen($value);
}

function noteSpeseValidateBaseData($data, $id_tipologia, $descrizione, $importo, $controparte = '')
{
    $descrizione = trim((string) $descrizione);
    $controparte = trim((string) $controparte);

    return !empty(noteSpeseParseDate($data))
        && !empty($id_tipologia)
        && $descrizione !== ''
        && noteSpeseStringLength($descrizione) <= 255
        && noteSpeseStringLength($controparte) <= 255
        && $importo !== null
        && $importo > 0;
}

function noteSpeseIsOutOfScopeCorporateExpense($value)
{
    $value = noteSpeseNormalizeText($value);
    $keywords = [
        'f24', 'tributo', 'tributi', 'inps', 'inail', 'affitto', 'locazione',
        'canone locazione', 'assicurazione', 'assicurazioni', 'polizza', 'polizze',
        'spese bancarie', 'spesa bancaria', 'commissione bancaria', 'commissioni bancarie',
        'stipendio', 'stipendi', 'cedolino', 'cedolini', 'busta paga',
        'compenso amministratore', 'compenso amm',
    ];

    foreach ($keywords as $keyword) {
        if (noteSpeseContains($value, $keyword)) {
            return true;
        }
    }

    return false;
}

function noteSpeseGetAllowedImportCategory($dbo, $value, $explicitCategory = false)
{
    $allowed = ['carburante', 'pedaggio', 'parcheggio', 'vitto', 'alloggio', 'trasporto', 'materiale_consumo', 'altro'];

    // Se l'utente ha indicato esplicitamente una Tipologia esistente nel foglio,
    // rispetta anche le Tipologie custom attive. Il controllo testuale sui costi
    // aziendali si applica solo alla classificazione automatica: una scelta
    // esplicita dell'utente non deve essere reinterpretata dal classificatore.
    if ($explicitCategory) {
        $value = trim((string) $value);
        $lang = (int) Models\Locale::getDefault()->id;
        if ($value !== '') {
            $exact = $dbo->fetchOne(
                'SELECT t.`id`, t.`codice`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
                .'FROM `co_note_spese_tipologie` t '
                .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.prepare($lang).' '
                .'WHERE t.`enabled` = 1 AND ('
                .'LOWER(t.`codice`) = LOWER('.prepare($value).') OR '
                .'LOWER(t.`descrizione`) = LOWER('.prepare($value).') OR '
                .'LOWER(l.`title`) = LOWER('.prepare($value).')) LIMIT 1'
            );
            if (!empty($exact)) {
                // Una Tipologia indicata esplicitamente dall'utente viene rispettata
                // se esiste ed è attiva, incluse le Tipologie custom. Le tipologie
                // aziendali fuori perimetro sono disattivate e quindi non entrano qui.
                return $exact;
            }
        }
    }

    if (noteSpeseIsOutOfScopeCorporateExpense($value)) {
        return null;
    }

    $category = noteSpeseGetCategory($dbo, $value);
    if (empty($category)) {
        return null;
    }

    return in_array((string) ($category['codice'] ?? ''), $allowed, true) ? $category : null;
}

switch (post('op')) {
    case 'inline_update':
        Permissions::check('rw');

        header('Content-Type: application/json; charset=UTF-8');

        $record_id = (int) $id_record;
        $field = trim((string) post('field'));
        $value = post('value');
        $allowed_fields = ['data', 'descrizione', 'controparte', 'importo'];

        if ($record_id <= 0 || !in_array($field, $allowed_fields, true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => tr('Modifica rapida non valida.')]);
            break;
        }

        $current = $dbo->fetchOne('SELECT `id`, `data`, `descrizione`, `controparte`, `importo`, `id_stato` FROM `co_note_spese` WHERE `id` = '.prepare($record_id).' LIMIT 1');
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
            $period_start = $_SESSION['period_start'] ?? date('Y-01-01');
            $period_end = $_SESSION['period_end'] ?? date('Y-12-31');
            $outside_period = !noteSpeseIsDateInPeriod($parsed, $period_start, $period_end);
        } elseif ($field === 'importo') {
            $parsed = noteSpeseParseAmount($value);
            if ($parsed === null || $parsed <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Importo non valido.')]);
                break;
            }

            $update['importo'] = number_format($parsed, 2, '.', '');
        } elseif ($field === 'descrizione') {
            $parsed = trim(strip_tags((string) $value));
            if ($parsed === '' || noteSpeseStringLength($parsed) > 255) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Descrizione non valida.')]);
                break;
            }

            $update['descrizione'] = $parsed;
        } elseif ($field === 'controparte') {
            $parsed = trim(strip_tags((string) $value));
            if (noteSpeseStringLength($parsed) > 255) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => tr('Controparte non valida.')]);
                break;
            }

            $update['controparte'] = $parsed !== '' ? $parsed : null;
        }

        $changed = false;
        if (!empty($update)) {
            $new_value = reset($update);
            $current_value = $current[$field] ?? null;

            if ($field === 'importo') {
                $changed = number_format((float) $current_value, 2, '.', '') !== number_format((float) $new_value, 2, '.', '');
            } elseif ($field === 'controparte') {
                $changed = trim((string) $current_value) !== trim((string) ($new_value ?? ''));
            } else {
                $changed = (string) $current_value !== (string) $new_value;
            }
        }

        $requires_review = false;
        if ($changed) {
            $id_stato_confermato = noteSpeseGetStatusId($dbo, 'confermato');
            $id_stato_verifica = noteSpeseGetStatusId($dbo, 'da_verificare');

            if (!empty($id_stato_confermato) && !empty($id_stato_verifica) && (int) $current['id_stato'] === (int) $id_stato_confermato) {
                $update['id_stato'] = $id_stato_verifica;
                $requires_review = true;
            }

            $dbo->update('co_note_spese', $update, ['id' => $record_id]);
        }

        echo json_encode([
            'success' => true,
            'outside_period' => $outside_period,
            'requires_review' => $requires_review,
            'message' => $requires_review
                ? tr('Nota spesa modificata e riportata Da verificare.')
                : tr('Nota spesa aggiornata.'),
        ]);
        break;

    case 'add':
        Permissions::check('rw');

        $data = noteSpeseParseDate(post('data'));
        $id_tipologia = (int) post('id_tipologia');
        $descrizione = trim(strip_tags((string) post('descrizione')));
        $importo = noteSpeseParseAmount(post('importo'));
        $controparte = trim(strip_tags((string) post('controparte')));
        $id_operatore = (int) post('id_operatore');
        $note = trim((string) post('note'));
        $id_stato_verifica = noteSpeseGetStatusId($dbo, 'da_verificare');
        $valid_category = $dbo->fetchOne('SELECT `id` FROM `co_note_spese_tipologie` WHERE `id` = '.prepare($id_tipologia).' AND `enabled` = 1 LIMIT 1');

        if (!noteSpeseValidateBaseData($data, $id_tipologia, $descrizione, $importo, $controparte) || empty($id_stato_verifica) || empty($valid_category) || $id_operatore <= 0 || !noteSpeseOperatorExists($dbo, $id_operatore)) {
            flash()->error(tr('Compilare correttamente data, tipologia, operatore, descrizione e importo.'));
            break;
        }

        $duplicate = noteSpeseFindDuplicate($dbo, $data, $importo, $descrizione, $controparte, null, $id_operatore);
        if (!empty($duplicate)) {
            $note = noteSpeseAppendNote($note, tr('Possibile duplicato della spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]));
        }

        $dbo->insert('co_note_spese', [
            'data' => $data,
            'id_tipologia' => $id_tipologia,
            'id_stato' => $id_stato_verifica,
            'descrizione' => $descrizione,
            'importo' => $importo,
            'controparte' => $controparte ?: null,
            'id_anagrafica' => null,
            'id_operatore' => $id_operatore,
            'origine' => 'manuale',
            'id_origine' => null,
            'note' => $note ?: null,
        ]);

        $id_record = $dbo->lastInsertedID();

        if (!empty($duplicate)) {
            flash()->warning(tr('Spesa aggiunta come Da verificare: esiste una possibile duplicazione con la spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]));
        } else {
            flash()->info(tr('Spesa aggiunta come Da verificare.'));
        }

        $period_start = $_SESSION['period_start'] ?? date('Y-01-01');
        $period_end = $_SESSION['period_end'] ?? date('Y-12-31');
        if (!noteSpeseIsDateInPeriod($data, $period_start, $period_end)) {
            flash()->warning(tr('La data della spesa è fuori dal periodo attualmente selezionato e la riga non comparirà nell’elenco corrente.'));
        }
        break;

    case 'update':
        Permissions::check('rw');

        if (empty($id_record)) {
            break;
        }

        $data = noteSpeseParseDate(post('data'));
        $id_tipologia = (int) post('id_tipologia');
        $id_stato = (int) post('id_stato');
        $descrizione = trim(strip_tags((string) post('descrizione')));
        $importo = noteSpeseParseAmount(post('importo'));
        $controparte = trim(strip_tags((string) post('controparte')));
        $id_operatore = (int) post('id_operatore');
        $note = trim((string) post('note'));
        $valid_status = $dbo->fetchOne('SELECT `id`, `name` FROM `co_note_spese_stati` WHERE `id` = '.prepare($id_stato).' LIMIT 1');
        $current_record = $dbo->fetchOne('SELECT `data`, `id_tipologia`, `id_stato`, `descrizione`, `importo`, `controparte`, `id_operatore` FROM `co_note_spese` WHERE `id` = '.prepare($id_record).' LIMIT 1') ?: [];
        $current_category_id = (int) ($current_record['id_tipologia'] ?? 0);
        $current_operator_id = (int) ($current_record['id_operatore'] ?? 0);
        $valid_category = $dbo->fetchOne(
            'SELECT `id`, `enabled` FROM `co_note_spese_tipologie` WHERE `id` = '.prepare($id_tipologia)
            .' AND (`enabled` = 1 OR `id` = '.prepare($current_category_id).') LIMIT 1'
        );

        if (!noteSpeseValidateBaseData($data, $id_tipologia, $descrizione, $importo, $controparte) || empty($valid_status) || empty($valid_category) || $id_operatore <= 0 || !noteSpeseOperatorExists($dbo, $id_operatore, $current_operator_id)) {
            flash()->error(tr('Compilare correttamente i dati della spesa, incluso l’Operatore.'));
            break;
        }

        if (($valid_status['name'] ?? '') === 'confermato' && empty($valid_category['enabled'])) {
            flash()->error(tr('Per confermare la Nota spesa selezionare una Tipologia attiva e coerente con il rimborso all’Operatore.'));
            break;
        }

        $id_stato_confermato = noteSpeseGetStatusId($dbo, 'confermato');
        $id_stato_verifica = noteSpeseGetStatusId($dbo, 'da_verificare');
        $substantive_changed = (
            (string) ($current_record['data'] ?? '') !== (string) $data
            || (int) ($current_record['id_tipologia'] ?? 0) !== $id_tipologia
            || trim((string) ($current_record['descrizione'] ?? '')) !== trim((string) $descrizione)
            || number_format((float) ($current_record['importo'] ?? 0), 2, '.', '') !== number_format((float) $importo, 2, '.', '')
            || trim((string) ($current_record['controparte'] ?? '')) !== trim((string) $controparte)
            || (int) ($current_record['id_operatore'] ?? 0) !== $id_operatore
        );

        $reset_to_review = false;
        if (
            $substantive_changed
            && !empty($id_stato_confermato)
            && !empty($id_stato_verifica)
            && (int) ($current_record['id_stato'] ?? 0) === (int) $id_stato_confermato
            && $id_stato === (int) $id_stato_confermato
        ) {
            $id_stato = $id_stato_verifica;
            $reset_to_review = true;
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

        $period_start = $_SESSION['period_start'] ?? date('Y-01-01');
        $period_end = $_SESSION['period_end'] ?? date('Y-12-31');
        if (!noteSpeseIsDateInPeriod($data, $period_start, $period_end)) {
            flash()->warning(tr('La data della spesa è fuori dal periodo attualmente selezionato.'));
        }
        break;

    case 'delete':
        Permissions::check('rw');

        if (!empty($id_record)) {
            if (noteSpeseDeleteRecord($dbo, $id_module, (int) $id_record)) {
                flash()->info(tr('Spesa eliminata correttamente.'));
            } else {
                flash()->error(tr('Impossibile eliminare la spesa.'));
            }
        }
        break;

    case 'import_rifornimenti':
    case 'import_scadenzario':
        Permissions::check('rw');
        flash()->warning(tr('Questa sorgente automatica non è più disponibile: una Nota spesa deve rappresentare un costo anticipato personalmente da un Operatore.'));
        break;

    case 'import_excel':
        Permissions::check('rw');

        $id_operatore = (int) post('id_operatore_excel');
        if ($id_operatore <= 0 || !noteSpeseOperatorExists($dbo, $id_operatore)) {
            flash()->error(tr('Selezionare un Operatore valido per le righe da importare.'));
            break;
        }

        $raw = trim((string) post('righe_excel'));
        if ($raw === '') {
            flash()->warning(tr('Incollare almeno una riga.'));
            break;
        }

        $id_stato = noteSpeseGetStatusId($dbo, 'da_verificare');
        if (empty($id_stato)) {
            flash()->error(tr('Stato Da verificare non disponibile.'));
            break;
        }

        $rows = preg_split('/\R/u', $raw);
        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $possible_duplicates = 0;
        $auto_categories = 0;
        $out_of_scope = 0;
        $dbo->beginTransaction();

        try {
            foreach ($rows as $row) {
                $row = trim($row);
                if ($row === '') {
                    continue;
                }

                $columns = strpos($row, "\t") !== false ? explode("\t", $row) : str_getcsv($row, ';');
                $columns = array_map(static fn ($value) => trim((string) $value), $columns);

                if (!empty($columns[0]) && in_array(noteSpeseLower($columns[0]), ['data', 'date'], true)) {
                    continue;
                }

                $category_raw = '';
                $counterparty = '';
                $user_notes = '';

                if (count($columns) === 3) {
                    [$date_raw, $description, $amount_raw] = $columns;
                    $category = noteSpeseGetAllowedImportCategory($dbo, $description);
                    ++$auto_categories;
                } elseif (count($columns) >= 4) {
                    [$date_raw, $category_raw, $description, $amount_raw] = array_slice($columns, 0, 4);
                    $counterparty = $columns[4] ?? '';
                    $user_notes = $columns[5] ?? '';
                    $category = noteSpeseGetAllowedImportCategory($dbo, $category_raw, true);
                    if (empty($category)) {
                        $category = noteSpeseGetAllowedImportCategory($dbo, trim($category_raw.' '.$description.' '.$counterparty));
                    }
                } else {
                    ++$skipped;
                    continue;
                }

                if (empty($category)) {
                    ++$out_of_scope;
                    continue;
                }

                $date = noteSpeseParseDate($date_raw);
                $amount = noteSpeseParseAmount($amount_raw);
                if ($amount !== null) {
                    $amount = abs($amount);
                }
                $description = trim(strip_tags((string) $description));
                $counterparty = trim(strip_tags((string) $counterparty));

                if (!noteSpeseValidateBaseData($date, (int) $category['id'], $description, $amount, $counterparty)) {
                    ++$skipped;
                    continue;
                }

                $duplicate = noteSpeseFindDuplicate($dbo, $date, $amount, $description, $counterparty, null, $id_operatore);
                if (!empty($duplicate) && ($duplicate['origine'] ?? '') === 'excel') {
                    ++$duplicates;
                    continue;
                }

                $notes = [];
                if ($category_raw !== '' && strcasecmp(trim($category_raw), (string) $category['descrizione']) !== 0 && strcasecmp(trim($category_raw), (string) $category['codice']) !== 0) {
                    $notes[] = tr('Categoria originale: _CATEGORY_', ['_CATEGORY_' => trim($category_raw)]);
                }
                if ($user_notes !== '') {
                    $notes[] = $user_notes;
                }
                if (!empty($duplicate)) {
                    ++$possible_duplicates;
                    $notes[] = tr('Possibile duplicato della spesa #_ID_.', ['_ID_' => (int) $duplicate['id']]);
                }

                $dbo->insert('co_note_spese', [
                    'data' => $date,
                    'id_tipologia' => $category['id'],
                    'id_stato' => $id_stato,
                    'descrizione' => trim($description),
                    'importo' => $amount,
                    'id_anagrafica' => null,
                    'id_operatore' => $id_operatore,
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
        if ($auto_categories > 0) {
            flash()->info(tr('Per _COUNT_ righe la tipologia è stata proposta automaticamente dalla descrizione.', ['_COUNT_' => $auto_categories]));
        }
        if ($out_of_scope > 0) {
            flash()->warning(tr('_COUNT_ righe sono state ignorate perché riconducibili a costi aziendali fuori dall’ambito Note spese.', ['_COUNT_' => $out_of_scope]));
        }
        if ($possible_duplicates > 0) {
            flash()->warning(tr('_COUNT_ righe potrebbero duplicare spese già presenti e sono state segnalate nelle note.', ['_COUNT_' => $possible_duplicates]));
        }
        break;
}
