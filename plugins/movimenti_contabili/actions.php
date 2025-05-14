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

switch (filter('op')) {
    case 'update_conti_movimenti':
        // Aggiornamento dei conti associati ai movimenti
        $idconti = post('idconto');
        
        if (!empty($idconti)) {
            foreach ($idconti as $id_movimento => $id_conto) {
                $dbo->update('co_movimenti', [
                    'idconto' => $id_conto,
                ], [
                    'id' => $id_movimento,
                ]);
            }
            
            flash()->info(tr('Conti aggiornati correttamente!'));
        } else {
            flash()->warning(tr('Nessun movimento da aggiornare!'));
        }
        
        break;
}