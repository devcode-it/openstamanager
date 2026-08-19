<?php

/**
 * Funzioni di supporto per il modulo Note spese.
 * Le funzioni sono prefissate per evitare collisioni con il core.
 */

function noteSpeseLower($value)
{
    $value = (string) $value;

    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function noteSpeseContains($haystack, $needle)
{
    if (function_exists('mb_strpos')) {
        return mb_strpos((string) $haystack, (string) $needle, 0, 'UTF-8') !== false;
    }

    return strpos((string) $haystack, (string) $needle) !== false;
}

function noteSpeseNormalizeText($value)
{
    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/\s+/u', ' ', $value);

    return noteSpeseLower($value ?: '');
}

function noteSpeseCsvSafeCell($value)
{
    $value = (string) $value;

    // Evita l'esecuzione di formule quando il CSV viene aperto con un foglio
    // elettronico. L'apostrofo forza l'interpretazione testuale della cella.
    if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $value)) {
        return "'".$value;
    }

    return $value;
}

function noteSpeseParseAmount($value)
{
    $value = trim((string) $value);
    $value = str_replace(['€', ' ', "\xc2\xa0"], '', $value);

    if ($value === '') {
        return null;
    }

    $lastComma = strrpos($value, ',');
    $lastDot = strrpos($value, '.');

    // Quando sono presenti entrambi i separatori, l'ultimo viene considerato
    // decimale e l'altro separatore viene trattato come separatore delle migliaia.
    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($lastComma !== false || $lastDot !== false) {
        $separator = $lastComma !== false ? ',' : '.';
        $parts = explode($separator, $value);

        if (count($parts) > 2) {
            $groupsAreThousands = true;
            foreach (array_slice($parts, 1) as $part) {
                if (strlen($part) !== 3 || !ctype_digit($part)) {
                    $groupsAreThousands = false;
                    break;
                }
            }

            if ($groupsAreThousands) {
                $value = implode('', $parts);
            } else {
                $decimal = array_pop($parts);
                $value = implode('', $parts).'.'.$decimal;
            }
        } else {
            [$integer, $decimal] = array_pad($parts, 2, '');
            // Nel registro gli importi hanno due decimali: un singolo gruppo di
            // tre cifre viene quindi interpretato come separatore delle migliaia.
            if ($decimal !== '' && strlen($decimal) === 3 && ctype_digit(ltrim($integer, '+-')) && ctype_digit($decimal)) {
                $value = $integer.$decimal;
            } elseif ($separator === ',') {
                $value = str_replace(',', '.', $value);
            }
        }
    }

    return is_numeric($value) ? round((float) $value, 2) : null;
}

function noteSpeseParseDate($value)
{
    $value = trim((string) $value);
    $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y'];

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat('!'.$format, $value);
        if ($date && $date->format($format) === $value) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}

function noteSpeseIsDateInPeriod($date, $periodStart, $periodEnd)
{
    $date = noteSpeseParseDate($date);
    $periodStart = noteSpeseParseDate($periodStart);
    $periodEnd = noteSpeseParseDate($periodEnd);

    return !empty($date) && !empty($periodStart) && !empty($periodEnd) && $date >= $periodStart && $date <= $periodEnd;
}

function noteSpeseGuessCategoryCode($value)
{
    $value = noteSpeseLower(trim(strip_tags((string) $value)));

    // L'auto-classificazione riguarda soltanto tipologie coerenti con una
    // spesa anticipata personalmente dall'Operatore. I costi aziendali dismessi
    // (assicurazioni, affitti, tributi, spese bancarie, personale) non vengono
    // più proposti né dedotti automaticamente.
    $rules = [
        'carburante' => ['carburante', 'benzina', 'diesel', 'gasolio', 'rifornimento'],
        'pedaggio' => ['pedaggio', 'autostrada', 'telepass'],
        'parcheggio' => ['parcheggio', 'parking', 'sosta'],
        'vitto' => ['pranzo', 'cena', 'ristorante', 'ristorazione', 'bar', 'vitto'],
        'alloggio' => ['hotel', 'albergo', 'alloggio', 'pernottamento'],
        'trasporto' => ['taxi', 'treno', 'aereo', 'trasporto', 'bus', 'autobus'],
        'materiale_consumo' => ['materiale di consumo', 'consumabile', 'cancelleria'],
    ];

    foreach ($rules as $code => $keywords) {
        foreach ($keywords as $keyword) {
            if (noteSpeseContains($value, $keyword)) {
                return $code;
            }
        }
    }

    return 'altro';
}

function noteSpeseFindDuplicate($dbo, $date, $amount, $description, $counterparty = '', $excludeId = null, $operatorId = null)
{
    $date = noteSpeseParseDate($date);
    $amount = noteSpeseParseAmount($amount);
    $description = noteSpeseNormalizeText($description);
    $counterparty = noteSpeseNormalizeText($counterparty);
    $operatorId = (int) $operatorId;

    if (empty($date) || $amount === null || $amount <= 0 || $description === '') {
        return null;
    }

    $whereExclude = !empty($excludeId) ? ' AND `id` != '.prepare((int) $excludeId) : '';

    return $dbo->fetchOne(
        'SELECT n.`id`, n.`data`, n.`importo`, n.`descrizione`, n.`controparte`, n.`origine`, n.`id_stato` '
        .'FROM `co_note_spese` n '
        .'LEFT JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` '
        .'WHERE n.`data` = '.prepare($date)
        .' AND n.`importo` = '.prepare(number_format($amount, 2, '.', ''))
        .' AND LOWER(TRIM(n.`descrizione`)) = '.prepare($description)
        .' AND LOWER(TRIM(COALESCE(n.`controparte`, ""))) = '.prepare($counterparty)
        .' AND COALESCE(n.`id_operatore`, 0) = '.prepare($operatorId)
        .' AND COALESCE(st.`name`, "") != '.prepare('escluso')
        .str_replace('`id`', 'n.`id`', $whereExclude)
        .' ORDER BY n.`id` ASC LIMIT 1'
    );
}

function noteSpeseAnagraficaExists($dbo, $idAnagrafica)
{
    $idAnagrafica = (int) $idAnagrafica;
    if ($idAnagrafica <= 0) {
        return false;
    }

    return !empty($dbo->fetchOne(
        'SELECT `id` FROM `an_anagrafiche` WHERE `id` = '.prepare($idAnagrafica).' LIMIT 1'
    ));
}

function noteSpeseOperatorSelectQuery($currentId = null)
{
    $currentId = (int) $currentId;

    $activeTechnician = '(a.`deleted_at` IS NULL '
        .'AND EXISTS ('
        .'SELECT 1 FROM `an_tipi_anagrafiche_anagrafiche` ta '
        .'INNER JOIN `an_tipi_anagrafiche` t ON t.`id` = ta.`id_tipo_anagrafica` '
        .'WHERE ta.`id_anagrafica` = a.`id` AND t.`name` = \'Tecnico\') '
        .'AND (NOT EXISTS (SELECT 1 FROM `zz_users` ux WHERE ux.`id_anagrafica` = a.`id`) '
        .'OR EXISTS (SELECT 1 FROM `zz_users` ua WHERE ua.`id_anagrafica` = a.`id` AND ua.`enabled` = 1)))';

    $where = $currentId > 0
        ? '('.$activeTechnician.' OR a.`id` = '.$currentId.')'
        : $activeTechnician;

    return 'SELECT DISTINCT a.`id` AS `id`, '
        .'CONCAT(a.`ragione_sociale`, IF(COALESCE(a.`codice`, \'\') = \'\', \'\', CONCAT(\' - \', a.`codice`)), '
        .'IF(('.$activeTechnician.'), \'\', \' (non attivo)\')) AS `descrizione` '
        .'FROM `an_anagrafiche` a '
        .'WHERE '.$where.' '
        .'ORDER BY a.`ragione_sociale`';
}

function noteSpeseOperatorExists($dbo, $operatorId, $allowedCurrentId = null)
{
    $operatorId = (int) $operatorId;
    $allowedCurrentId = (int) $allowedCurrentId;
    if ($operatorId <= 0) {
        return true;
    }

    if ($allowedCurrentId > 0 && $operatorId === $allowedCurrentId) {
        return !empty($dbo->fetchOne(
            'SELECT `id` FROM `an_anagrafiche` WHERE `id` = '.prepare($operatorId).' LIMIT 1'
        ));
    }

    return !empty($dbo->fetchOne(
        'SELECT a.`id` FROM `an_anagrafiche` a '
        .'WHERE a.`id` = '.prepare($operatorId).' '
        .'AND a.`deleted_at` IS NULL '
        .'AND EXISTS ('
        .'SELECT 1 FROM `an_tipi_anagrafiche_anagrafiche` ta '
        .'INNER JOIN `an_tipi_anagrafiche` t ON t.`id` = ta.`id_tipo_anagrafica` '
        .'WHERE ta.`id_anagrafica` = a.`id` AND t.`name` = '.prepare('Tecnico').') '
        .'AND (NOT EXISTS (SELECT 1 FROM `zz_users` ux WHERE ux.`id_anagrafica` = a.`id`) '
        .'OR EXISTS (SELECT 1 FROM `zz_users` ua WHERE ua.`id_anagrafica` = a.`id` AND ua.`enabled` = 1)) '
        .'LIMIT 1'
    ));
}

function noteSpeseAppendNote($note, $line)
{
    $note = trim((string) $note);
    $line = trim((string) $line);

    if ($line === '') {
        return $note !== '' ? $note : null;
    }

    if ($note === '') {
        return $line;
    }

    if (noteSpeseContains($note, $line)) {
        return $note;
    }

    return $note."\n".$line;
}

function noteSpeseDeleteRecord($dbo, $idModule, $idRecord)
{
    $idModule = (int) $idModule;
    $idRecord = (int) $idRecord;
    if ($idModule <= 0 || $idRecord <= 0) {
        return false;
    }

    Uploads::deleteLinked([
        'id_module' => $idModule,
        'id_plugin' => null,
        'id_record' => $idRecord,
        'key' => null,
    ]);

    return (bool) $dbo->delete('co_note_spese', ['id' => $idRecord]);
}

function noteSpeseSourceLabel($source)
{
    return match ((string) $source) {
        'automezzi_rifornimento' => tr('Automezzi'),
        'scadenzario_generico' => tr('Scadenzario'),
        'excel' => tr('Importazione dati'),
        default => tr('Manuale'),
    };
}

function noteSpeseGetStatusId($dbo, $name)
{
    $row = $dbo->fetchOne('SELECT `id` FROM `co_note_spese_stati` WHERE `name` = '.prepare($name).' LIMIT 1');

    return !empty($row['id']) ? (int) $row['id'] : null;
}

function noteSpeseGetCategory($dbo, $value)
{
    $value = trim((string) $value);
    $lang = (int) Models\Locale::getDefault()->id;

    if ($value !== '') {
        $row = $dbo->fetchOne(
            'SELECT t.`id`, t.`codice`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
            .'FROM `co_note_spese_tipologie` t '
            .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.prepare($lang).' '
            .'WHERE t.`enabled` = 1 AND ('
            .'LOWER(t.`codice`) = LOWER('.prepare($value).') OR '
            .'LOWER(t.`descrizione`) = LOWER('.prepare($value).') OR '
            .'LOWER(l.`title`) = LOWER('.prepare($value).')) LIMIT 1'
        );
        if (!empty($row)) {
            return $row;
        }
    }

    $code = noteSpeseGuessCategoryCode($value);
    $row = $dbo->fetchOne(
        'SELECT t.`id`, t.`codice`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
        .'FROM `co_note_spese_tipologie` t '
        .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.prepare($lang).' '
        .'WHERE t.`enabled` = 1 AND t.`codice` = '.prepare($code).' LIMIT 1'
    );

    if (!empty($row)) {
        return $row;
    }

    // Per valori non riconosciuti usa esclusivamente la tipologia Altro, se
    // attiva. Non ripiega sulla prima tipologia disponibile: eviterebbe errori
    // di classificazione silenziosi (es. una voce non riconosciuta come Carburante).
    return $dbo->fetchOne(
        'SELECT t.`id`, t.`codice`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
        .'FROM `co_note_spese_tipologie` t '
        .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.prepare($lang).' '
        .'WHERE t.`enabled` = 1 AND t.`codice` = '.prepare('altro').' LIMIT 1'
    );
}
