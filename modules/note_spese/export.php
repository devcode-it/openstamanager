<?php

include_once __DIR__.'/../../core.php';

$id_module_note_spese = (int) ($dbo->fetchOne('SELECT `id` FROM `zz_modules` WHERE `name` = '.prepare('Note spese').' LIMIT 1')['id'] ?? 0);
if ($id_module_note_spese <= 0) {
    exit(tr('Accesso negato'));
}
Permissions::addModule($id_module_note_spese);
Permissions::check(['r', 'rw']);

$date_start = ($_SESSION['period_start'] ?? date('Y-01-01'));
$date_end = ($_SESSION['period_end'] ?? date('Y-12-31'));
$lang = (int) Models\Locale::getDefault()->id;
$rows = $dbo->fetchArray(
    'SELECT n.*, COALESCE(tl.`title`, t.`descrizione`) AS tipologia, '
    .'COALESCE(NULLIF(n.`controparte`, ""), a.`ragione_sociale`, "") AS controparte_display, '
    .'COALESCE(op.`ragione_sociale`, "") AS operatore, '
    .'(SELECT COUNT(*) FROM `zz_files` f WHERE f.`id_module` = '.prepare($id_module_note_spese).' AND f.`id_plugin` IS NULL '
    .'AND f.`id_record` = n.`id` AND (f.`key` IS NULL OR f.`key` = "")) AS allegati '
    .'FROM `co_note_spese` n '
    .'INNER JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` AND st.`name` = '.prepare('confermato').' '
    .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
    .'LEFT JOIN `co_note_spese_tipologie_lang` tl ON tl.`id_record` = t.`id` AND tl.`id_lang` = '.prepare($lang).' '
    .'LEFT JOIN `an_anagrafiche` a ON a.`id` = n.`id_anagrafica` '
    .'LEFT JOIN `an_anagrafiche` op ON op.`id` = n.`id_operatore` '
    .'WHERE n.`data` >= '.prepare($date_start).' AND n.`data` <= '.prepare($date_end).' '
    .'ORDER BY n.`data`, n.`id`'
);

if (ob_get_length()) {
    ob_clean();
}

$filename = 'note_spese_'.$date_start.'_'.$date_end.'.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, [tr('Data'), tr('Tipologia'), tr('Descrizione'), tr('Controparte'), tr('Operatore'), tr('Importo'), tr('Allegati'), tr('Origine'), tr('Note')], ';', '"', '');

foreach ($rows as $row) {
    fputcsv($out, [
        Translator::dateToLocale($row['data']),
        noteSpeseCsvSafeCell($row['tipologia']),
        noteSpeseCsvSafeCell($row['descrizione']),
        noteSpeseCsvSafeCell($row['controparte_display']),
        noteSpeseCsvSafeCell($row['operatore']),
        number_format((float) $row['importo'], 2, ',', ''),
        (int) $row['allegati'],
        noteSpeseCsvSafeCell(noteSpeseSourceLabel($row['origine'])),
        noteSpeseCsvSafeCell(preg_replace('/\s+/u', ' ', (string) $row['note'])),
    ], ';', '"', '');
}

fclose($out);
exit;
