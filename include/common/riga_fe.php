<?php

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

echo '
<table class="table">
    <tbody>
        <tr>
            <th colspan="2">2 FatturaElettronicaBody</th>
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
    include __DIR__.'/fe_components/altri_dati.php';

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

include __DIR__.'/fe_components/altri_dati.php';

echo '
    </tbody>
</table>';
