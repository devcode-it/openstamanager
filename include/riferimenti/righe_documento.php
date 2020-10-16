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

// Generazione della tabella per l'aggiunta dei riferimenti
$id_documento = filter('id_documento');
$tipo_documento = filter('tipo_documento');
if (empty($tipo_documento) || empty($id_documento)) {
    return;
}
$documento = $tipo_documento::find($id_documento);

// Informazioni sulla riga
$source_type = filter('source_type');
$source_id = filter('source_id');
$riferimenti = (array) filter('riferimenti');

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th>'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
        <th class="text-center">#</th>
    </tr>

    <tbody>';

    $righe = $documento->getRighe();
    foreach ($righe as $riga) {
        $riga_class = get_class($riga);

        $riferimento_locale = $riga_class.'|'.$riga->id;
        $presente = in_array($riferimento_locale, $riferimenti);

        echo '
    <tr data-id="'.$riga->id.'" data-type="'.$riga_class.'">
        <td>'.$riga->descrizione.'</td>
        <td>'.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').'</td>
        <td class="text-center">
            <button type="button" class="btn btn-'.($presente ? 'success' : 'info').' btn-xs" '.($presente ? '' : 'onclick="salvaRiferimento(this, \''.addslashes($source_type).'\',\''.$source_id.'\')"').'>
                <i class="fa fa-check"></i>
            </button>
        </td>
    </tr>';
    }

    echo '
    </tbody>
</table>';
