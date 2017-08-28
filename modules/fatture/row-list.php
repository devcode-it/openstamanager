<?php

include_once __DIR__.'/../../core.php';

/*
    Righe fattura
*/
$q = "SELECT *, IFNULL((SELECT codice FROM mg_articoli WHERE id=idarticolo),'') AS codice, (SELECT descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=IF(co_righe_documenti.idconto = 0, (SELECT idconto FROM co_documenti WHERE iddocumento=".prepare($id_record)." LIMIT 1), co_righe_documenti.idconto)) AS descrizione_conto FROM `co_righe_documenti` WHERE iddocumento=".prepare($id_record).' GROUP BY idgruppo ORDER BY `order`';
$rs = $dbo->fetchArray($q);

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'._('Descrizione').'</th>
        <th width="120">'._('Q.tà').'</th>
        <th width="80">'._('U.m.').'</th>
        <th width="120">'._('Costo unitario').'</th>
        <th width="120">'._('Iva').'</th>
        <th width="120">'._('Imponibile').'</th>
        <th width="60"></th>
    </tr>
    <tbody class="sortable">';

if (!empty($rs)) {
    foreach ($rs as $r) {
        $extra = '';

        // Articoli
        if (!empty($r['idarticolo'])) {
            $modulo = Modules::getModule('Articoli')['id'];
            $id = $r['idarticolo'];

            $r['descrizione'] = $r['codice'].' - '.$r['descrizione'];

            $delete = 'unlink_articolo';

            $qserial = 'SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idarticolo='.prepare($r['idarticolo']).' AND idgruppo='.prepare($r['idgruppo']);
            $rsserial = $dbo->fetchArray($qserial);

            $mancanti = 0;
            $serials = [];

            if (!empty($r['abilita_serial'])) {
                foreach ($rsserial as $seriali) {
                    $seriali['serial'] = trim($seriali['serial']);
                    if (!empty($seriali['serial'])) {
                        $serials[] = $seriali['serial'];
                    } else {
                        ++$mancanti;
                    }
                }
            }

            if ($mancanti > 0) {
                $extra = 'class="warning"';
            }
        }
        // Preventivi
        elseif (!empty($r['idpreventivo'])) {
            $modulo = Modules::getModule('Preventivi')['id'];
            $id = $r['idpreventivo'];

            $delete = 'unlink_preventivo';
        }
        // Contratti
        elseif (!empty($r['idcontratto'])) {
            $modulo = Modules::getModule('Contratti')['id'];
            $id = $r['idcontratto'];

            $delete = 'unlink_contratto';
        }
        // Intervento
        elseif (!empty($r['idintervento'])) {
            $modulo = Modules::getModule('Interventi')['id'];
            $id = $r['idintervento'];

            $delete = 'unlink_intervento';
        }
        // Righe generiche
        else {
            $modulo = 0;
            $id = 0;

            $delete = 'unlink_riga';
        }

        echo '
    <tr data-id="'.$r['id'].'" '.$extra.'>';

        echo '
        <td>
            '.Modules::link($modulo, $id, $r['descrizione']).'
            <small class="pull-right text-muted">'.$r['descrizione_conto'].'</small>';

        if (!empty($r['abilita_serial'])) {
            if (!empty($mancanti)) {
                echo '
            <br><b><small class="text-danger">'.str_replace('_NUM_', $mancanti, _('_NUM_ serial mancanti')).'</small></b>';
            }
            if (!empty($serials)) {
                echo '
            <br>'._('SN').': '.implode(', ', $serials);
            }
        } else {
            if ($r['lotto'] != '') {
                echo '
                <br>'._('Lotto').': '.$r['lotto'];
            }
            if ($r['serial'] != '') {
                echo '
                <br>'._('SN').': '.$r['serial'];
            }
            if ($r['altro'] != '') {
                echo '
                <br>'.$r['altro'];
            }
        }

        // Aggiunta riferimento a ordine
        if (!empty($r['idordine'])) {
            $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id='.prepare($r['idordine']));
            $numero = ($rso[0]['numero_esterno'] != '') ? $rso[0]['numero_esterno'] : $rso[0]['numero'];
            echo '
        <br>'.str_replace(['_NUM_', '_DATE_'], [$numero, Translator::dateToLocale($rso[0]['data'])], _('Rif. ordine _NUM_ del _DATE_'));
        } elseif (!empty($r['idddt'])) {
            $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM dt_ddt WHERE id='.prepare($r['idddt']));
            $numero = ($rso[0]['numero_esterno'] != '') ? $rso[0]['numero_esterno'] : $rso[0]['numero'];
            echo '
        <br>'.str_replace(['_NUM_', '_DATE_'], [$numero, Translator::dateToLocale($rso[0]['data'])], _('Rif. ddt _NUM_ del _DATE_'));
        } elseif (!empty($r['idpreventivo'])) {
            $rso = $dbo->fetchArray('SELECT numero, data_bozza FROM co_preventivi WHERE id='.prepare($r['idpreventivo']));
            echo '
        <br>'.str_replace(['_NUM_', '_DATE_'], [$rso[0]['numero'], Translator::dateToLocale($rso[0]['data_bozza'])], _('Rif. preventivo _NUM_ del _DATE_'));
        }

        echo '
        </td>';

        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['qta']).'
        </td>';

        // Unità di misura
        echo '
        <td class="text-center">
            '.$r['um'].'
        </td>';

        // Costo unitario
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['subtotale'] / $r['qta']).' &euro;';

        if ($r['sconto_unitario'] > 0) {
            echo '
            <br><small class="label label-danger">- sconto '.Translator::numberToLocale($r['sconto_unitario']).($r['tipo_sconto'] == 'PRC' ? '%' : ' &euro;').'</small>';
        }

        echo '
        </td>';

        // Iva
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['iva']).' &euro;
            <br><small class="help-block">'.$r['desc_iva'].'</small>
        </td>';

        // Imponibile
        echo '
        <td class="text-right">
            '.Translator::numberToLocale($r['subtotale'] - $r['sconto']).' &euro;
        </td>';

        // Possibilità di rimuovere una riga solo se la fattura non è pagata
        echo '
        <td class="text-center">';

        if ($records[0]['stato'] != 'Pagato' && $records[0]['stato'] != 'Emessa' && strpos($r['descrizione'], 'SCONTO') === false) {
            echo "
            <form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='delete-form-".$r['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='idriga' value='".$r['id']."'>
                <input type='hidden' name='op' value='".$delete."'>";

            if (!empty($r['idarticolo'])) {
                echo "
                <input type='hidden' name='idarticolo' value='".$r['idarticolo']."'>";
            }

            echo "
                <div class='input-group-btn'>";

            if (!empty($r['idarticolo']) && $r['abilita_serial'] && (empty($r['idddt']) || empty($r['idintervento']))) {
                echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$rootdir.'/modules/fatture/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idgruppo='.$r['idgruppo'].'&idarticolo='.$r['idarticolo']."', 1 );\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
            }

            echo "
                    <a class='btn btn-xs btn-warning' title='Modifica questa riga...' onclick=\"launch_modal( 'Modifica riga', '".$rootdir.'/modules/fatture/edit_riga.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id']."', 1 );\"><i class='fa fa-edit'></i></a>
                    <a class='btn btn-xs btn-danger' title='Rimuovi questa riga...' onclick=\"if( confirm('Rimuovere questa riga dalla fattura?') ){ $('#delete-form-".$r['id']."').submit(); }\"><i class='fa fa-trash'></i></a>
                </div>
            </form>";
        }

        if (strpos($r['descrizione'], 'SCONTO') === false) {
            echo '
            <div class="handle clickable" style="padding:10px">
                <i class="fa fa-sort"></i>
            </div>';
        }

        echo '
        </td>

    </tr>';
    }
}

echo '
    </tbody>';

// Calcoli
$imponibile = sum(array_column($rs, 'subtotale'));
$sconto = sum(array_column($rs, 'sconto'));
$iva = sum(array_column($rs, 'iva'));

$imponibile_scontato = sum($imponibile, -$sconto);

$totale_iva = sum($iva, $records[0]['iva_rivalsainps']);

$totale = sum([
    $imponibile_scontato,
    $records[0]['rivalsainps'],
    $totale_iva,
]);

$netto_a_pagare = sum([
    $totale,
    $marca_da_bollo,
    -$records[0]['ritenutaacconto'],
]);

// IMPONIBILE
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Imponibile')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($imponibile).' &euro;
        </td>
        <td></td>
    </tr>';

// SCONTO
if (abs($sconto) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Sconto')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($sconto).' &euro;
        </td>
        <td></td>
    </tr>';

    // IMPONIBILE SCONTATO
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Imponibile scontato')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($imponibile_scontato).' &euro;
        </td>
        <td></td>
    </tr>';
}

// RIVALSA INPS
if (abs($records[0]['rivalsainps']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Rivalsa INPS')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($records[0]['rivalsainps']).' &euro;
        </td>
        <td></td>
    </tr>';
}

// IVA
if (abs($totale_iva) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Iva')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($totale_iva).' &euro;
        </td>
        <td></td>
    </tr>';
}

// TOTALE
echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Totale')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($totale).' &euro;
        </td>
        <td></td>
    </tr>';

// Mostra marca da bollo se c'è
if (abs($records[0]['bollo']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Marca da bollo')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($records[0]['bollo']).' &euro;
        </td>
        <td></td>
    </tr>';
}

// RITENUTA D'ACCONTO
if (abs($records[0]['ritenutaacconto']) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_("Ritenuta d'acconto")).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($records[0]['ritenutaacconto']).' &euro;
        </td>
        <td></td>
    </tr>';

    //$netto_a_pagare -= $records[0]['ritenutaacconto'];
}

// NETTO A PAGARE
if ($totale != $netto_a_pagare) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('Netto a pagare')).':</b>
        </td>
        <td align="right">
            '.Translator::numberToLocale($netto_a_pagare).' &euro;
        </td>
        <td></td>
    </tr>';
}

echo '
</table>';

echo '
<script>
$(document).ready(function(){
	$(".sortable").each(function() {
        $(this).sortable({
            axis: "y",
            handle: ".handle",
			cursor: "move",
			dropOnEmpty: true,
			scroll: true,
			start: function(event, ui) {
				ui.item.data("start", ui.item.index());
			},
			update: function(event, ui) {
				$.post("'.$rootdir.'/actions.php", {
					id: ui.item.data("id"),
					id_module: '.$id_module.',
					id_record: '.$id_record.',
					op: "update_position",
					start: ui.item.data("start"),
					end: ui.item.index()
				});
			}
		});
	});
});
</script>';
