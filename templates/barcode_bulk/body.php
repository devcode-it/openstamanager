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

use Modules\Articoli\Articolo;

include_once __DIR__.'/../../core.php';

echo '<style>
.barcode {
    padding: 0;
    margin: 0;
    vertical-align: top;
}
.barcode-cell {
    text-align: center;
    vertical-align: middle;
}
</style>';

if (!empty($_SESSION['superselect']['id_articolo_barcode'])) {
    $barcodes = $dbo->table('mg_articoli_barcode')->whereIn('idarticolo',$_SESSION['superselect']['id_articolo_barcode'])->get();
    unset($_SESSION['superselect']['id_articolo_barcode']);
} elseif( !empty(get('idbarcode')) ){
    $barcodes = $dbo->table('mg_articoli_barcode')->where('id',get('idbarcode'))->get();
} else {
    $barcodes = $dbo->table('mg_articoli_barcode')->where('idarticolo',$id_record)->get();
}

// Inizializza $records come array vuoto
$records = [];

// Verifica che $barcodes non sia null o vuoto
if (!empty($barcodes) && is_array($barcodes)) {
    if( !empty(get('qta')) ){
        foreach($barcodes as $barcode) {
            for( $i=0; $i<get('qta'); $i++ ){
                $records[] = $barcode;
            }
        }
    }else{
        $records = $barcodes;
    }
}

echo "
<table style='width:100%'>
    <tr>";

$i = 0;
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Verifica che ci siano records da elaborare
if (!empty($records)) {
    foreach ($records as $record) {
    $articolo = Articolo::find($record->idarticolo);
    $barcode = $record->barcode;

    if ($i % 5 == 0) {
        echo '</tr><tr>';
    }
    echo '
    <td class="barcode-cell">
        <p style="font-size:11pt;"><b>'.$articolo->codice.'</b></p>
        <p style="font-size:10pt;">'.$articolo->getTranslation('title').'</p><br>
        <p style="font-size:15pt;"><b>'.moneyFormat($prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita).'</b></p><br>
        <barcode code="'.$barcode.'" type="C39" height="2" size="0.65" class="barcode" />
        <p><b>'.$barcode.'</b></p>
    </td><br><br>';

    ++$i;
    }
}

echo '
    </tr>
</table>';
