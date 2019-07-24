<?php

include_once __DIR__.'/../../../core.php';

use Modules\Fatture\Fattura;

$space = str_repeat('&nbsp;', 6);

$documento = Fattura::find($id_record);

$result = $documento->toArray();
$result = array_merge($result, $documento->dati_aggiuntivi_fe);

echo '
    <link rel="stylesheet" type="text/css" media="all" href="'.$structure->fileurl('fe/style.css').'"/>';

echo '
<form action="" method="post">
	<input type="hidden" name="op" value="manage_documento_fe">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">';

echo '
<table class="table">
    <tbody>
        <tr class="first-level">
            <th colspan="2">
                2 FatturaElettronicaBody
                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-edit"></i> '.tr('Salva').'
                </button>
			</th>
        </tr>
        <tr class="second-level">
            <th colspan="2">'.str_repeat($space, 1).'2.1 DatiGenerali</th>
        </tr>
        <tr class="third-level">
            <th colspan="2">'.str_repeat($space, 2).'2.1.1 DatiGeneraliDocumento</th>
        </tr>';

// Art73
echo '
        <tr class="fourth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 3).'2.1.1.12 Art73</td>
            <td>
                {[ "type": "checkbox", "name": "art73", "value": "'.$result['art73'].'", "placeholder": "'.tr("Emesso ai sensi dell'articolo 73 del DPR 633/72").'" ]}
            </td>
        </tr>
    </tbody>';

echo '
<script>
var keys = {};
var ref_keys = {};
</script>';

$documenti = [
    'dati_ordine' => [
        'code' => '2.1.2',
        'name' => 'DatiOrdineAcquisto',
    ],
    'dati_contratto' => [
        'code' => '2.1.3',
        'name' => 'DatiContratto',
    ],
    'dati_convenzione' => [
        'code' => '2.1.4',
        'name' => 'DatiConvenzione',
    ],
    'dati_ricezione' => [
        'code' => '2.1.5',
        'name' => 'DatiRicezione',
    ],
    'dati_fatture' => [
        'code' => '2.1.6',
        'name' => 'DatiFattureCollegate',
    ],
];
foreach ($documenti as $nome => $info) {
    if (empty($result[$nome])) {
        $result[$nome][] = [];
    }

    $key = 1;
    foreach ($result[$nome] as $dato) {
        include __DIR__.'/components/dati_documento.php';

        echo '
    <script>
        ref_keys["'.$nome.$key.'"] = '.($index - 1).';
    </script>';

        ++$key;
    }

    echo '
    <script>
        keys["'.$nome.'"] = '.($key - 1).';
    </script>';
}

echo '
</table>';

foreach ($documenti as $nome => $info) {
    echo '
<table class="hide" id="'.$nome.'-templace">';
    $dato = [];
    $key = '-id-';

    include __DIR__.'/components/dati_documento.php';

    echo '
</table>

<table class="hide">
    <tbody id="riferimento_'.$nome.'-templace">
        <tr class="fifth-level" title="RiferimentoNumeroLinea-'.$nome.'--id-">
            <td style="vertical-align: middle;">
                '.str_repeat($space, 4).$info['code'].'.1 RiferimentoNumeroLinea - '.tr('Riga _NUM_', [
            '_NUM_' => '-num-',
        ]).'
            </td>
            <td>
                {[ "type": "number", "name": "'.$nome.'[-id-][riferimento_linea][]", "value": "", "maxlength": 4, "decimals": 0 ]}
            </td>
        </tr>
    </tbody>
</table>';
}

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
<script>
function replaceAll(str, find, replace) {
  return str.replace(new RegExp(find, "g"), replace);
}

function add_blocco(btn, nome){
    $("#template .superselect, #template .superselectajax").select2().select2("destroy");
    var last = $(btn).closest("table").find("tr[id^=last-" + nome + "]").parent().last();

    keys[nome]++;
    var text = replaceAll($("#" + nome + "-templace").html(), "-id-", "" + keys[nome]);
    
    ref_keys[nome + keys[nome]] = 1;
    
    last.after(text);
    
    start_superselect();
    start_datepickers();
}

function add_riferimento(btn, nome, key) {
    $("#template .superselect, #template .superselectajax").select2().select2("destroy");
    var last = $(btn).closest("table").find("tr[title=RiferimentoNumeroLinea-" + nome + "-" + key + "]").last();

    ref_keys[nome + key]++;
    var text = replaceAll($("#riferimento_" + nome + "-templace").html(), "-id-", "" + key);
    text = replaceAll(text, "-num-", "" + ref_keys[nome + key]);
    
    last.after(text);
    
    start_superselect();
    start_datepickers();
}
</script>

<script src="'.ROOTDIR.'/lib/init.js"></script>';
