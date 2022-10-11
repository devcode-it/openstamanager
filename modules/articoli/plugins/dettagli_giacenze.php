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

use Modules\Articoli\Articolo;

$articolo = Articolo::find($id_record);

$idsede = (empty(get('idsede')) ? 0 : get('idsede'));
$movimenti = $articolo->movimentiComposti()
    ->where('idsede',$idsede)
    ->orderBy('mg_movimenti.data', 'DESC')
    ->orderBy('mg_movimenti.id', 'DESC');

// Raggruppamento per documento
$movimenti = $movimenti->get();
if (!empty($movimenti)) {
    $totale = 0;
    echo '
    <div style="max-height:400px; overflow:auto;">
        <table class="table table-striped table-condensed table-bordered">
            <tr>
                <th class="text-center">'.tr('Q.tà').'</th>
                <th class="text-center">'.tr('Q.tà progressiva').'</th>
                <th>'.tr('Operazione').'</th>
                <th class="text-center">'.tr('Data').'</th>
                <th class="text-center" width="7%">#</th>
            </tr>';

    foreach ($movimenti as $i => $movimento) {
        // Quantità progressiva
        if ($i == 0) {
            $movimento['progressivo_finale'] = $articolo->qta;
        } else {
            $movimento['progressivo_finale'] = $movimenti[$i - 1]['progressivo_iniziale'];
        }
        $movimento['progressivo_iniziale'] = $movimento['progressivo_finale'] - $movimento->qta;
        $movimento['progressivo_iniziale'] = $movimento['progressivo_finale'] - $movimento->qta;

        $movimenti[$i]['progressivo_iniziale'] = $movimento['progressivo_iniziale'];
        $movimenti[$i]['progressivo_finale'] = $movimento['progressivo_finale'];

        $totale += $movimento->qta;

        // Quantità
        echo '
            <tr>
                <td class="text-center">
                    '.numberFormat($movimento->qta, 'qta').' '.$record['um'].'
                </td>

                <td class="text-center">
                    '.numberFormat($movimento['progressivo_iniziale'], 'qta').' '.$record['um'].'
                    <i class="fa fa-arrow-circle-right"></i>
                    '.numberFormat($movimento['progressivo_finale'], 'qta').' '.$record['um'].'
                </td>

                <td>
                    '.$movimento->descrizione.''.($movimento->hasDocument() ? ' - '.reference($movimento->getDocument()) : '').'
                </td>';

        // Data
        echo '
                <td class="text-center">'.dateFormat($movimento->data).' <span  class="tip" title="'.tr('Data di creazione del movimento: _DATE_', [
               '_DATE_' => timestampFormat($movimento->created_at),
            ]).'"><i class="fa fa-question-circle-o"></i></span> </td>';

        // Operazioni
        echo '
                <td class="text-center">';

        if (Auth::admin() && $movimento->isManuale()) {
            echo '
                    <a class="btn btn-danger btn-xs ask" data-backto="record-edit" data-op="delmovimento" data-idmovimento="'.$movimento['id'].'">
                        <i class="fa fa-trash"></i>
                    </a>';
        }

        echo '
                </td>
            </tr>';
    }

    echo '
        </table>
    </div>
    <table class="table table-bordered">
        <tr>
            <th class="text-right">'.tr('Totale').'</th>
            <th class="text-right" width="17.9%">'.Translator::numberToLocale($totale,'qta').' '.$articolo->um.'</th>
        </tr>
    </table>';
} else {
    echo '
	<div class="alert alert-info">
		<i class="fa fa-info-circle"></i>
		'.tr('Questo articolo non è ancora stato movimentato').'.
	</div>';
}