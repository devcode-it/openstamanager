<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<div class="box box-info collapsable" style="'.((strtolower($record['colore']) == '#ffffff' or empty($record['colore'])) ? '' : 'border-color: '.$record['colore']).'">

        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-user"></i> '.$record['ragione_sociale'].'</h3>
            <div class="box-tools pull-right">
                '.Modules::link('Anagrafiche', $record['idanagrafica']).'
            </div>
        </div>

        <div class="box-body">

        <table class="table table-striped table-condensed">

        <tr>
            <th>'.tr('Attività').'</th>

            <th>
                '.tr('Addebito orario').'
                <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span>
            </th>
            <th>
                '.tr('Addebito km').'
                <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span>
            </th>
            <th>
                '.tr('Addebito diritto ch.').'
                <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span>
            </th>

            <th>
                '.tr('Costo orario').'
                <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span>
            </th>
            <th>
                '.tr('Costo km').'
                <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span>
            </th>
            <th>
                '.tr('Costo diritto ch.').'
                <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span>
            </th>

            <th width="40"></th>
        </tr>';

        // Tipi di interventi
        foreach ($tipi_interventi as $tipo_intervento) {
            echo '
        <tr>

            <td>'.$tipo_intervento['descrizione'].'</td>

            <td>
                {[ "type": "number", "name": "costo_ore['.$tipo_intervento['id'].']", "required": 1, "value": "'.$tipo_intervento['costo_ore'].'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "costo_km['.$tipo_intervento['id'].']", "required": 1, "value": "'.$tipo_intervento['costo_km'].'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "costo_dirittochiamata['.$tipo_intervento['id'].']", "required": 1, "value": "'.$tipo_intervento['costo_dirittochiamata'].'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "costo_ore_tecnico['.$tipo_intervento['id'].']", "required": 1, "value": "'.$tipo_intervento['costo_ore_tecnico'].'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "costo_km_tecnico['.$tipo_intervento['id'].']", "required": 1, "value": "'.$tipo_intervento['costo_km_tecnico'].'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "costo_dirittochiamata_tecnico['.$tipo_intervento['id'].']", "required": 1, "value": "'.$tipo_intervento['costo_dirittochiamata_tecnico'].'" ]}
            </td>

            <td>
                <a class="btn btn-warning ask" data-backto="record-edit" data-method="post" data-op="import" data-idtipointervento="'.$tipo_intervento['id'].'" data-msg="'.tr('Vuoi importare la tariffa standard?').'" data-button="'.tr('Importa').'" data-class="btn btn-lg btn-info">
                    <i class="fa fa-download"></i>
                </a>
            </td>
        </tr>';
        }
echo '
    </table>
    </div>
</div>';
