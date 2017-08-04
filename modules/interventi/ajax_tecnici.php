<?php

include_once __DIR__.'/../../core.php';

include_once $docroot.'/modules/interventi/modutil.php';

switch (get('op')) {
    // OPERAZIONI PER AGGIUNTA NUOVA SESSIONE DI LAVORO
    case 'add_sessione':
        $idtecnico = get('idtecnico');

        // Verifico se l'intervento è collegato ad un contratto
        $rs = $dbo->fetchArray('SELECT idcontratto FROM co_righe_contratti WHERE idintervento='.prepare($id_record));
        $idcontratto = $rs[0]['idcontratto'];

        $ore = 1;

        $inizio = date('Y-m-d H:\0\0');
        $fine = date_modify(date_create(date('Y-m-d H:\0\0')), '+'.$ore.' hours')->format('Y-m-d H:\0\0');

        add_tecnico($id_record, $idtecnico, $inizio, $fine, $idcontratto);

        break;

    // RIMOZIONE SESSIONE DI LAVORO
    case 'del_sessione':
        $dbo->query('DELETE FROM in_interventi_tecnici WHERE id='.prepare(get('id')));
        break;
}

$show_costi = true;

// Limitazione delle azioni dei tecnici
if ($user['gruppo'] == 'Tecnici') {
    $show_costi = !empty($user_idanagrafica) && get_var('Mostra i prezzi al tecnico');
}

// RECUPERO IL TIPO DI INTERVENTO
$rss = $dbo->fetchArray('SELECT idtipointervento FROM in_interventi WHERE id='.prepare($id_record));
$idtipointervento = $rs[0]['idtipointervento'];

$query = 'SELECT * FROM an_anagrafiche JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE deleted=0 AND idintervento='.prepare($id_record)." AND idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica = (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = 'Tecnico')) ORDER BY ragione_sociale ASC, in_interventi_tecnici.orario_inizio ASC";
$rs2 = $dbo->fetchArray($query);
$prev_tecnico = '';

if (!empty($rs2)) {
    foreach ($rs2 as $key => $r) {
        $idtecnico = $r['idanagrafica'];

        // Intestazione tecnico
        if ($prev_tecnico != $r['ragione_sociale']) {
            echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed '.$class_tecnico.'">
        <tr>
            <th width="200"><i class="fa fa-user"></i> '.$r['ragione_sociale'].'</th>
            <th width="300">'._('Orario').'</th>
            <th width="190">'._('Ore').'</th>
            <th width="190">'._('Km').'</th>
            <th width="100">'._('Sconto ore').'</th>
            <th width="100">'._('Sconto km').'</th>
            <th width="40"></th>
        </tr>';
        }

        $id = $r['id'];

        // Lettura costi unitari salvati al momento dell'intervento
        $sconto_unitario = $r['sconto_unitario'];
        $tipo_sconto = $r['tipo_sconto'];

        $scontokm_unitario = $r['scontokm_unitario'];
        $tipo_scontokm = $r['tipo_scontokm'];

        $sconto = $r['sconto'];
        $scontokm = $r['scontokm'];

        $costo_ore_unitario = $r['prezzo_ore_unitario'];
        $costo_km_unitario = $r['prezzo_km_unitario'];
        $costo_dirittochiamata = $r['prezzo_dirittochiamata'];

        $costo_ore_unitario_tecnico = $r['prezzo_ore_unitario_tecnico'];
        $costo_km_unitario_tecnico = $r['prezzo_km_unitario_tecnico'];
        $costo_dirittochiamata_tecnico = $r['prezzo_dirittochiamata_tecnico'];

        $costo_km_consuntivo_tecnico = $r['prezzo_km_consuntivo_tecnico'];
        $costo_ore_consuntivo_tecnico = $r['prezzo_ore_consuntivo_tecnico'];
        $costo_km_consuntivo = $r['prezzo_km_consuntivo'];
        $costo_ore_consuntivo = $r['prezzo_ore_consuntivo'];

        $orario_inizio = '0000-00-00 00:00:00';
        $orario_fine = '0000-00-00 00:00:00';

        $pausa_inizio = '0000-00-00 00:00:00';
        $pausa_fine = '0000-00-00 00:00:00';

        if (!empty($r['orario_inizio'])) {
            $orario_inizio = Translator::timestampToLocale($r['orario_inizio']);
        }

        if (!empty($r['orario_fine'])) {
            $orario_fine = Translator::timestampToLocale($r['orario_fine']);
        }

        if (!empty($r['pausa_inizio'])) {
            $pausa_inizio = Translator::timestampToLocale($r['pausa_inizio']);
        }

        if (!empty($r['pausa_fine'])) {
            $pausa_fine = Translator::timestampToLocale($r['pausa_fine']);
        }

        $orario = $orario_inizio.' - '.$orario_fine;
        $pausa = $pausa_inizio.' - '.$pausa_fine;

        $km = $r['km'];
        $ore = $r['ore'];

        // Costi unitari
        echo '
        <input type="hidden" name="idtecnico['.$id.']" value="'.$idtecnico.'" />
        <input type="hidden" name="id_[]" value="'.$id.'" />

        <input type="hidden" name="prezzo_km_unitario['.$id.']" value="'.$costo_km_unitario.'" />
        <input type="hidden" name="prezzo_dirittochiamata['.$id.']" value="'.$costo_dirittochiamata.'" />

        <input type="hidden" name="prezzo_ore_unitario_tecnico['.$id.']" value="'.$costo_ore_unitario_tecnico.'" />
        <input type="hidden" name="prezzo_km_unitario_tecnico['.$id.']" value="'.$costo_km_unitario_tecnico.'" />
        <input type="hidden" name="prezzo_dirittochiamata_tecnico['.$id.']" value="'.$costo_dirittochiamata_tecnico.'" />';

        echo '
        <tr class="'.$class_tecnico.'">
            <td class="tecn_'.$r['idtecnico'].'">';

        if ($rs[0]['stato'] != 'Fatturato') {
            // Elenco tipologie di interventi
            echo '
                {[ "type": "select", "name": "idtipointerventot['.$id.']", "value": "'.$r['idtipointervento'].'", "values": "query=SELECT idtipointervento AS id, descrizione, IFNULL((SELECT costo_ore FROM in_tariffe WHERE idtipointervento=in_tipiintervento.idtipointervento AND idtecnico='.prepare($r['idtecnico']).'), 0) AS costo_orario FROM in_tipiintervento ORDER BY descrizione" ]}';
        }

        echo '
            </td>';

        // Orario
        echo '
            <td>';
        if ($rs[0]['stato'] == 'Fatturato') {
            echo '
                <span>'.$ora_dal1.'</span>
                <input type="hidden" id="orario_'.$id.'" name="orario['.$id.']" style="'.$display.'" value="'.$orario.'">';
        } else {
            echo '
                <input class="form-control text-center datetimepicker" type="text" id="orario_'.$id.'" name="orario['.$id.']" value="'.$orario.'">';
        }
        echo '
            </td>';

        // ORE
        echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "ore['.$id.']", "value": "'.$ore.'", "disabled": 1 ]}

                <div class="extra hide">
                    <table class="table table-condensed table-bordered">
                    <tr><th class="text-danger">'._('Costo').':</th><td align="right">'.Translator::numberToLocale($costo_ore_consuntivo_tecnico)."<small class='help-block'>".Translator::numberToLocale($costo_ore_unitario_tecnico).'x'.Translator::numberToLocale($ore).'<br>+'.Translator::numberToLocale($costo_dirittochiamata_tecnico).'</small></td></tr>
                    <tr><th>'._('Addebito').':</th><td align="right">'.Translator::numberToLocale($costo_ore_consuntivo).'<small class="help-block">'.Translator::numberToLocale($costo_ore_unitario).'x'.Translator::numberToLocale($ore).'<br>+'.Translator::numberToLocale($costo_dirittochiamata).'</small></td></tr>
                    <tr><th>'._('Scontato').':</th><td align="right">'.Translator::numberToLocale($costo_ore_consuntivo - $sconto).'</td></tr>
                    </table>
                </div>
            </td>';

        // KM
        echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "km['.$id.']", "value": "'.$km.'" ]}

                <div class="extra hide">
                    <table class="table table-condensed table-bordered">
                    <tr>
                        <th class="text-danger">'._('Costo').':</th>
                        <td align="right">
                            '.Translator::numberToLocale($costo_km_consuntivo_tecnico).'
                            <small class="help-block">
                                '.Translator::numberToLocale($costo_km_unitario_tecnico).'x'.Translator::numberToLocale($km).'
                            </small><br>
                        </td>
                    </tr>
                    <tr>
                        <th>'._('Addebito').':</th>
                        <td align="right">
                            '.Translator::numberToLocale($costo_km_consuntivo).'
                            <small class="help-block">
                                '.Translator::numberToLocale($costo_km_unitario).'x'.Translator::numberToLocale($km).'
                            </small><br>
                        </td>
                    </tr>
                    <tr>
                        <th>'._('Scontato').':</th>
                        <td align="right">'.Translator::numberToLocale($costo_km_consuntivo - $scontokm).'</td>
                    </tr>
                    </table>
                </div>
            </td>';

        // Sconto ore
        echo '
            <td style="border-right:1px solid #aaa;">';
        if ($user_idanagrafica == 0 || $show_costi) {
            echo '
                {[ "type": "number", "name": "sconto['.$id.']", "value": "'.$sconto_unitario.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}';
        } else {
            echo '
                <input type="hidden" name="sconto['.$id.']" value="'.Translator::numberToLocale($sconto_unitario).'" />
                <input type="hidden" name="sconto['.$id.']" value="'.Translator::numberToLocale($tipo_sconto).'" />';
        }

        echo '
            </td>';

        // Sconto km
        echo '
            <td style="border-right:1px solid #aaa;">';
        if ($user_idanagrafica == 0 || $show_costi) {
            echo '
                {[ "type": "number", "name": "scontokm['.$id.']", "value": "'.$scontokm_unitario.'", "icon-after": "choice|untprc|'.$tipo_scontokm.'" ]}';
        } else {
            echo '
                <input type="hidden" name="scontokm['.$id.']" value="'.Translator::numberToLocale($scontokm_unitario).'" />
                <input type="hidden" name="tipo_scontokm['.$id.']" value="'.Translator::numberToLocale($tipo_scontokm).'" />';
        }

        echo '
                <input type="hidden" name="prezzo_ore_consuntivo['.$id.']" value="'.Translator::numberToLocale($costo_ore_consuntivo).'" />
                <input type="hidden" name="prezzo_km_consuntivo['.$id.']" value="'.Translator::numberToLocale($costo_km_consuntivo).'" />
                <input type="hidden" name="prezzo_ore_consuntivotecnico['.$id.']" value="'.Translator::numberToLocale($costo_ore_consuntivo_tecnico).'" />
                <input type="hidden" name="prezzo_km_consuntivotecnico['.$id.']" value="'.Translator::numberToLocale($costo_km_consuntivo_tecnico).'" />
            </td>';

        // Pulsante aggiunta nuova sessione
        echo '
            <td>
                <a class="btn btn-danger" id="delbtn_'.$id.'" onclick="elimina_sessione(\''.$id.'\', \''.$id_record.'\', \''.$idzona.'\');" title="Elimina riga" class="only_rw"><i class="fa fa-trash-o"></i></a>
            </td>
        </tr>';

        // Intestazione tecnico
        if (!isset($rs2[$key + 1]['ragione_sociale']) || $r['ragione_sociale'] != $rs2[$key + 1]['ragione_sociale']) {
            echo '
    </table>
</div>';
        }
    }
} else {
    echo
'<p>'._('Nessun tecnico presente').'.</p>';
}

echo '
<!-- AGGIUNTA TECNICO -->
<div class="row">
    <div class="col-md-offset-6 col-md-3">
        {[ "type": "select", "label": "'._('Aggiungi tecnico').'", "name": "nuovotecnico", "ajax-source": "tecnici" ]}
    </div>

    <div class="col-md-3">
        <br>
        <button type="button" class="btn btn-primary btn-block" onclick="if($(\'#nuovotecnico\').val()){ add_tecnici( \''.$id_record.'\', $(\'#nuovotecnico\').val()); }else{ alert(\'Seleziona un tecnico!\'); }">
            <i class="fa fa-plus"></i> '._('Aggiungi tecnico').'
        </button>
    </div>
</div>';

?>

<script type="text/javascript" charset="utf-8">
    $(document).ready( function(){
        $('.datetimepicker').daterangepicker({
                timePicker: true,
                timePickerIncrement: 5,
                locale: {
                    format: globals.timestampFormat,
                    customRangeLabel: globals.translations.custom,
                    applyLabel: globals.translations.apply,
                    cancelLabel: globals.translations.cancel,
                    fromLabel: globals.translations.from,
                    toLabel: globals.translations.to,
                },
                timePicker24Hour: true,
                applyClass: 'btn btn-success btn-sm',
                cancelClass: 'btn btn-danger btn-sm',
            }
        ).on('change', function(){
            id = $(this).attr('id').split('_');
            idriga = id[1];

            orario = $('#orario_'+idriga).val();
            o = orario.split(' - ');

            start = o[0];
            end = o[1];

            calcola_ore(idriga, start, end);
        });
    });
</script>

<script type="text/javascript" src="<?php echo $rootdir ?>/lib/init.js"></script>
