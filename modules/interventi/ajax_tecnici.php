<?php

if (file_exists(__DIR__.'/../../../core.php')) {
    include_once __DIR__.'/../../../core.php';
} else {
    include_once __DIR__.'/../../core.php';
}

include_once Modules::filepath('Interventi', 'modutil.php');

switch (get('op')) {
    // OPERAZIONI PER AGGIUNTA NUOVA SESSIONE DI LAVORO
    case 'add_sessione':
        $idtecnico = get('idtecnico');

        // Verifico se l'intervento è collegato ad un contratto
        $rs = $dbo->fetchArray('SELECT idcontratto FROM co_promemoria WHERE idintervento='.prepare($id_record));
        $idcontratto = $rs[0]['idcontratto'];

        $ore = 1;

        $inizio = date('Y-m-d H:\0\0');
        $fine = date_modify(date_create(date('Y-m-d H:\0\0')), '+'.$ore.' hours')->format('Y-m-d H:\0\0');

        add_tecnico($id_record, $idtecnico, $inizio, $fine, $idcontratto);
        break;

    // RIMOZIONE SESSIONE DI LAVORO
    case 'del_sessione':
        $id = get('id');

        $tecnico = $dbo->fetchOne('SELECT an_anagrafiche.email FROM an_anagrafiche INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare($id));

        $dbo->query('DELETE FROM in_interventi_tecnici WHERE id='.prepare($id));

        // Notifica nuovo intervento al tecnico
        if (!empty($tecnico['email'])) {
            $n = new Notifications\EmailNotification();

            $n->setTemplate('Notifica rimozione intervento', $id_record);
            $n->setReceivers($tecnico['email']);

            $n->send();
        }
        break;
}

$show_costi = true;

// Limitazione delle azioni dei tecnici
if ($user['gruppo'] == 'Tecnici') {
    $show_costi = !empty($user['idanagrafica']) && setting('Mostra i prezzi al tecnico');
}

// RECUPERO IL TIPO DI INTERVENTO
$rss = $dbo->fetchArray('SELECT idtipointervento, idstatointervento FROM in_interventi WHERE id='.prepare($id_record));
$idtipointervento = $rss[0]['idtipointervento'];
$idstatointervento = $rss[0]['idstatointervento'];

$rss = $dbo->fetchArray('SELECT completato AS flag_completato FROM in_statiintervento WHERE idstatointervento='.prepare($idstatointervento));
$flag_completato = $rss[0]['flag_completato'];

$query = 'SELECT * FROM an_anagrafiche JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE idintervento='.prepare($id_record)." AND idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica = (SELECT id FROM an_tipianagrafiche WHERE descrizione = 'Tecnico')) ORDER BY ragione_sociale ASC, in_interventi_tecnici.orario_inizio ASC, in_interventi_tecnici.id ASC";
$rs2 = $dbo->fetchArray($query);
$prev_tecnico = '';

if ($flag_completato) {
    $readonly = 'readonly';
} else {
    $readonly = '';
}

if (!empty($rs2)) {
    foreach ($rs2 as $key => $r) {
        $idtecnico = $r['idanagrafica'];

        // Intestazione tecnico
        if ($prev_tecnico != $r['ragione_sociale']) {
            $prev_tecnico = $r['ragione_sociale'];

            echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th><i class="fa fa-user"></i> '.$r['ragione_sociale'].' '.(($r['deleted']) ? '<small class="text-danger"><em>('.tr('Eliminato').')</em></small>' : '').'</th>
            <th>'.tr('Orario inizio').'</th>
            <th>'.tr('Orario fine').'</th>
            <th>'.tr('Ore').'</th>
            <th>'.tr('Km').'</th>
            <th>'.tr('Sconto ore').'</th>
            <th>'.tr('Sconto km').'</th>
            <th></th>
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

        $orario_inizio = $r['orario_inizio'];
        $orario_fine = $r['orario_fine'];

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
        <input type="hidden" name="prezzo_dirittochiamata_tecnico['.$id.']" value="'.$costo_dirittochiamata_tecnico.'" />

        <tr>';

        // Elenco tipologie di interventi
        echo '
            <td class="tecn_'.$r['idtecnico'].'" style="min-width:200px;">
                {[ "type": "select", "name": "idtipointerventot['.$id.']", "value": "'.$r['idtipointervento'].'", "values": "query=SELECT idtipointervento AS id, descrizione, IFNULL((SELECT costo_ore FROM in_tariffe WHERE idtipointervento=in_tipiintervento.idtipointervento AND idtecnico='.prepare($r['idtecnico']).'), 0) AS costo_orario FROM in_tipiintervento ORDER BY descrizione", "extra": "'.$readonly.'" ]}
            </td>';

        // Orario di inizio
        echo '
            <td>
                {[ "type": "timestamp", "name": "orario_inizio['.$id.']", "id": "inizio_'.$id.'", "value": "'.$orario_inizio.'", "class": "orari min-width", "extra": "'.$readonly.'" ]}
            </td>';

        // Orario di fine
        echo '
        <td>
            {[ "type": "timestamp", "name": "orario_fine['.$id.']", "id": "fine_'.$id.'", "value": "'.$orario_fine.'", "class": "orari min-width", "min-date": "'.$orario_inizio.'", "extra": "'.$readonly.'" ]}
        </td>';

        // ORE
        echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "ore['.$id.']", "value": "'.$ore.'", "disabled": 1, "class": "small-width" ]}

                <div class="extra hide">
                    <table class="table table-condensed table-bordered">
                    <tr><th class="text-danger">'.tr('Costo').':</th><td align="right">'.Translator::numberToLocale($costo_ore_consuntivo_tecnico)."<small class='help-block'>".Translator::numberToLocale($costo_ore_unitario_tecnico).'x'.Translator::numberToLocale($ore).'<br>+'.Translator::numberToLocale($costo_dirittochiamata_tecnico).'</small></td></tr>
                    <tr><th>'.tr('Addebito').':</th><td align="right">'.Translator::numberToLocale($costo_ore_consuntivo).'<small class="help-block">'.Translator::numberToLocale($costo_ore_unitario).'x'.Translator::numberToLocale($ore).'<br>+'.Translator::numberToLocale($costo_dirittochiamata).'</small></td></tr>
                    <tr><th>'.tr('Scontato').':</th><td align="right">'.Translator::numberToLocale($costo_ore_consuntivo - $sconto).'</td></tr>
                    </table>
                </div>
            </td>';

        // KM
        echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "km['.$id.']", "value": "'.$km.'", "class": "small-width", "extra": "'.$readonly.'" ]}

                <div class="extra hide">
                    <table class="table table-condensed table-bordered">
                    <tr>
                        <th class="text-danger">'.tr('Costo').':</th>
                        <td align="right">
                            '.Translator::numberToLocale($costo_km_consuntivo_tecnico).'
                            <small class="help-block">
                                '.Translator::numberToLocale($costo_km_unitario_tecnico).'x'.Translator::numberToLocale($km).'
                            </small><br>
                        </td>
                    </tr>
                    <tr>
                        <th>'.tr('Addebito').':</th>
                        <td align="right">
                            '.Translator::numberToLocale($costo_km_consuntivo).'
                            <small class="help-block">
                                '.Translator::numberToLocale($costo_km_unitario).'x'.Translator::numberToLocale($km).'
                            </small><br>
                        </td>
                    </tr>
                    <tr>
                        <th>'.tr('Scontato').':</th>
                        <td align="right">'.Translator::numberToLocale($costo_km_consuntivo - $scontokm).'</td>
                    </tr>
                    </table>
                </div>
            </td>';

        // Sconto ore
        echo '
            <td style="border-right:1px solid #aaa;">';
        if ($show_costi) {
            echo '
                {[ "type": "number", "name": "sconto['.$id.']", "value": "'.$sconto_unitario.'", "icon-after": "choice|untprc|'.$tipo_sconto.'|'.$readonly.'", "class": "small-width", "extra": "'.$readonly.'" ]}';
        } else {
            echo '
                <input type="hidden" name="sconto['.$id.']" value="'.Translator::numberToLocale($sconto_unitario).'" />
                <input type="hidden" name="tipo_sconto['.$id.']" value="'.Translator::numberToLocale($tipo_sconto).'" />';
        }

        echo '
            </td>';

        // Sconto km
        echo '
            <td style="border-right:1px solid #aaa;">';
        if ($show_costi) {
            echo '
                {[ "type": "number", "name": "scontokm['.$id.']", "value": "'.$scontokm_unitario.'", "icon-after": "choice|untprc|'.$tipo_scontokm.'|'.$readonly.'", "class": "small-width", "extra": "'.$readonly.'" ]}';
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

        // Pulsante eliminazione sessione
        echo '
            <td>';

        if (!$flag_completato) {
            echo '
                <a class="btn btn-danger" id="delbtn_'.$id.'" onclick="elimina_sessione(\''.$id.'\', \''.$id_record.'\', \''.$idzona.'\');" title="Elimina riga" class="only_rw"><i class="fa fa-trash"></i></a>
                <a class="btn btn-info" onclick="launch_modal(\''.tr('Modifica sessione').'\', \''.$module->fileurl('manage_sessione.php').'?id_module='.$id_module.'&id_sessione='.$id.'\', 1);" title="'.tr('Modifica sessione').'">
                    <i class="fa fa-pencil"></i>
                </a>';
        }

        echo '
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
'<div class=\'alert alert-info\' ><i class=\'fa fa-info-circle\'></i> '.tr('Nessun tecnico assegnato').'.</div>';
}

if (!$flag_completato) {
    echo '
    <!-- AGGIUNTA TECNICO -->
    <div class="row">
        <div class="col-md-offset-6 col-md-3">
            {[ "type": "select", "label": "'.tr('Aggiungi tecnico').'", "name": "nuovotecnico", "ajax-source": "tecnici" ]}
        </div>

        <div class="col-md-3">
            <br>
            <button type="button" class="btn btn-primary btn-block" onclick="if($(\'#nuovotecnico\').val()){ add_tecnici( \''.$id_record.'\', $(\'#nuovotecnico\').val()); }else{ alert(\'Seleziona un tecnico!\'); }">
                <i class="fa fa-plus"></i> '.tr('Aggiungi tecnico').'
            </button>
        </div>
    </div>';
}
?>

<script src="<?php echo $rootdir; ?>/lib/init.js"></script>

<script type="text/javascript">
    $(document).ready(function(){

        <?php
        if (count($rs2) == 0) {
            echo '$(".btn-details").attr("disabled", true);';
            echo '$(".btn-details").addClass("disabled");';
            echo '$("#showall_dettagli").removeClass("hide");';
            echo '$("#dontshowall_dettagli").addClass("hide");';
        } else {
            echo '$(".btn-details").attr("disabled", false);';
            echo '$(".btn-details").removeClass("disabled");';
        }
        ?>

        $('.orari').on("dp.change", function(){
            idriga = $(this).attr('id').split('_')[1];

            start = $('#inizio_' + idriga).val();
            end = $('#fine_' + idriga).val();

            calcola_ore(idriga, start, end);
        });

        $(".orari[id^=inizio]").on("dp.change", function (e) {
            var fine = $(this).closest("tr").find(".orari[id^=fine]").data("DateTimePicker");

            fine.minDate(e.date);

            if(fine.date() < e.date){
                date = moment(e.date).add(1, 'h');

                fine.date(date);
            }
        });
    });
</script>
