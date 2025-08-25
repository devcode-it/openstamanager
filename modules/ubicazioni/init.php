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

include_once __DIR__.'/../../core.php';
//use Modules\Articoli\Categoria;
use Modules\Ubicazioni;

if (!empty($id_record)) {
    //cambio query dopo l'aggiunta di mg_ubicazioni_lang
    //$record = $dbo->fetchOne('SELECT * FROM `mg_ubicazioni` WHERE id='.prepare($id_record));
    $record = $dbo->fetchOne('SELECT `mg_ubicazioni`.*, `mg_ubicazioni_lang`.`title`, `mg_ubicazioni_lang`.`notes` FROM `mg_ubicazioni` LEFT JOIN `mg_ubicazioni_lang` ON (`mg_ubicazioni`.`id`=`mg_ubicazioni_lang`.`id_record` AND `mg_ubicazioni_lang`.`id_lang`='.prepare(parameter: Models\Locale::getDefault()->id).') WHERE `mg_ubicazioni`.`id`='.prepare($id_record));

}