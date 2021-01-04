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
include_once __DIR__.'/../init.php';

use Modules\Fatture\Fattura;

$modulo = Modules::get($id_module)['name'];
if ($modulo == 'Anagrafiche') {
    $movimenti = $dbo->fetchArray('SELECT co_movimenti.*, SUM(totale) AS totale, co_pianodeiconti3.descrizione, co_pianodeiconti3.numero AS conto3, co_pianodeiconti2.numero AS conto2 FROM co_movimenti LEFT JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id LEFT JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE id_anagrafica='.prepare($id_record).' GROUP BY idmastrino, idconto ORDER BY data, idmastrino');
} else {
    $movimenti = $dbo->fetchArray('SELECT co_movimenti.*, SUM(totale) AS totale, co_pianodeiconti3.descrizione, co_pianodeiconti3.numero AS conto3, co_pianodeiconti2.numero AS conto2 FROM co_movimenti LEFT JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id LEFT JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE iddocumento='.prepare($id_record).' GROUP BY idmastrino, idconto ORDER BY data, idmastrino');
}

$idmastrini_processati = [-1];

if (!empty($movimenti)) {
    echo '
    <table class="table table-hover table-condensed table-bordered table-striped" style="font-size:11pt;">
        <thead>
            <tr>
                <th width="160">'.tr('Data').'</th>
                <th>'.tr('Conto').'</th>
                <th width="170">'.tr('Dare').'</th>
                <th width="170">'.tr('Avere').'</th>
                <th width="170">'.tr('Scalare').'</th>
            </tr>
        </thead>

        <tbody>';

        foreach ($movimenti as $movimento) {
            $documento = $modulo == 'Anagrafiche' ? Fattura::find($movimento['iddocumento']) : null;
            $scalare += $movimento['totale'];
            $descrizione = $movimento['conto2'].'.'.$movimento['conto3'].' - '.$movimento['descrizione'];

            if( $movimento['primanota']==1 ){
                $descrizione = Modules::link('Prima nota',$movimento['idmastrino'],$descrizione);
            }

            echo '
                <tr>
                    <td class="text-center">'.dateFormat($movimento['data']).'</td>
                    <td>'.$descrizione.'<small class="pull-right text-right text-muted" style="font-size:8pt;">'.($documento ? $documento->getReference() : '').'</small></td>
                    <td class="text-right">'.($movimento['totale']>0 ? moneyFormat(abs($movimento['totale'])) : "").'</td>
                    <td class="text-right">'.($movimento['totale']<0 ? moneyFormat(abs($movimento['totale'])) : "").'</td>
                    <td class="text-right">'.moneyFormat($scalare).'</td>
                </tr>';

            $idmastrini_processati[] = $movimento['idmastrino'];
        }

        // Altri movimenti del mastrino collegati ma non direttamente collegati alla fattura (es. spese bancarie)
        if ($modulo != 'Anagrafiche') {
            $altri_movimenti = $dbo->fetchArray('SELECT co_movimenti.*, SUM(totale) AS totale, co_pianodeiconti3.descrizione, co_pianodeiconti3.numero AS conto3, co_pianodeiconti2.numero AS conto2 FROM co_movimenti LEFT JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id LEFT JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE iddocumento=0 AND idmastrino IN('.implode(',', $idmastrini_processati).') GROUP BY idmastrino, idconto ORDER BY data, idmastrino');

            foreach ($altri_movimenti as $altro_movimento) {
                $documento = $modulo == 'Anagrafiche' ? Fattura::find($altro_movimento['iddocumento']) : null;
                $scalare += $altro_movimento['totale'];
                $descrizione = $altro_movimento['conto2'].'.'.$altro_movimento['conto3'].' - '.$altro_movimento['descrizione'];

                if( $altro_movimento['primanota']==1 ){
                    $descrizione = Modules::link('Prima nota',$altro_movimento['idmastrino'],$descrizione);
                }

                echo '
                    <tr>
                        <td class="text-center">'.dateFormat($altro_movimento['data']).'</td>
                        <td>'.$descrizione.'<small class="pull-right text-right text-muted" style="font-size:8pt;">'.($documento ? $documento->getReference() : '').'</small></td>
                        <td class="text-right">'.($altro_movimento['totale']>0 ? moneyFormat(abs($altro_movimento['totale'])) : "").'</td>
                        <td class="text-right">'.($altro_movimento['totale']<0 ? moneyFormat(abs($altro_movimento['totale'])) : "").'</td>
                        <td class="text-right">'.moneyFormat($scalare).'</td>
                    </tr>';

            }
        }
    echo '
        </tbody>
    </table>';
} else {
    echo '
    <h3 class="text-center">
        <small class="help-block">'.tr('Non sono presenti movimenti contabili').'</small>
    </h3>';
}
