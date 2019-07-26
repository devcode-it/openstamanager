<?php

include_once __DIR__.'/../../core.php';

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
                <button type="button" class="btn btn-xs btn-info pull-right" id="add-scadenza">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi scadenza').'
                </button>
            </h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<!-- Info scadenza -->
				<div class="col-md-7">
					<table class="table table-striped table-hover table-condensed table-bordered">';

$rs = $dbo->fetchArray('SELECT * FROM (co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id='.prepare($record['iddocumento']));
$dir = $rs[0]['dir'];
$numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$modulo_fattura = $dir == 'entrata' ? 'Fatture di vendita' : 'Fatture di acquisto';

if (!empty($rs)) {
    echo "
                        <tr>
                            <th width='120'>".($dir == 'entrata' ? tr('Cliente') : tr('Fornitore')).':</th>
                            <td>
                                '.Modules::link('Anagrafiche', $rs[0]['idanagrafica'], $rs[0]['ragione_sociale']).'
                            </td>
                        </tr>';
    echo '
                        <tr>
                            <th>'.tr('Documento').':</th>
                            <td>'.$rs[0]['descrizione'].'</td>
                        </tr>';
    echo '
                        <tr>
                            <th>'.tr('Numero').':</th>
                            <td>'.$numero.'</td>
                        </tr>';
    echo '
                        <tr>
                            <th>'.tr('Data').':</th>
                            <td>'.Translator::dateToLocale($rs[0]['data']).'</td>
                        </tr>';
} else {
    $rs = $dbo->fetchArray("SELECT * FROM co_scadenziario WHERE id='".$id_record."'");
    echo "
                        <tr>
                                <th width='120'>".tr('Descrizione').':</th>
                                <td><input type="text" class="form-control" name="descrizione" value="'.$rs[0]['descrizione'].'"></td>
                        </tr>';
}

echo '
                    </table>

                    '.Modules::link($modulo_fattura, $record['iddocumento'], '<i class="fa fa-folder-open"></i> '.tr('Apri documento'), null, 'class="btn btn-primary"').'
				</div>

				<!-- Elenco scadenze -->
				<div class="col-md-5">
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

$totale_da_pagare = 0;
$totale_pagato = 0;

//Scelgo la query in base al segmento
if ($record['iddocumento'] != 0) {
    $rs = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = (SELECT iddocumento FROM co_scadenziario s WHERE id='.prepare($id_record).') ORDER BY scadenza ASC');
} else {
    $rs = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE id='.prepare($id_record).' ORDER BY scadenza ASC');
}

for ($i = 0; $i < count($rs); ++$i) {
    if ($rs[$i]['da_pagare'] == $rs[$i]['pagato']) {
        $class = 'success';
    } elseif (abs($rs[$i]['pagato']) == 0) {
        $class = 'danger';
    } elseif (abs($rs[$i]['pagato']) <= abs($rs[$i]['da_pagare'])) {
        $class = 'warning';
    } else {
        $class = 'danger';
    }

    echo '
                        <tr class="'.$class.'">
                            <td align="center">
                                {[ "type": "date", "name": "scadenza['.$rs[$i]['id'].']", "value": "'.$rs[$i]['scadenza'].'" ]}
                            </td>

                            <td align="right">
                                {[ "type": "number", "name": "da_pagare['.$rs[$i]['id'].']", "decimals": 2, "value": "'.Translator::numberToLocale($rs[$i]['da_pagare'], 2).'", "onchange": "controlloTotale()" ]}
                            </td>

                            <td align="right">
                                {[ "type": "number", "name": "pagato['.$rs[$i]['id'].']", "decimals": 2, "value": "'.Translator::numberToLocale($rs[$i]['pagato']).'"  ]}
                            </td>
                            
                            <td align="center">
                                {[ "type": "date", "name": "data_concordata['.$rs[$i]['id'].']", "value": "'.$rs[$i]['data_concordata'].'" ]}
                            </td>
                        </tr>';
}

$totale_da_pagare = sum(array_column($rs, 'da_pagare'));
$totale_pagato = sum(array_column($rs, 'pagato'));

if ($totale_da_pagare == $totale_pagato) {
    echo '
<script>
$(document).ready(function(){
    $("#pulsanti").children().find("a:nth-child(2)").attr("disabled", true).addClass("disabled");
})
</script>';
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
                        <a onclick="launch_modal( 'Registra contabile pagamento', '<?php echo $rootdir; ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id']; ?>&dir=<?php echo $dir; ?>&id_scadenze=<?php echo $id_record; ?>', 1 );" class="btn btn-sm btn-primary"><i class="fa fa-euro"></i> <?php echo tr('Registra contabile pagamento...'); ?></a>
                    </div>

					<div class="clearfix"></div>

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
if ($records[0]['iddocumento'] == 0) {
                            ?>
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
<?php
                        }

echo '
<table class="hide">
    <tbody id="scadenza-template">
        <tr class="danger">
            <input type="hidden" name="nuova[-id-]" value="1">
            
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

?>

<script>
    globals.cifre_decimali = 2;

	$(document).ready(function(){
        controlloTotale();

        <?php
        if ($dir == 'uscita') {
            echo '
        $("#email-button").remove();';
        }
        ?>
	});

    function controlloTotale() {
        totale_da_pagare = $('#totale_da_pagare').val().toEnglish();
        totale_utente = 0;

        $('input[name*=da_pagare]').each(function() {

            totale_utente += $(this).val().toEnglish();

        });

        if (isNaN(totale_utente)) {
            totale_utente = 0;
        }

        totale_utente = Math.round(totale_utente * 100) / 100;
        totale_da_pagare = Math.round(totale_da_pagare * 100) / 100;


        diff = Math.abs(totale_da_pagare) - Math.abs(totale_utente);

        if (diff == 0) {
            $('#btn-saves').removeClass('hide');
            $('#totale').addClass('hide');
        } else {
            $('#btn-saves').addClass('hide');
            $('#totale').removeClass('hide');
        }

        $('#diff').html(diff.toLocale());
        $('#totale_utente').html(totale_utente.toLocale());
    }
</script>
