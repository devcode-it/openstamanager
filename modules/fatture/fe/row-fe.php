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

include_once __DIR__.'/../../../core.php';

use Modules\Fatture\Fattura;

$tipi_cessione_prestazione = [
    [
        'id' => 'SC',
        'text' => 'SC - '.tr('Sconto'),
    ],
    [
        'id' => 'PR',
        'text' => 'PR - '.tr('Premio'),
    ],
    [
        'id' => 'AB',
        'text' => 'AB - '.tr('Abbuono'),
    ],
    [
        'id' => 'AC',
        'text' => 'AC - '.tr('Spesa accessoria'),
    ],
];

$space = str_repeat('&nbsp;', 6);

$documento = Fattura::find($id_record);

// Dati della riga
$id_riga = get('riga_id');
$type = get('riga_type');
$riga = $documento->getRiga($type, $id_riga);

$result = $riga->toArray();
$result = array_merge($result, $riga->dati_aggiuntivi_fe);

echo '
    <link rel="stylesheet" type="text/css" media="all" href="'.$structure->fileurl('fe/style.css').'"/>';

echo '
<form action="" method="post">
	<input type="hidden" name="op" value="manage_riga_fe">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="id_riga" value="'.$id_riga.'">';

echo '
<table class="table">
    <tbody>
        <tr class="first-level">
            <th colspan="2">
                2 FatturaElettronicaBody
                <!--button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-edit"></i> '.tr('Salva').'
                </button-->
			</th>
        </tr>
        <tr class="second-level">
            <th colspan="2">'.str_repeat($space, 1).'2.2 DatiBeniServizi</th>
        </tr>
        <tr class="third-level">
            <th colspan="2">'.str_repeat($space, 2).'2.2.1 DettaglioLinee</th>
        </tr>';

// Tipo Cessione Prestazione
 echo '
        <tr class="fourth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.2 TipoCessionePrestazione</td>
            <td>
                {[ "type": "select", "name": "tipo_cessione_prestazione", "value": "'.$result['tipo_cessione_prestazione'].'", "values": '.json_encode($tipi_cessione_prestazione).' ]}
            </td>
        </tr>';

// Data inizio periodo
echo '
        <tr class="fourth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.7 DataInizioPeriodo</td>
            <td>
                {[ "type": "date", "name": "data_inizio_periodo", "value": "'.$result['data_inizio_periodo'].'" ]}
            </td>
        </tr>';

// Data fine periodo
echo '
        <tr class="fourth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.8 DataFinePeriodo</td>
            <td>
                {[ "type": "date", "name": "data_fine_periodo", "value": "'.$result['data_fine_periodo'].'" ]}
            </td>
        </tr>';

// Riferimento amministrazione
echo '
        <tr class="fourth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.15 RiferimentoAmministrazione</td>
            <td>
                {[ "type": "text", "name": "riferimento_amministrazione", "value": "'.$result['riferimento_amministrazione'].'", "maxlength": 20 ]}
            </td>
        </tr>
    </tbody>';

if (empty($result['altri_dati'])) {
    $result['altri_dati'][] = [];
}

$key = 1;
foreach ($result['altri_dati'] as $dato) {
    include __DIR__.'/components/altri_dati.php';

    ++$key;
}

 echo '

</table>';

echo '
<script>
var n = '.($key - 1).';
function add_altri_dati(btn){
    cleanup_inputs();

    var last = $(btn).closest("table").find("tr[id^=last-altri_dati]").parent().last();

    n++;
    var text = replaceAll($("#altri_dati-template").html(), "-id-", "" + n);

    last.after(text);
    restart_inputs();
};
</script>

<table class="hide" id="altri_dati-template">';
$dato = [];
$key = '-id-';

include __DIR__.'/components/altri_dati.php';

echo '
</table>';

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
			    <i class="fa fa-edit"></i> '.tr('Salva').'
			</button>
		</div>
	</div>';

echo '
</form>';

echo '
<script>$(document).ready(init)</script>';
