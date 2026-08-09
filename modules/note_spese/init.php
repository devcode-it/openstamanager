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
 * Modulo Note spese.
 *
 * @author sajotrei
 * @link https://github.com/sajotrei
 */

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $lang = (int) Models\Locale::getDefault()->id;
    $record = $dbo->fetchOne(
        'SELECT n.*, COALESCE(tl.`title`, t.`descrizione`) AS `tipologia`, '
        .'COALESCE(sl.`title`, st.`name`) AS `stato`, st.`name` AS `stato_name`, st.`colore` AS `stato_colore` '
        .'FROM `co_note_spese` n '
        .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
        .'LEFT JOIN `co_note_spese_tipologie_lang` tl ON tl.`id_record` = t.`id` AND tl.`id_lang` = '.prepare($lang).' '
        .'LEFT JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` '
        .'LEFT JOIN `co_note_spese_stati_lang` sl ON sl.`id_record` = st.`id` AND sl.`id_lang` = '.prepare($lang).' '
        .'WHERE n.`id` = '.prepare($id_record)
    );
}
