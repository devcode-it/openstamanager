<?php

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
$id_riga = get('idriga');
$riga = $documento->getRighe()->find($id_riga);

$result = $riga->toArray();
$result = array_merge($result, $riga->dati_aggiuntivi_fe);

echo '
<form action="" method="post">
	<input type="hidden" name="op" value="manage_riga_fe">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">';

echo '
<table class="table">
    <tbody>
        <tr>
            <th colspan="2">
                2 FatturaElettronicaBody
                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-edit"></i> '.tr('Salva').'
                </button>
			</th>
        </tr>
        <tr>
            <th colspan="2">'.str_repeat($space, 1).'2.2 DatiBeniServizi</th>
        </tr>
        <tr>
            <th colspan="2">'.str_repeat($space, 2).'2.2.1 DettaglioLinee</th>
        </tr>';

// Tipo Cessione Prestazione
 echo '
        <tr>
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.2 TipoCessionePrestazione</td>
            <td>
                {[ "type": "select", "name": "tipo_cessione_prestazione", "value": "'.$result['tipo_cessione_prestazione'].'", "values": '.json_encode($tipi_cessione_prestazione).' ]}
            </td>
        </tr>';

// Data inizio periodo
echo '
        <tr>
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.7 DataInizioPeriodo</td>
            <td>
                {[ "type": "date", "name": "data_inizio_periodo", "value": "'.$result['data_inizio_periodo'].'" ]}
            </td>
        </tr>';

// Data fine periodo
echo '
        <tr>
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.8 DataFinePeriodo</td>
            <td>
                {[ "type": "date", "name": "data_fine_periodo", "value": "'.$result['data_fine_periodo'].'" ]}
            </td>
        </tr>';

// Riferimento amministrazione
echo '
        <tr>
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.2.1.15 RiferimentoAmministrazione</td>
            <td>
                {[ "type": "text", "name": "riferimento_amministrazione", "value": "'.$result['riferimento_amministrazione'].'", "maxlength": 20 ]}
            </td>
        </tr>';

if (empty($result['altri_dati'])) {
    $result['altri_dati'][] = [];
}

$key = 1;
foreach ($result['altri_dati'] as $dato) {
    include __DIR__.'/components/altri_dati.php';

    ++$key;
}

 echo '
    </tbody>
</table>';

echo '
<script>
function replaceAll(str, find, replace) {
  return str.replace(new RegExp(find, "g"), replace);
}

var n = '.($key - 1).';
function add_altri_dati(btn){
    $("#template .superselect, #template .superselectajax").select2().select2("destroy");
    var last = $(btn).closest("table").find("tr[id^=last-altri_dati]").last();

    n++;
    var text = replaceAll($("#altri_dati-templace").html(), "-id-", "" + n);
    
    last.after(text);
    console.log(text);
    
    start_superselect();
};
</script>

<table class="hide">
    <tbody id="altri_dati-templace">';
$dato = [];
$key = '-id-';

include __DIR__.'/components/altri_dati.php';

echo '
    </tbody>
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
<script src="'.ROOTDIR.'/lib/init.js"></script>';
