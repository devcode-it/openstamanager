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
 * Tipologie del modulo Note spese.
 *
 * @author sajotrei
 * @link https://github.com/sajotrei
 */

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $id_lang = (int) Models\Locale::getDefault()->id;
    $record = $dbo->fetchOne(
        'SELECT t.*, COALESCE(l.`title`, t.`descrizione`) AS `title` '
        .'FROM `co_note_spese_tipologie` t '
        .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.prepare($id_lang).' '
        .'WHERE t.`id` = '.prepare($id_record).' LIMIT 1'
    );
}
