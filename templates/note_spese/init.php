<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Stampa riepilogativa del modulo Note spese.
 *
 * @author sajotrei
 * @link https://github.com/sajotrei
 */

include_once __DIR__.'/../../core.php';

// La stampa e' di periodo e non e' legata a una singola anagrafica/documento.
// Questi valori evitano riferimenti indefiniti quando il loader standard include
// templates/info.php per predisporre i placeholder OSM.
$id_cliente = 0;
$idcliente = 0;
$id_sede = -1;
$tipo_cliente = '';

$date_start = ($_SESSION['period_start'] ?? date('Y-01-01'));
$date_end = ($_SESSION['period_end'] ?? date('Y-12-31'));
$id_lang = (int) Models\Locale::getDefault()->id;
$id_module_note_spese = (int) ($dbo->fetchOne('SELECT `id` FROM `zz_modules` WHERE `name` = '.prepare('Note spese').' LIMIT 1')['id'] ?? 0);

$rows = $dbo->fetchArray(
    'SELECT n.*, COALESCE(tl.`title`, t.`descrizione`) AS tipologia, '
    .'COALESCE(NULLIF(n.`controparte`, ""), a.`ragione_sociale`, "") AS controparte_display, '
    .'COALESCE(op.`ragione_sociale`, "") AS operatore, '
    .'(SELECT COUNT(*) FROM `zz_files` f WHERE f.`id_module` = '.prepare($id_module_note_spese).' AND f.`id_plugin` IS NULL '
    .'AND f.`id_record` = n.`id` AND (f.`key` IS NULL OR f.`key` = "")) AS allegati '
    .'FROM `co_note_spese` n '
    .'INNER JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` AND st.`name` = '.prepare('confermato').' '
    .'INNER JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
    .'LEFT JOIN `co_note_spese_tipologie_lang` tl ON tl.`id_record` = t.`id` AND tl.`id_lang` = '.prepare($id_lang).' '
    .'LEFT JOIN `an_anagrafiche` a ON a.`id` = n.`id_anagrafica` '
    .'LEFT JOIN `an_anagrafiche` op ON op.`id` = n.`id_operatore` '
    .'WHERE n.`data` >= '.prepare($date_start).' AND n.`data` <= '.prepare($date_end).' '
    .'ORDER BY n.`data` ASC, n.`id` ASC'
);

$groups = $dbo->fetchArray(
    'SELECT COALESCE(tl.`title`, t.`descrizione`, '.prepare(tr('Senza tipologia')).') AS tipologia, SUM(n.`importo`) AS totale, COUNT(*) AS righe '
    .'FROM `co_note_spese` n '
    .'INNER JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` AND st.`name` = '.prepare('confermato').' '
    .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
    .'LEFT JOIN `co_note_spese_tipologie_lang` tl ON tl.`id_record` = t.`id` AND tl.`id_lang` = '.prepare($id_lang).' '
    .'WHERE n.`data` >= '.prepare($date_start).' AND n.`data` <= '.prepare($date_end).' '
    .'GROUP BY t.`id`, tl.`title`, t.`descrizione`, t.`ordine` '
    .'ORDER BY t.`ordine`, tipologia'
);

$without_attachments = 0;
foreach ($rows as $row) {
    if (empty($row['allegati'])) {
        ++$without_attachments;
    }
}
