<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

$documento = Fattura::find($record['iddocumento']);
$dir = $documento->direzione;
$numero = $documento->numero_esterno ?: $documento->numero;

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="'.$id_record.'">

	<input type="hidden" name="tipo" value="'.$record['tipo'].'">
	<input type="hidden" name="descrizione" value="'.$record['descrizione'].'">
	<input type="hidden" name="iddocumento" value="'.$record['iddocumento'].'">

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">
			    '.tr('Dettagli scadenza').'
                <button type="button" class="btn btn-xs btn-info pull-right '.(empty($documento) ? 'disabled' : '').'" id="add-scadenza" '.(empty($documento) ? 'disabled' : '').'>
                    <i class="fa fa-plus"></i> '.tr('Aggiungi scadenza').'
                </button>
            </h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<!-- Info scadenza -->
				<div class="col-md-6">';

if (!empty($documento)) {
    echo '
					<table class="table table-striped table-hover table-condensed table-bordered">
                        <tr>
                            <th width="120">'.($dir == 'entrata' ? tr('Cliente') : tr('Fornitore')).':</th>
                            <td>
                                '.Modules::link('Anagrafiche', $documento->anagrafica->id, $documento->anagrafica->ragione_sociale).'
                            </td>
                        </tr>

                        <tr>
                            <th>'.tr('Documento').':</th>
                            <td>'.$documento->tipo->descrizione.'</td>
                        </tr>

                        <tr>
                            <th>'.tr('Numero').':</th>
                            <td>'.$numero.'</td>
                        </tr>

                        <tr>
                            <th>'.tr('Data').':</th>
                            <td>'.Translator::dateToLocale($documento->data).'</td>
                        </tr>

                        <tr>
                            <th>'.tr('Netto a pagare').':</th>
                            <td>'.moneyFormat($documento->netto).'</td>
                        </tr>

                        <tr>
                            <th>'.tr('Note').':</th>
                            <td>
                                {[ "type": "textarea", "name": "note", "value": "'.$record['note'].'" ]}
                            </td>
                        </tr>

                    </table>

                    '.Modules::link($documento->module, $record['iddocumento'], '<i class="fa fa-folder-open"></i> '.tr('Apri documento'), null, 'class="btn btn-primary"');
} else {
    $scadenza = $dbo->fetchOne('SELECT * FROM co_scadenziario WHERE id='.prepare($id_record));

    echo input([
        'type' => 'textarea',
        'label' => tr('Descrizione'),
        'name' => 'descrizione',
        'required' => 1,
        'value' => $scadenza['descrizione'],
    ]);
}

echo '
				</div>

				<!-- Elenco scadenze -->
				<div class="col-md-6">
					<table class="table table-hover table-condensed table-bordered">
                        <thead>
                            <tr>
                                <th width="150">'.tr('Data').'</th>
                                <th width="150">'.tr('Importo').'</th>
                                <th width="150">'.tr('Pagato').'</th>
                                <th width="150">'.tr('Data concordata').'</th>
                            </tr>
                        </thead>

                        <tbody id="scadenze">';

// Scelgo la query in base alla scadenza
if (!empty($documento)) {
    $rs = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = '.prepare($documento->id).' ORDER BY scadenza ASC');
    $totale_da_pagare = $documento->netto;
} else {
    $rs = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE id = '.prepare($id_record).' ORDER BY scadenza ASC');
    $totale_da_pagare = sum(array_column($rs, 'da_pagare'));
}

foreach ($rs as $i => $scadenza) {
    if ($scadenza['da_pagare'] == $scadenza['pagato']) {
        $class = 'success';
    } elseif (abs($scadenza['pagato']) == 0) {
        $class = 'danger';
    } elseif (abs($scadenza['pagato']) <= abs($scadenza['da_pagare'])) {
        $class = 'warning';
    } else {
        $class = 'danger';
    }

    echo '
                        <tr class="'.$class.'">
                            <input type="hidden" name="id_scadenza['.$i.']" value="'.$scadenza['id'].'">

                            <td align="center">
                                {[ "type": "date", "name": "scadenza['.$i.']", "value": "'.$scadenza['scadenza'].'" ]}
                            </td>

                            <td align="right">
                                {[ "type": "number", "name": "da_pagare['.$i.']", "decimals": 2, "value": "'.Translator::numberToLocale($scadenza['da_pagare'], 2).'", "onchange": "controlloTotale()" ]}
                            </td>

                            <td align="right">
                                {[ "type": "number", "name": "pagato['.$i.']", "decimals": 2, "value": "'.Translator::numberToLocale($scadenza['pagato']).'"  ]}
                            </td>

                            <td align="center">
                                {[ "type": "date", "name": "data_concordata['.$i.']", "value": "'.$scadenza['data_concordata'].'" ]}
                            </td>
                        </tr>';
}

echo '
                        </tbody>
                        <tfoot>
                            <tr>
                                <td align="right"><b>'.tr('Totale').'</b></td>
                                <td align="right" id="totale_utente">'.Translator::numberToLocale($totale_da_pagare).'</td>
                                <td align="right"></td>
                            </tr>
                        </tfoot>';

?>

					</table>

                    <div class='pull-right'>
                        <a onclick="launch_modal( 'Registra contabile pagamento', '<?php echo $rootdir; ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id']; ?>&<?php echo !empty($record['iddocumento']) ? 'id_documenti='.$record['iddocumento'].'&single=1' : 'id_scadenze='.$id_record; ?>');" class="btn btn-sm btn-primary"><i class="fa fa-euro"></i> <?php echo tr('Registra contabile pagamento...'); ?></a>
                    </div>

					<div class="clearfix"></div>
                    <br>

					<div class="alert alert-error hide" id="totale"><?php echo tr('Il totale da pagare deve essere pari a _MONEY_', [
                        '_MONEY_' => '<b>'.moneyFormat($totale_da_pagare).'</b>',
                    ]); ?>.<br><?php echo tr('Differenza di _TOT_ _CURRENCY_', [
                            '_TOT_' => '<span id="diff"></span>',
                            '_CURRENCY_' => currency(),
                        ]); ?>.
					</div>

					<input type="hidden" id="totale_da_pagare" value="<?php echo Translator::numberToLocale($totale_da_pagare, 2); ?>">
				</div>
			</div>
		</div>
	</div>
</form>

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
if (empty($documento)) {
                            echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
                        }

echo '
<table class="hide">
    <tbody id="scadenza-template">
        <tr class="danger">
            <input type="hidden" name="id_scadenza[-id-]" value="">

            <td align="center">
                {[ "type": "date", "name": "scadenza[-id-]" ]}
            </td>

            <td align="right">
                {[ "type": "number", "name": "da_pagare[-id-]", "decimals": 2, "onchange": "controlloTotale()" ]}
            </td>

            <td align="right">
                {[ "type": "number", "name": "pagato[-id-]", "decimals": 2 ]}
            </td>

            <td align="center">
                {[ "type": "date", "name": "data_concordata[-id-]" ]}
            </td>
        </tr>
    </tbody>
</table>

<script>
    var i = '.$i.';
	$(document).on("click", "#add-scadenza", function(){
        cleanup_inputs();

        i++;
		var text = replaceAll($("#scadenza-template").html(), "-id-", "" + i);

		$("#scadenze").append(text);
		restart_inputs();
	});
</script>';

// Abilitazione dei controlli solo per Scadenze collegate a documenti
if (!empty($documento)) {
    echo '
<script>
    globals.cifre_decimali = 2;

	$(document).ready(function(){
        controlloTotale();';

    if ($dir == 'uscita') {
        echo '
        $("#email-button").remove();';
    }
    echo '
	});

    function controlloTotale() {
        totale_da_pagare = $("#totale_da_pagare").val().toEnglish();
        totale_utente = 0;

        $("input[name*=da_pagare]").each(function() {

            totale_utente += $(this).val().toEnglish();

        });

        if (isNaN(totale_utente)) {
            totale_utente = 0;
        }

        totale_utente = Math.round(totale_utente * 100) / 100;
        totale_da_pagare = Math.round(totale_da_pagare * 100) / 100;

        diff = Math.abs(totale_da_pagare) - Math.abs(totale_utente);

        if (diff == 0) {
            $("#save").removeClass("hide");
            $("#totale").addClass("hide");
        } else {
            $("#save").addClass("hide");
            $("#totale").removeClass("hide");
        }

        $("#diff").html(diff.toLocale());
        $("#totale_utente").html(totale_utente.toLocale());
    }
</script>';
}
