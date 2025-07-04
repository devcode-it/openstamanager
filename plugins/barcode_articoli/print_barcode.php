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

$id_record = get('id_record');
$id_parent = get('id_parent');

$barcode = $dbo->table('mg_articoli_barcode')->where('id',$id_record)->first();

echo '
<div class="row">
    <div class="col-md-3">
        {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "1", "decimals":"0", "help":"'.tr('Definisci quante etichette stampare per questo barcode').'" ]}
    </div>
    <div class="col-md-3">
        {[ "type": "select", "label": "'.tr('Tipologia stampa').'", "name": "tipologia", "required": 1, "values": "list=\"singola\":\"Singola\",\"a4\":\"Formato A4\"", "value": "singola" ]}
    </div>
</div>
<div class="row">
    <div class="col-md-12 text-right">
        <a class="btn btn-primary" onclick="stampaBarcode();"><i class="fa fa-print"></i> '.tr('Stampa').'</a>
    </div>
</div>';

echo '
<script>

function stampaBarcode() {
    let qta = input("qta").get();

    if ($("#tipologia").val() == "singola") {
        var id_print = '.Prints::getPrints()['Barcode'].';
    } else {
        var id_print = '.Prints::getPrints()['Barcode bulk'].';
    }

    window.open("'.$rootdir.'/pdfgen.php?id_print=" + id_print + "&id_record='.$id_parent.'&idbarcode='.$id_record.'&qta=" + qta, "_blank");
}

</script>';