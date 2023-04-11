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

use Modules\Interventi\Intervento;

if ( $id_module == Modules::get('Fatture di acquisto')['id'] ){
    $conti = 'conti-acquisti';
} else{
    $conti = 'conti-vendite';
}

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="change-conto">

    <div class="row">
        <div class="col-md-12 pull-right">
            <button type="button" class="btn btn-info btn-sm pull-right" onclick="copy()"><i class="fa fa-copy"></i> '.tr('Copia conto dalla prima riga valorizzata').'</button>
        </div>
    </div>
    <br>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-condensed table-bordered">
            <thead>
                <tr>
                    <th width="35" class="text-center" >'.tr('#').'</th>
                    <th>'.tr('Descrizione').'</th>
                    <th class="text-center" width="100">'.tr('Q.tà').'</th>
                    <th class="text-center" width="140">'.tr('Prezzo unitario').'</th>
                    <th width="450">'.tr('Conto').'</th>
                </tr>
            </thead>
            <tbody class="sortable">';

    // Righe documento
    if (!empty($fattura)) {
        $optionsConti = AJAX::select($conti, [], null, 0, 10000);
        $righe = $fattura->getRighe();
        $num = 0;
        foreach ($righe as $riga) {
            ++$num;

            if (!$riga->isDescrizione()) {

                echo '
                <tr>
                    <td class="text-center">
                        '.$num.'
                    </td>

                    <td>';

                if ($riga->isArticolo()) {
                    echo Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$riga->descrizione);
                } else {
                    echo nl2br($riga->descrizione);
                }

                echo '
                    </td>';

                // Quantità e unità di misura
                echo '
                    <td class="text-center">
                        '.numberFormat($riga->qta, 'qta').' '.$riga->um.'
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
                    </td>

                    <td>
                        {[ "type": "select", "name": "idconto['.$riga['id'].']", "required": 1, "value": "'.$riga->id_conto.'", "values": ' . json_encode($optionsConti['results']) . ', "class": "unblockable" ]}
                    </td>
                </tr>';
            }
        }
    }

    echo '
            </tbody>
        </table>
    </div>
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>

<script>
function copy() {
    let conti = $("select[name^=idconto]");

    // Individuazione del primo conto selezionato
    let conto_selezionato = null;
    for (const conto of conti) {
        const data = $(conto).selectData();
        if (data && data.id) {
            conto_selezionato = data;
            break;
        }
    }

    // Selezione generale per il conto
    if (conto_selezionato) {
        conti.each(function() {
            $(this).selectSetNew(conto_selezionato.id, conto_selezionato.text, conto_selezionato);
        });
    }
}
</script>';
