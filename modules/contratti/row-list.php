<?php

include_once __DIR__.'/../../core.php';

// Mostro le righe del preventivo
$totale_preventivo = 0.00;
$totale_imponibile = 0.00;
$totale_iva = 0.00;
$totale_da_evadere = 0.00;

/*
ARTICOLI
*/
$q_art = 'SELECT * FROM co_righe2_contratti WHERE idcontratto='.prepare($id_record);
$rs_art = $dbo->fetchArray($q_art);
$imponibile_art = 0.0;
$iva_art = 0.0;

echo '
<table class="table table-striped table-hover table-condensed">
    <tr>
        <th>'._('Descrizione').'</th>
        <th width="10%" class="text-center">'._('Q.tà').'</th>
        <th width="10%" class="text-center">'._('U.m.').'</th>
        <th width="12%" class="text-center">'._('Costo unitario').'</th>
        <th width="12%" class="text-center">'._('Iva').'</th>
        <th width="10%" class="text-center">'._('Imponibile').'</th>
        <th width="80"></th>
    </tr>';

// se ho almeno un articolo caricato mostro la riga
if (!empty($rs_art)) {
    foreach ($rs_art as $r) {
        // descrizione
        echo '
    <tr>
        <td>
        '.nl2br($r['descrizione']).'
        </td>';

        // q.tà
        echo '
        <td class="text-center">
            '.Translator::numberToLocale($r['qta']).'
        </td>';

        // um
        echo '
        <td class="text-center">
            '.$r['um'].'
        </td>';

        // costo unitario
        echo '
        <td class="text-center">
            '.Translator::numberToLocale($r['subtotale'] / $r['qta']).' &euro;
        </td>';

        // iva
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['iva'])." &euro;<br>
            <small class='help-block'>".$r['desc_iva'].'</small>
        </td>';

        // Imponibile
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['subtotale']).' &euro;';

        if ($r['sconto'] > 0) {
            echo '<br>
            <small class="help-block">- sconto '.Translator::numberToLocale($r['sconto'] * $r['qta']).' &euro;</small>';
        }

        echo '
        </td>';

        // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
        echo '
        <td class="text-center">';

        if ($records[0]['stato'] != 'Pagato') {
            echo '
            <form action="'.$rootdir.'/editor.php?id_module='.Modules::getModule('Contratti')['id'].'&id_record='.$id_record.'" method="post" id="delete-form-'.$r['id'].'" role="form">
                <input type="hidden" name="backto" value="record-edit">
                <input type="hidden" name="id_record" value="'.$id_record.'">
                <input type="hidden" name="op" value="delriga">
                <input type="hidden" name="idriga" value="'.$r['id'].'">
                <input type="hidden" name="idarticolo" value="'.$r['idarticolo'].'">

                <div class="btn-group">';
            echo "
                    <a class='btn btn-xs btn-warning' onclick=\"launch_modal('Modifica riga', '".$rootdir.'/modules/contratti/add_riga.php?idcontratto='.$id_record.'&idriga='.$r['id']."', 1 );\"><i class='fa fa-edit'></i></a>
                    <a href='javascript:;' class='btn btn-xs btn-danger' title='Rimuovi questa riga' onclick=\"if( confirm('Rimuovere questa riga dal contratto?') ){ $('#delete-form-".$r['id']."').submit(); }\"><i class='fa fa-trash'></i></a>";
            echo '
                </div>
            </form>';
        }

        echo '
        </td>
    </tr>';

        $iva_art += $r['iva'];
        $imponibile_art += $r['subtotale'] - ($r['sconto'] * $r['qta']);
        $imponibile_nosconto += $r['subtotale'];
        $sconto_art += $r['sconto'] * $r['qta'];
    }
}

// SCONTO
if (abs($sconto_art) > 0) {
    // Totale imponibile scontato
    echo '
    <tr>
        <td colspan="5"" class="text-right">
            <b>'.strtoupper(_('Imponibile')).':</b>
        </td>
        <td class="text-right">
            <span id="budget">'.Translator::numberToLocale($imponibile_nosconto).' &euro;</span>
        </td>
        <td></td>
    </tr>';

    echo '
    <tr>
        <td colspan="5"" class="text-right">
            <b>'.strtoupper(_('Sconto')).':</b>
        </td>
        <td class="text-right">
            '.Translator::numberToLocale($sconto_art).' &euro;
        </td>
        <td></td>
    </tr>';

    // Totale imponibile scontato
    echo '
    <tr>
        <td colspan="5"" class="text-right">
            <b>'.strtoupper(_('Imponibile scontato')).':</b>
        </td>
        <td class="text-right">
            '.Translator::numberToLocale($imponibile_art).' &euro;
        </td>
        <td></td>
    </tr>';
} else {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="5"" class="text-right">
            <b>'.strtoupper(_('Imponibile')).':</b>
        </td>
        <td class="text-right">
            <span id="budget">'.Translator::numberToLocale($imponibile_art).' &euro;</span>
        </td>
        <td></td>
    </tr>';
}

// Totale iva
echo '
    <tr>
        <td colspan="5"" class="text-right">
            <b>'.strtoupper(_('Iva')).':</b>
        </td>
        <td class="text-right">
            '.Translator::numberToLocale($iva_art).' &euro;
        </td>
        <td></td>
    </tr>';

// Totale contratto
echo '
    <tr>
        <td colspan="5"" class="text-right">
            <b>'.strtoupper(_('Totale')).':</b>
        </td>
        <td class="text-right">
            '.Translator::numberToLocale($imponibile_art + $iva_art).' &euro;
        </td>
        <td></td>
    </tr>';

echo '
</table>';
