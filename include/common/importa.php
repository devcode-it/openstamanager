<?php

// Inizializzazione
$documento = $options['documento'];
$documento_finale = $options['documento_finale'];
if (empty($documento) || (!empty($documento_finale) && $documento_finale->direzione != $documento->direzione)) {
    return;
}

// Informazioi utili
$dir = $documento->direzione;
$original_module = Modules::get($documento->module);

$name = !empty($documento_finale) ? $documento_finale->module : $options['module'];
$final_module = Modules::get($name);

// IVA predefinta
$id_iva = $id_iva ?: setting('Iva predefinita');

$righe = $documento->getRighe()->where('qta_rimanente', '>', 0);
if (empty($righe)) {
    echo '
<p>'.tr('Non ci sono elementi da evadere').'...</p>';

    return;
}

$link = !empty($documento_finale) ? ROOTDIR.'/editor.php?id_module='.$final_module['id'].'&id_record='.$documento_finale->id : ROOTDIR.'/controller.php?id_module='.$final_module['id'];

echo '
    
<form action="'.$link.'" method="post">
    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="backto" value="record-edit">
    
    <input type="hidden" name="id_documento" value="'.$documento->id.'">
    <input type="hidden" name="type" value="'.$options['type'].'">';

// Creazione fattura dal documento
if (!empty($options['create_document'])) {
    echo '
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Nuovo documento').'</h3>
        </div>
        <div class="box-body">
    
            <div class="row">
                <input type="hidden" name="create_document" value="on" />
        
                <div class="col-md-6">
                    {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
                </div>';

    if (in_array($final_module['name'], ['Fatture di vendita', 'Fatture di acquisto'])) {
        if ($op == 'nota_accredito' && !empty($segmenti)) {
            $segmento = $dbo->fetchOne("SELECT * FROM zz_segments WHERE predefined_accredito='1'");

            $id_segment = $segmento['id'];
        } else {
            $id_segment = $_SESSION['module_'.$final_module['id']]['id_segment'];
        }

        echo '
            <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Ritenuta contributi').'", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT * FROM co_ritenuta_contributi" ]}
                </div>
                
                <div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.prepare($final_module['id']).' ORDER BY name", "value": "'.$id_segment.'" ]}
                </div>';
    }

    echo '
            </div>
        </div>
    </div>';
}

    // Conto, rivalsa INPS, ritenuta d'acconto e ritenuta contributi
if (in_array($final_module['name'], ['Fatture di vendita', 'Fatture di acquisto']) && !in_array($original_module['name'], ['Fatture di vendita', 'Fatture di acquisto'])) {
    $id_rivalsa_inps = setting('Percentuale rivalsa');
    if ($dir == 'uscita') {
        $id_ritenuta_acconto = $documento->anagrafica->id_ritenuta_acconto_acquisti;
    } else {
        $id_ritenuta_acconto = $documento->anagrafica->id_ritenuta_acconto_vendite ?: setting("Percentuale ritenuta d'acconto");
    }
    $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");

    $show_rivalsa = !empty($id_rivalsa_inps);
    $show_ritenuta_acconto = setting("Percentuale ritenuta d'acconto") != '' || !empty($id_ritenuta_acconto);
    $show_ritenuta_contributi = !empty($documento_finale['id_ritenuta_contributi']);

    $id_conto = $documento_finale['idconto'];
    if (empty($id_conto)) {
        $id_conto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
    }

    echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Opzioni generali delle righe').'</h3>
        </div>
        <div class="box-body">';

    if ($show_rivalsa || $show_ritenuta_acconto) {
        echo '
            <div class="row">';

        // Rivalsa INPS
        if ($show_rivalsa) {
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Rivalsa').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.(($options['dir'] == 'entrata') ? setting('Tipo Cassa Previdenziale') : null).'" ]}
                </div>';
        }

        // Ritenuta d'acconto
        if ($show_ritenuta_acconto) {
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "id_ritenuta_acconto", "value": "'.$id_ritenuta_acconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
                </div>';

            // Calcola ritenuta d'acconto su
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"IMP\":\"Imponibile\", \"IMP+RIV\":\"Imponibile + rivalsa\"", "required": "1" ]}
                </div>';
        }

        echo '
            </div>';
    }

    $width = $show_ritenuta_contributi ? 6 : 12;

    echo '
            <div class="row">';

    // Ritenuta contributi
    if ($show_ritenuta_contributi) {
        echo '
                <div class="col-md-'.$width.'">
                    {[ "type": "checkbox", "label": "'.tr('Ritenuta contributi').'", "name": "ritenuta_contributi", "value": "1" ]}
                </div>';
    }

    // Conto
    echo '
                <div class="col-md-'.$width.'">
                    {[ "type": "select", "label": "'.tr('Conto').'", "name": "id_conto", "required": 1, "value": "'.$id_conto.'", "ajax-source": "'.($dir == 'entrata' ? 'conti-vendite' : 'conti-acquisti').'" ]}
                </div>
            </div>
        </div>
    </div>';
}

    echo '
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Righe da importare').'</h3>
        </div>
    
        <table class="box-body table table-striped table-hover table-condensed">
            <tr>
                <th>'.tr('Descrizione').'</th>
                <th width="10%">'.tr('Q.tà').'</th>
                <th width="15%">'.tr('Q.tà da evadere').'</th>
                <th width="20%">'.tr('Subtot.').'</th>';

if (!empty($options['serials'])) {
    echo '
                <th width="20%">'.tr('Seriali').'</th>';
}

echo '
            </tr>';

foreach ($righe as $i => $r) {
    // Descrizione
    echo '
            <tr>
                <td>
                    <input type="hidden" id="subtot_'.$i.'" name="subtot['.$r['id'].']" value="'.$r['prezzo_unitario_vendita'].'" />
                    <input type="hidden" id="sconto_'.$i.'" name="sconto['.$r['id'].']" value="'.$r['sconto'] / $r['qta'].'" />
                    <input type="hidden" id="iva_'.$i.'" name="iva['.$r['id'].']" value="'.$r['iva'] / $r['qta'].'" />
                    <input type="hidden" id="qtamax_'.$i.'" value="'.($r['qta_rimanente']).'" />';

    // Checkbox - da evadere?
    echo '
                    <input type="checkbox" checked="checked" id="checked_'.$i.'" name="evadere['.$r['id'].']" value="on" onclick="ricalcola_subtotale_riga('.$i.');" />';

    $descrizione = ($r->isArticolo() ? $r->articolo->codice.' - ' : '').$r['descrizione'];

    echo '&nbsp;'.nl2br($descrizione);

    echo '
                </td>';

    // Q.tà rimanente
    echo '
                <td class="text-center">
                    '.Translator::numberToLocale($r['qta_rimanente']).'
                </td>';

    // Q.tà da evadere
    echo '
                <td>
                    {[ "type": "number", "name": "qta_da_evadere['.$r['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$r['qta_rimanente'].'", "decimals": "qta", "min-value": "0", "extra": "'.(($r['is_descrizione']) ? 'readonly' : '').' onkeyup=\"ricalcola_subtotale_riga('.$i.');\"" ]}
                </td>';

    echo '
                <td>
                    <big id="subtotale_'.$i.'">'.moneyFormat($r->totale).'</big><br/>
    
                    <small style="color:#777;" id="subtotaledettagli_'.$i.'">'.Translator::numberToLocale($r->totale_imponibile).' + '.Translator::numberToLocale($r->iva).'</small>
                </td>';

    // Seriali
    if (!empty($options['serials'])) {
        echo '
                <td>';

        if (!empty($r['abilita_serial'])) {
            $serials = $r->serials;

            $list = [];
            foreach ($serials as $serial) {
                $list[] = [
                    'id' => $serial,
                    'text' => $serial,
                ];
            }

            if (!empty($serials)) {
                echo '
                    {[ "type": "select", "name": "serial['.$r['id'].'][]", "id": "serial_'.$i.'", "multiple": 1, "values": '.json_encode($list).', "value": "'.implode(',', $serials).'", "extra": "data-maximum=\"'.intval($r['qta_rimanente']).'\"" ]}';
            }
        }

        if (empty($r['abilita_serial']) || empty($serials)) {
            echo '-';
        }

        echo '
                </td>';
    }

    echo '
            </tr>';
}

// Totale
echo '
            <tr>
                <td colspan="'.(!empty($options['serials']) ? 4 : 3).'" align="right" class="text-right">
                    <b>'.tr('Totale').':</b>
                </td>
                <td class="text-right" colspan="2">
                    <big id="totale"></big>
                </td>
            </tr>
        </table>
    </div>';

echo '

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" id="submit_btn" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.$options['button'].'
            </button>
        </div>
    </div>
</form>';

echo '
<script src="'.ROOTDIR.'/lib/init.js"></script>';

?>

<script type="text/javascript">
    function ricalcola_subtotale_riga(r) {
        subtot = $("#subtot_" + r).val();
        sconto = $("#sconto_" + r).val();
        iva = $("#iva_" + r).val();

        qtamax = $("#qtamax_" + r).val() ? $("#qtamax_" + r).val() : 0;

        subtot = parseFloat(subtot);
        sconto = parseFloat(sconto);
        iva = parseFloat(iva);
        qtamax = parseFloat(qtamax);

        subtot = subtot - sconto;

        qta = $("#qta_" + r).val().toEnglish();

        // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
        if (qta > qtamax) {
            qta = qtamax;

            $('#qta_' + r).val(qta);
        }

        // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
        if (isNaN(qta) || !$('#checked_' + r).is(':checked')) {
            qta = 0;
        }

        $("#serial_" + r).selectClear();
        $("#serial_" + r).select2("destroy");
        $("#serial_" + r).data('maximum', qta);
        start_superselect();

        subtotale = (subtot * qta + iva * qta).toLocale();

        $("#subtotale_" + r).html(subtotale + " " + globals.currency);
        $("#subtotaledettagli_" + r).html((subtot * qta).toLocale() + " + " + (iva * qta).toLocale());

        ricalcola_totale();
    }

    function ricalcola_totale() {
        totale = 0.00;

        $('input[id*=qta_]').each(function() {
            qta = $(this).val().toEnglish();
            r = $(this).attr("id").replace("qta_", "");

            if (!$("#checked_" + r).is(":checked") || isNaN(qta)) {
                qta = 0;
            }

            subtot = $("#subtot_" + r).val();
            sconto = $("#sconto_" + r).val();
            iva = $("#iva_" + r).val();

            subtot = parseFloat(subtot);
            sconto = parseFloat(sconto);
            iva = parseFloat(iva);

            subtot = subtot - sconto;

            if(subtot) {
                totale += subtot * qta + iva * qta;
            }
        });

        $('#totale').html((totale.toLocale()) + " " + globals.currency);

        <?php

        if (empty($options['allow-empty'])) {
            echo '
        if (tot_qta > 0)
            $("#submit_btn").show();
        else
            $("#submit_btn").hide();';
        }

        ?>
    }

    ricalcola_totale();
</script>
