<?php

include_once __DIR__.'/../../core.php';

$tipi_interventi = $dbo->fetchArray('SELECT * FROM in_tipiintervento ORDER BY descrizione');

$tecnici = $dbo->fetchArray("SELECT idanagrafica, ragione_sociale FROM an_anagrafiche WHERE idanagrafica IN (
    SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica IN (
        SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = 'Tecnico'
    )
) AND deleted_at IS NULL ORDER BY ragione_sociale");

if (!empty($tecnici)) {
    echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-list">

	<table class="table table-striped table-condensed">';

    foreach ($tecnici as $tecnico) {
        echo '
        <tr>
            <th>'.$tecnico['ragione_sociale'].'</th>
            <th>'.tr('Attività').'</th>
            <th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

            <th>'.tr('Costo orario').' <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th>'.tr('Costo km').' <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th>'.tr('Costo diritto ch.').' <span class="tip" title="'.tr('Costo interno').'"><i class="fa fa-question-circle-o"></i></span></th>
            <th width="40"></th>
        </tr>';

        // Tipi di interventi
        foreach ($tipi_interventi as $tipo_intervento) {
            // Lettura costi
            $rsc = $dbo->fetchArray('SELECT * FROM in_tariffe WHERE idtecnico='.prepare($tecnico['idanagrafica']).' AND idtipointervento='.prepare($tipo_intervento['idtipointervento']));

            echo '
        <tr>
            <td></td>
            <td>'.$tipo_intervento['descrizione'].'</td>

            <td>
                <input type="text" class="form-control inputmask-decimal" name="costo_ore['.$tecnico['idanagrafica'].']['.$tipo_intervento['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_ore']).'">
            </td>

            <td>
                <input type="text" class="form-control inputmask-decimal" name="costo_km['.$tecnico['idanagrafica'].']['.$tipo_intervento['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_km']).'">
            </td>

            <td>
                <input type="text" class="form-control inputmask-decimal" name="costo_dirittochiamata['.$tecnico['idanagrafica'].']['.$tipo_intervento['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_dirittochiamata']).'">
            </td>

            <td>
                <input type="text" class="form-control inputmask-decimal" name="costo_ore_tecnico['.$tecnico['idanagrafica'].']['.$tipo_intervento['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_ore_tecnico']).'">
            </td>

            <td>
                <input type="text" class="form-control inputmask-decimal" name="costo_km_tecnico['.$tecnico['idanagrafica'].']['.$tipo_intervento['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_km_tecnico']).'">
            </td>

            <td>
                <input type="text" class="form-control inputmask-decimal" name="costo_dirittochiamata_tecnico['.$tecnico['idanagrafica'].']['.$tipo_intervento['idtipointervento'].']" value="'.Translator::numberToLocale($rsc[0]['costo_dirittochiamata_tecnico']).'">
            </td>
            <td>
                <button type="button" class="btn btn-primary" data-toggle="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.$rootdir.'/modules/tecnici_tariffe/actions.php\', { op: \'import\', idtecnico: \''.$tecnico['idanagrafica'].'\', idtipointervento: \''.$tipo_intervento['idtipointervento'].'\' }, function(data){ location.href=\''.$rootdir.'/controller.php?id_module='.$id_module.'\'; } ); }">
                    <i class="fa fa-download"></i>
                </button>
            </td>
        </tr>';
        }
    }

    echo '
    </table>

    <div class="pull-right">
        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>
    </div>
    <div class="clearfix"></div>
</form>';
} else {
    echo '
<p>'.tr('Non sono presenti anagrafiche di tipo "Tecnico"').'</p>';
}
