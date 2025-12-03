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

include_once __DIR__.'/../../../core.php';

$righe = post('righe');

if (empty($righe) || !is_array($righe)) {
    exit;
}

echo '<table class="table table-striped table-hover table-bordered" id="tabella-inventario">
    <thead>
        <tr>
            <th width="20%">'.tr('Codice').'</th>
            <th width="30%">'.tr('Descrizione').'</th>
            <th width="15%">'.tr('Giacenza attuale').'</th>
            <th width="15%">'.tr('Nuova giacenza').'</th>
            <th width="15%">'.tr('Ubicazione').'</th>
            <th width="5%">'.tr('Azioni').'</th>
        </tr>
    </thead>
    <tbody>';

foreach ($righe as $riga) {
    $id = $riga['id'];
    $codice = $riga['codice'];
    $descrizione = $riga['descrizione'];
    $giacenza_attuale = floatval($riga['giacenza_attuale']);
    $nuova_giacenza = floatval($riga['nuova_giacenza']);
    $ubicazione = $riga['ubicazione'];
    
    echo '<tr data-id="'.$id.'">
        <td>'.$codice.'</td>
        <td>'.$descrizione.'</td>
        <td class="text-right">'.Translator::numberToLocale($giacenza_attuale).'</td>
        <td>';
    
    // Input giacenza con struttura OpenSTAManager
    echo '{[ "type": "number", "name": "giacenza_'.$id.'", "value": "'.$nuova_giacenza.'", "decimals": "qta", "class": "text-right", "onchange": "aggiornaGiacenza('.$id.', this.value)" ]}';
    
    echo '</td>
        <td>';
    
    // Input ubicazione con struttura OpenSTAManager
    echo '{[ "type": "text", "name": "ubicazione_'.$id.'", "value": "'.$ubicazione.'", "placeholder": "'.tr('Ubicazione').'", "onchange": "aggiornaUbicazione('.$id.', this.value)" ]}';
    
    echo '</td>
        <td class="text-center">
            <button type="button" class="btn btn-danger" 
                    onclick="rimuoviRigaInventario('.$id.')" 
                    title="'.tr('Rimuovi').'">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>';
}

echo '</tbody></table>';
