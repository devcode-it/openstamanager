<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-list">

	<div class="pull-right">
		<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '._('Salva modifiche').'</button>
	</div>

	<div class="clearfix"></div>
	<br>

	<table class="table table-striped table-condensed">';

$rst = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica, ragione_sociale FROM (an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica) WHERE an_tipianagrafiche.descrizione='Tecnico' ORDER BY ragione_sociale");

for ($t = 0; $t < count($rst); ++$t) {
    echo '
			<tr>
				<th>'.$rst[$t]['ragione_sociale'].'</th>
				<th>'._('Attività').'</th>
				<th>'._('Addebito orario').'</th>
				<th>'._('Addebito km').'</th>
				<th>'._('Addebito diritto ch.').'</th>

				<th>'._('Costo orario').'</th>
				<th>'._('Costo km').'</th>
				<th>'._('Costo diritto ch.').'</th>
                <th width="40"></th>
			</tr>';

    // Attività
    $rsa = $dbo->fetchArray('SELECT * FROM in_tipiintervento ORDER BY descrizione');

    for ($a = 0; $a < count($rsa); ++$a) {
        //Lettura costi
        $rsc = $dbo->fetchArray('SELECT * FROM in_tariffe WHERE idtecnico='.prepare($rst[$t]['idanagrafica']).' AND idtipointervento='.prepare($rsa[$a]['idtipointervento']));
        echo '
				<tr>
					<td></td>
					<td>'.$rsa[$a]['descrizione'].'</td>

					<td>
                        <input type="text" class="form-control inputmask-decimal" name="costo_ore['.$rst[$t]['idanagrafica'].']['.$rsa[$a]['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_ore']).'">
                    </td>

					<td>
                        <input type="text" class="form-control inputmask-decimal" name="costo_km['.$rst[$t]['idanagrafica'].']['.$rsa[$a]['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_km']).'">
                    </td>

                    <td>
                        <input type="text" class="form-control inputmask-decimal" name="costo_dirittochiamata['.$rst[$t]['idanagrafica'].']['.$rsa[$a]['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_dirittochiamata']).'">
                    </td>

					<td>
                        <input type="text" class="form-control inputmask-decimal" name="costo_ore_tecnico['.$rst[$t]['idanagrafica'].']['.$rsa[$a]['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_ore_tecnico']).'">
                    </td>

					<td>
                        <input type="text" class="form-control inputmask-decimal" name="costo_km_tecnico['.$rst[$t]['idanagrafica'].']['.$rsa[$a]['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_km_tecnico']).'">
                    </td>

					<td>
                        <input type="text" class="form-control inputmask-decimal" name="costo_dirittochiamata_tecnico['.$rst[$t]['idanagrafica'].']['.$rsa[$a]['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_dirittochiamata_tecnico']).'">
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary" data-toggle="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.$rootdir.'/modules/tecnici_tariffe/actions.php\', { op: \'import\', idtecnico: \''.$rst[$t]['idanagrafica'].'\', idtipointervento: \''.$rsa[$a]['idtipointervento'].'\' }, function(data){ location.href=\''.$rootdir.'/controller.php?id_module='.$id_module.'\'; } ); }">
                            <i class="fa fa-download"></i>
                        </button>
                    </td>
				</tr>';
    }
}

echo '
	</table>

	<div class="pull-right">
		<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '._('Salva modifiche').'</button>
	</div>
	<div class="clearfix"></div>';
