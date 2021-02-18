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

// Informazioni generali sulla riga
$source_type = $source_type ?: filter('source_type');
$source_id = $source_id ?: filter('source_id');
if (empty($source_type) || empty($source_id)) {
    return;
}

$source = $source_type::find($source_id);

$riferimenti = $source->referenceTargets;
$elenco_riferimenti = [];
if (!$riferimenti->isEmpty()) {
    echo '
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Riferimento').'</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($riferimenti as $riferimento) {
        $riga = $riferimento->target;
        $riga_class = get_class($source);

        echo '
        <tr data-id="'.$riga->id.'" data-type="'.$riga_class.'">
            <td>
                <button type="button" class="btn btn-xs btn-danger pull-right" onclick="rimuoviRiferimento(this, \''.addslashes($source_type).'\', \''.$source_id.'\', \''.$riferimento->id.'\')">
                    <i class="fa fa-trash"></i>
                </button>

                '.$riferimento->target->descrizione.'

                <br>
                <small>'.reference($riferimento->target->getDocument()).'</small>
            </td>
        </tr>';

        // Aggiunta all'elenco dei riferimenti
        $elenco_riferimenti[] = addslashes($riga_class).'|'.$riga->id;
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessun riferimento presente').'.
</div>';
}
