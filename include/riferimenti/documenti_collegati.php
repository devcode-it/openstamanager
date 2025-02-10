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

use Modules\Articoli\Articolo;

// Informazioni generali sulla riga
$source_type = filter('riga_type');
$source_id = filter('riga_id');
if (empty($source_type) || empty($source_id)) {
    return;
}

$source = $source_type::find($source_id);
$documenti_destinazione = getDestinationComponents($source);

echo '
<div class="card card-primary collapsable">
    <div class="card-header with-border">
        <h3 class="card-title">';
if ($source->isArticolo()) {
    $articolo_riga = Articolo::find($source->idarticolo);
    echo $articolo_riga->codice.' - '.$source->descrizione;
} else {
    echo nl2br($source->descrizione);
}
echo '
        </h3>
        <div class="card-tools pull-right">
            '.tr('Quantità evasa / Totale').': <b>'.numberFormat($source->qta_evasa, 'qta').' / '.numberFormat($source->qta, 'qta').'</b>
        </div>
    </div>

    <div class="card-body">';
if ($documenti_destinazione) {
    echo '
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="90%">'.tr('Documento').'</th>
                    <th class="text-center">'.tr('Q.tà').'</th>
                </tr>
            </thead>

            <tbody>';

    $documenti_destinazione = getDestinationComponents($source);
    foreach ($documenti_destinazione['documento'] as $key => $destinazione) {
        echo '
                <tr>
                    <td>
                        '.reference($destinazione).'
                    </td>
                    <td class="text-right">
                        '.numberFormat($documenti_destinazione['qta'][$key], 'qta').'
                    </td>
                </tr>';
    }
    echo '
            </tbody>
        </table>';
} else {
    echo '
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> '.tr('Nessun documento collegato').'.
        </div>';
}
echo '
    </div>
</div>';
