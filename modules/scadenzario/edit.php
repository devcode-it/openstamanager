<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="'.$id_record.'">

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'._('Dettagli scadenza').'</h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<!-- Info scadenza -->
				<div class="col-md-7">
					<table class="table table-striped table-hover table-condensed table-bordered">';

$rs = $dbo->fetchArray('SELECT * FROM (co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id='.prepare($records[0]['iddocumento']));

$numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];

if ($rs[0]['dir'] == 'entrata') {
    $dir = 'entrata';
    $modulo = 'Fatture di vendita';
    echo "
                        <tr>
                            <th width='120'>"._('Cliente').':</th>
                            <td>
                                '.Modules::link('Anagrafiche', $rs[0]['idanagrafica'], $rs[0]['ragione_sociale']).'
                            </td>
                        </tr>';
} else {
    $dir = 'uscita';
    $modulo = 'Fatture di acquisto';
    echo "
                        <tr>
                            <th width='120'>"._('Fornitore').':</th>
                            <td>'.$rs[0]['ragione_sociale'].'</td>
                        </tr>';
}

echo '
                        <tr>
                            <th>'._('Documento').':</th>
                            <td>'.$rs[0]['descrizione'].'</td>
                        </tr>';
echo '
                        <tr>
                            <th>'._('Numero').':</th>
                            <td>'.$numero.'</td>
                        </tr>';
echo '
                        <tr>
                            <th>'._('Data').':</th>
                            <td>'.Translator::dateToLocale($rs[0]['data']).'</td>
                        </tr>';
echo '
                    </table>

                    '.Modules::link($modulo, $records[0]['iddocumento'], '<i class="fa fa-folder-open"></i> '._('Apri documento'), null, 'class="btn btn-primary"').'
				</div>

				<!-- Elenco scadenze -->
				<div class="col-md-5">
					<table class="table table-hover table-condensed table-bordered">
					    <tr>
                            <th width="100">'._('Data').'</th>
                            <th width="150">'._('Importo').'</th>
                            <th width="150">'._('Pagato').'</th>
                        </tr>';

$totale_da_pagare = 0;
$totale_pagato = 0;

$rs = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = (SELECT iddocumento FROM co_scadenziario s WHERE id='.prepare($id_record).') ORDER BY scadenza ASC');

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
                                {[ "type": "date", "name": "data['.$rs[$i]['id'].']", "value": "'.$rs[$i]['scadenza'].'" ]}
                            </td>

                            <td align="right">
                                {[ "type": "number", "name": "scadenza['.$rs[$i]['id'].']", "value": "'.Translator::numberToLocale($rs[$i]['da_pagare']).'" ]}
                            </td>

                            <td align="right">
                                {[ "type": "number", "name": "pagato['.$rs[$i]['id'].']", "value": "'.Translator::numberToLocale($rs[$i]['pagato']).'" ]}
                            </td>
                        </tr>';

    $totale_da_pagare += $rs[$i]['da_pagare'];
    $totale_pagato += $rs[$i]['pagato'];
}

echo '
                        <tr>
                            <td align="right"><b>'._('Totale').'</b></td>
                            <td align="right" id="totale_utente">'.Translator::numberToLocale($totale_da_pagare).'</td>
                            <td align="right"></td>
                        </tr>';

?>

					</table>

					<a onclick="launch_modal( 'Aggiungi prima nota', '<?php echo $rootdir ?>/add.php?id_module=<?php echo Modules::getModule('Prima nota')['id'] ?>&iddocumento=<?php echo $records[0]['iddocumento'] ?>&dir=<?php echo $dir ?>', 1 );" class="btn btn-sm btn-primary pull-right"><i class="fa fa-euro"></i> <?php echo _('Aggiungi prima nota...'); ?></a>

					<p class="text-danger hide" id="totale"><?php echo str_replace('_NUM_', '<b>'.Translator::numberToLocale($totale_da_pagare).'&euro;</b>', _('Il totale da pagare deve essere pari a _NUM_')); ?>.<br><?php echo _('Differenza di'); ?> <span id="diff"></span> &euro;.</p>

					<input type="hidden" id="totale_da_pagare" value="<?php echo Translator::numberToLocale($totale_da_pagare) ?>">
				</div>
			</div>

	        <div class="clearfix"></div><br>
			<div class="pull-right">
				<button type="submit" id="btn-saves" class="btn btn-success hide"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
			</div>
		</div>
	</div>

	<div class="clearfix"></div>
</form>


<script>
	$(document).ready( function(){
		totale_ok();
		$('input[name*=scadenza]').keyup( function(){ totale_ok(); } );
	});

	function totale_ok(){
		totale_da_pagare	= $('#totale_da_pagare').val().toEnglish();
		totale_utente		= 0;

		$('input[name*=scadenza]').each( function(){
			totale_utente += $(this).val().toEnglish();
		});

		if( isNaN(totale_utente) ){
			totale_utente = 0;
		}

		totale_utente = Math.round(totale_utente*100)/100;
		totale_da_pagare = Math.round(totale_da_pagare*100)/100;


		diff = Math.abs(totale_da_pagare) - Math.abs(totale_utente);

		if( diff == 0 ){
			$('#btn-saves').removeClass('hide');
			$('#totale').addClass('hide');
		}

		else{
			$('#btn-saves').addClass('hide');
			$('#totale').removeClass('hide');
		}

		$('#diff').html(diff.toFixedLocale());
		$('#totale_utente').html(totale_utente.toFixedLocale());
	}
</script>
