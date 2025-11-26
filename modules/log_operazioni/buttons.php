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

if (!empty($record['id_record'])) {
    echo '
    <a class="btn btn-primary" href="'.base_path_osm().'/editor.php?id_module='.$record['id_module'].'&id_record='.$record['id_record'].($record['id_plugin'] ? '#tab_'.$record['id_plugin'] : '').'">
        <i class="fa fa-copy"></i> '.tr('Apri scheda').'
    </a>';
}
