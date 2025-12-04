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

// Pulsante centrale per aggiungere inventario
echo '
<div class="row">
    <div class="col-md-12 text-center">
        <button type="button" class="btn btn-primary btn-lg" onclick="openModal(\''.tr('Inventario').'\', \''.base_path_osm().'/modules/movimenti/modals/inventario.php?id_module='.$id_module.'\');">
            '.tr('Aggiorna inventario').'
        </button>
    </div>
</div>';
