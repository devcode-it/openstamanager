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

use Modules\Contratti\Contratto;

//Estraggo tutti i contratti del cliente
$contratti = $dbo->table("co_contratti")->where('idanagrafica',$id_record)->get();

if( !$contratti->isEmpty() ){
    echo '
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-condensed">
                <tr>
                    <th>'.tr('Documento').'</th>
                    <th width="11%">'.tr('Totale').'</th>
                    <th width="3%"></th>
                </tr>';

                foreach($contratti as $c){
                    $contratto = Contratto::find($c->id);
                    $righe = $contratto->getRighe();

                    // Calcoli
                    $imponibile = abs($contratto->imponibile);
                    $sconto = $contratto->sconto;
                    $totale_imponibile = abs($contratto->totale_imponibile);
                    $iva = abs($contratto->iva);
                    $totale = abs($contratto->totale);

                    $descrizione = tr('Contratto num. _NUM_ del _DATA_',[
                        '_NUM_' => $contratto->numero,
                        '_DATA_' => dateFormat($contratto->data_bozza),
                    ]);
                    echo '
                    <tr>
                        <td>'.Modules::link('Contratti', $contratto->id, $descrizione).'</td>
                        <td class="text-right">'.moneyFormat($contratto->totale, 2).'</td>
                        <td class="text-center">
                            <a class="btn btn-xs btn-primary" onclick="if( $(\'#righe_'.$contratto->id.'\').is(\':visible\') ){ $(\'#righe_'.$contratto->id.'\').hide(); }else{ $(\'#righe_'.$contratto->id.'\').show(); }"><i class="fa fa-plus"></i></a>
                        </td>
                    </tr>';

                    if( $righe->isempty() ){
                        echo '
                        <tr id="righe_'.$contratto->id.'" >
                            <td colspan="3" class="text-muted">'.tr('Nessuna riga presente!').'</td>
                        </tr>';
                    }else{
                        echo '
                        <tr id="righe_'.$contratto->id.'" style="display:none;">
                            <td colspan="3">
                                <table class="table table-bordered table-stripped">
                                    <tr>
                                        <th width="2%">#</th>
                                        <th>'.tr('Descrizione').'</th>
                                        <th class="text-center" width="8%">'.tr('Q.tà').'</th>
                                        <th width="11%">'.tr('Prezzo unitario').'</th>
                                        <th width="11%">'.tr('Iva unitaria').'</th>
                                        <th width="11%">'.tr('Importo').'</th>
                                    </tr>';

                                    $num = 0;
                                    foreach($righe as $riga){
                                        ++$num;

                                        echo '
                                        <tr>
                                            <td class="text-center">'.$num.'</td>';

                                            // Descrizione
                                            $descrizione = nl2br($riga->descrizione);
                                            if ($riga->isArticolo()) {
                                                $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$descrizione);
                                            }

                                        echo '
                                            <td>'.$descrizione.'</td>';
                                        
                                            // Quantità e unità di misura
                                        echo '
                                            <td class="text-center">
                                                '.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').' '.$riga->um.'
                                            </td>';

                                        // Prezzi unitari
                                        echo '
                                            <td class="text-right">
                                                '.moneyFormat($riga->prezzo_unitario_corrente);

                                        if ($dir == 'entrata' && $riga->costo_unitario != 0) {
                                            echo '
                                                <br><small class="text-muted">
                                                    '.tr('Acquisto').': '.moneyFormat($riga->costo_unitario).'
                                                </small>';
                                        }

                                        if (abs($riga->sconto_unitario) > 0) {
                                            $text = discountInfo($riga);

                                            echo '
                                                <br><small class="label label-danger">'.$text.'</small>';
                                        }

                                        echo '
                                            </td>';

                                        // Iva
                                        echo '
                                            <td class="text-right">
                                                '.moneyFormat($riga->iva_unitaria_scontata).'
                                                <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
                                            </td>';

                                        // Importo
                                        echo '
                                            <td class="text-right">
                                                '.moneyFormat($riga->importo).'
                                            </td>
                                        </tr>';
                                    }

                                    // Totale totale imponibile
                                    echo '
                                        <tr>
                                            <td colspan="5" class="text-right">
                                                <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
                                            </td>
                                            <td class="text-right">
                                                '.moneyFormat($contratto->imponibile, 2).'
                                            </td>
                                        </tr>';

                                    // SCONTO
                                    if (!empty($sconto)) {
                                        echo '
                                            <tr>
                                                <td colspan="5" class="text-right">
                                                    <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
                                                </td>
                                                <td class="text-right">
                                                    '.moneyFormat($contratto->sconto, 2).'
                                                </td>
                                            </tr>';

                                        // Totale totale imponibile
                                        echo '
                                            <tr>
                                                <td colspan="5" class="text-right">
                                                    <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
                                                </td>
                                                <td class="text-right">
                                                    '.moneyFormat($totale_imponibile, 2).'
                                                </td>
                                            </tr>';
                                    }

                                    // Totale iva
                                    echo '
                                        <tr>
                                            <td colspan="5" class="text-right">
                                                <b>'.tr('Iva', [], ['upper' => true]).':</b>
                                            </td>
                                            <td class="text-right">
                                                '.moneyFormat($contratto->iva, 2).'
                                            </td>
                                        </tr>';

                                    // Totale contratto
                                    echo '
                                        <tr>
                                            <td colspan="5" class="text-right">
                                                <b>'.tr('Totale', [], ['upper' => true]).':</b>
                                            </td>
                                            <td class="text-right">
                                                '.moneyFormat($contratto->totale, 2).'
                                            </td>
                                        </tr>';

                        echo '
                                </table>
                            </td>
                        </tr>';
                    }
                }

    echo '
            </table>
        </div>
    </div>';
}else{
    echo '
    <div class="row">
        <div class="col-md-12">
            <span style="text-muted">'.tr('Non è stato trovato nessun contratto associato a questo cliente!').'</span>
        </div>
    </div>';
}