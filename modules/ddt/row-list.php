<?php

include_once __DIR__.'/../../core.php';

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

/*
    Articoli e righe generiche
*/
$q_art = 'SELECT *, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice FROM `dt_righe_ddt` WHERE idddt='.prepare($id_record).' GROUP BY idgruppo ORDER BY `order`';
$rs = $dbo->fetchArray($q_art);

if (!empty($rs)) {
    foreach ($rs as $r) {
        if (!empty($r['idarticolo'])) {
            $qserial = 'SELECT * FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND idarticolo='.prepare($r['idarticolo']).' AND idgruppo='.prepare($r['idgruppo']);
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

        echo '
    <tr data-id="'.$r['id'].'" '.$extra.'>
        <td align="left">';

        if (!empty($r['idarticolo'])) {
            echo '
            '.Modules::link('Articoli', $r['idarticolo'], $r['codice'].' - '.$r['descrizione']);

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
                    echo '<br>Lotto: '.$r['lotto'];
                }
                if ($r['serial'] != '') {
                    echo '<br>SN: '.$r['serial'];
                }
                if ($r['altro'] != '') {
                    echo '<br>'.$r['altro'];
                }
            }
        } else {
            echo nl2br($r['descrizione']);
        }

        // Aggiunta riferimento a ordine
        if (!empty($r['idordine'])) {
            $rso = $dbo->fetchArray('SELECT numero, numero_esterno, data FROM or_ordini WHERE id='.prepare($r['idordine']));
            $numero = ($rso[0]['numero_esterno'] != '') ? $rso[0]['numero_esterno'] : $rso[0]['numero'];
            echo '<br>Rif. ordine n<sup>o</sup>'.$numero.' del '.Translator::dateToLocale($rso[0]['data']);
        }
        echo '
        </td>';

        echo '
        <td class="text-center">';
        if (!str_contains($r['descrizione'], 'SCONTO')) {
            echo '
            <big>'.Translator::numberToLocale($r['qta'] - $r['qta_evasa']).'</big>
            <br><small>('._('Q.tà iniziale').': '.Translator::numberToLocale($r['qta']).')</small>';
        } else {
            echo '1';
        }
        echo '
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

        // Possibilità di rimuovere una riga solo se il ddt non è evaso
        echo '
        <td class="text-center">';
        if ($records[0]['stato'] != 'Evaso' && !str_contains($r['descrizione'], 'SCONTO')) {
            echo "
            <form action='".$rootdir.'/editor.php?id_module='.Modules::getModule($name)['id'].'&id_record='.$id_record."' method='post' id='delete-form-".$r['id']."' role='form'>
                <input type='hidden' name='backto' value='record-edit'>
                <input type='hidden' name='id_record' value='".$id_record."'>
                <input type='hidden' name='idriga' value='".$r['id']."'>
                <input type='hidden' name='dir' value='".$dir."'>";

            if (!empty($r['idarticolo'])) {
                echo "
                <input type='hidden' name='idarticolo' value='".$r['idarticolo']."'>
                <input type='hidden' name='op' value='unlink_articolo'>";
            } else {
                echo "

                <input type='hidden' name='op' value='unlink_riga'>";
            }

            echo "

                <div class='input-group-btn'>";

            if (!empty($r['idarticolo']) && $r['abilita_serial']) {
                echo "
                    <a class='btn btn-primary btn-xs'data-toggle='tooltip' title='Aggiorna SN...' onclick=\"launch_modal( 'Aggiorna SN', '".$rootdir.'/modules/ddt/add_serial.php?id_module='.$id_module.'&id_record='.$id_record.'&idgruppo='.$r['idgruppo'].'&idarticolo='.$r['idarticolo']."', 1 );\"><i class='fa fa-barcode' aria-hidden='true'></i></a>";
            }

            echo "
                    <a class='btn btn-xs btn-warning' title='Modifica questa riga...' onclick=\"launch_modal( 'Modifica riga', '".$rootdir.'/modules/ddt/add_riga.php?id_module='.$id_module.'&id_record='.$id_record.'&idriga='.$r['id'].'&dir='.$dir."', 1 );\">
                        <i class='fa fa-edit'></i>
                    </a>

                    <a class='btn btn-xs btn-danger' title='Rimuovi questa riga...' onclick=\"if( confirm('Rimuovere questa riga dal ddt?') ){ $('#delete-form-".$r['id']."').submit(); }\">
                        <i class='fa fa-trash'></i>
                    </a>
                </div>
            </form>";
        }

        if (!str_contains($r['descrizione'], 'SCONTO')) {
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

if (abs($sconto) > 0) {
    // SCONTO
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

if (abs($totale_iva) > 0) {
    echo '
    <tr>
        <td colspan="5" class="text-right">
            <b>'.strtoupper(_('IVA')).':</b>
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
