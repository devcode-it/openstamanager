<?php

include_once __DIR__.'/../../core.php';

$id_module = Modules::get('Contratti')['id'];
$plugin = Plugins::get($id_plugin);

$contratto = $dbo->fetchOne('SELECT * FROM co_contratti WHERE id = :id', [
    ':id' => $id_record,
]);

$records = $dbo->fetchArray('SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_contratti_promemoria.idtipointervento) AS tipointervento FROM co_contratti_promemoria WHERE idcontratto='.prepare($id_record).' ORDER BY data_richiesta ASC');

// Intervento/promemoria pianificabile
$pianificabile = $dbo->fetchOne('SELECT pianificabile FROM co_staticontratti WHERE id = :id', [
    ':id' => $contratto['idstato'],
])['pianificabile'];

$stati_pianificabili = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione`) AS stati_pianificabili FROM `co_staticontratti` WHERE `pianificabile` = 1')['stati_pianificabili'];

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><span class="tip" title="'.tr('I promemoria  verranno visualizzati sulla \'Dashboard\' e serviranno per semplificare la pianificazione del giorno dell\'intervento, ad esempio nel caso di interventi con cadenza mensile.').'"" >'.tr('Pianificazione interventi').' <i class="fa fa-question-circle-o"></i></span> </h3>
    </div>
    <div class="box-body">
        <p>'.tr('Puoi <b>pianificare dei "promemoria" o direttamente gli interventi</b> da effettuare entro determinate scadenze. Per poter pianificare i promemoria il contratto deve essere in uno dei seguenti stati: <b>'.$stati_pianificabili.'</b> e la <b>data di conclusione</b> definita').'.</p>';

// Nessun intervento pianificato
if (!empty($records)) {
    echo '
        <br>
        <h5>'.tr('Lista promemoria ed eventuali interventi associati').':</h5>
        <table class="table table-condensed table-striped table-hover">
            <thead>
                <tr>
                    <th>'.tr('Data').'</th>
                    <th>'.tr('Tipo intervento').'</th>
                    <th>'.tr('Descrizione').'</th>
                    <th>'.tr('Intervento').'</th>
                    <th>'.tr('Sede').'</th>
					<th>'.tr('Impianti').'</th>
					<th>'.tr('Materiali').'</th>
					<th>'.tr('Allegati').'</th>
                    <th class="text-right" >'.tr('Opzioni').'</th>
                </tr>
            </thead>
            <tbody>';

    // Elenco promemoria
    foreach ($records as $record) {
        // Sede
        if ($record['idsede'] == '-1') {
            echo '- '.('Nessuna').' -';
        } elseif (empty($record['idsede'])) {
            $info_sede = tr('Sede legale');
        } else {
            $sede = $dbo->fetchOne("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($record['idsede']));

            $info_sede = $sede[0]['descrizione'];
        }

        // Intervento svolto
        if (!empty($record['idintervento'])) {
            $sede = $dbo->fetchOne('SELECT id, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data FROM in_interventi WHERE id='.prepare($record['idintervento']));

            $info_intervento = Modules::link('Interventi', $sede[0]['id'], tr('Intervento num. _NUM_ del _DATE_', [
                '_NUM_' => $sede[0]['codice'],
                '_DATE_' => Translator::dateToLocale($sede[0]['data']),
            ]));

            $disabled = 'disabled';
            $title = 'Per eliminare il promemoria, eliminare prima l\'intervento associato.';
        } else {
            $info_intervento = '- '.('Nessuno').' -';
            $disabled = '';
            $title = 'Elimina promemoria...';
        }

        // data_conclusione contratto
        if (date('Y', strtotime($contratto['data_conclusione'])) < 1971) {
            $contratto['data_conclusione'] = '';
        }

        // info impianti
        $info_impianti = '';
        if (!empty($record['idimpianti'])) {
            $impianti = $dbo->fetchArray('SELECT id, matricola, nome FROM my_impianti WHERE id IN ('.($record['idimpianti']).')');

            foreach ($impianti as $impianto) {
                $info_impianti .= Modules::link('MyImpianti', $impianto['id'], tr('_NOME_ (_MATRICOLA_)', [
                    '_NOME_' => $impianto['nome'],
                    '_MATRICOLA_' => $impianto['matricola'],
                ])).'<br>';
            }
        }

        // Info materiali/articoli
        $materiali = $dbo->fetchArray('SELECT id, descrizione,qta,um,prezzo_vendita, \'\' AS idarticolo FROM co_righe_contratti_materiali WHERE id_riga_contratto = '.prepare($record['id']).'
		UNION SELECT id, descrizione,qta,um,prezzo_vendita, idarticolo FROM co_righe_contratti_articoli WHERE id_riga_contratto = '.prepare($record['id']));

        $info_materiali = '';
        foreach ($materiali as $materiale) {
            $info_materiali .= tr(' _QTA_ _UM_ x _DESC_', [
                '_DESC_' => ((!empty($materiale['idarticolo'])) ? Modules::link('Articoli', $materiale['idarticolo'], $materiale['descrizione']) : $materiale['descrizione']),
                '_QTA_' => Translator::numberToLocale($materiale['qta']),
                '_UM_' => $materiale['um'],
                '_PREZZO_' => $materiale['prezzo_vendita'],
            ]).'<br>';
        }

        // Info allegati
        $allegati = $dbo->fetchArray('SELECT nome, original  FROM zz_files WHERE id_record = '.prepare($record['id']).' AND id_plugin = '.$id_plugin);

        $info_allegati = '';
        foreach ($allegati as $allegato) {
            $info_allegati .= tr(' _NOME_ (_ORIGINAL_)', [
                '_ORIGINAL_' => $allegato['original'],
                '_NOME_' => $allegato['nome'],
            ]).'<br>';
        }

        echo '
                <tr>
                    <td>'.Translator::dateToLocale($record['data_richiesta']).'<!--br><small>'.Translator::dateToLocale($contratto['data_conclusione']).'</small--></td>
                    <td>'.$record['tipointervento'].'</td>
                    <td>'.nl2br($record['richiesta']).'</td>
                    <td>'.$info_intervento.'</td>
                    <td>'.$info_sede.'</td>
                    <td>'.$info_impianti.'</td>
					<td>'.$info_materiali.'</td>
					<td>'.$info_allegati.'</td>
                    <td align="right">';

        echo '
                    <button type="button" class="btn btn-warning btn-sm" title="Pianifica..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica\', \''.$plugin->fileurl('pianficazione.php').'?id_module='.$id_module.'&id_plugin='.$plugin['id'].'&id_parent='.$id_record.'&id_record='.$record['id'].'\');"'.((!empty($pianificabile) && !empty($contratto['data_conclusione'])) ? '' : ' disabled').'>
                        <i class="fa fa-clock-o"></i>
                    </button>';

        echo '
					<button type="button" '.$disabled.' class="btn btn-primary btn-sm '.$disabled.' " title="Pianifica intervento ora..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica intervento\', \''.$rootdir.'/add.php?id_module='.Modules::get('Interventi')['id'].'&ref=interventi_contratti&idcontratto='.$id_record.'&idcontratto_riga='.$record['id'].'\');"'.(!empty($pianificabile) ? '' : ' disabled').'><i class="fa fa-calendar"></i></button>';

        echo '
					<button type="button" '.$disabled.' title="'.$title.'" class="btn btn-danger btn-sm ask '.$disabled.' " data-op="delete-promemoria" data-id="'.$record['id'].'">
						<i class="fa fa-trash"></i>
					</button>';

        echo '
                    </td>
                </tr>';
    }
    echo '
            </tbody>
        </table>';

    echo '<br><div class="pull-right">';

    if (!empty($records)) {
        echo '
        <button type="button" title="Elimina tutti i promemoria non associati ad intervento" class="btn btn-danger ask tip" data-op="delete-non-associati" data-id_plugin="'.$id_plugin.'" data-backto="record-edit">
            <i class="fa fa-trash"></i> '.tr('Elimina promemoria').'
        </button>';
    }

    echo '</div>';
}

    echo '
        <button type="button" title="Aggiungi un nuovo promemoria da pianificare." data-toggle="tooltip" class="btn btn-primary" id="add_promemoria">
            <i class="fa fa-plus"></i> '.tr('Nuovo promemoria').'
        </button>';

echo '
    </div>
</div>';

$options = $dbo->fetchArray('SELECT idtipointervento, descrizione FROM `in_tipiintervento`');

echo '
<script type="text/javascript">

	function askTipoIntervento () {
        swal({
            title: "'.tr('Aggiungere un nuovo promemoria?').'",
            type: "info",
            showCancelButton: true,
            confirmButtonText: "'.tr('Aggiungi').'",
            confirmButtonClass: "btn btn-lg btn-success",
            input: "select",
            inputOptions: {';

foreach ($options as $option) {
    echo '
                "'.$option['idtipointervento'].'": "'.$option['descrizione'].'", ';
}

echo '
            },
            inputPlaceholder: "'.tr('Tipo intervento').'",
            inputValidator: function(value) {
                return new Promise((resolve) => {
                    if (value === "") {
                        alert ("Seleziona un tipo intervento");
                        $(".swal2-select").attr("disabled", false);
                        $(".swal2-confirm").attr("disabled", false);
                        $(".swal2-cancel").attr("disabled", false);
                    } else {
                        resolve();
                    }
                })
            }
        }).then(
            function (result) {
                prev_html = $("#add_promemoria").html();
                $("#add_promemoria").html("<i class=\'fa fa-spinner fa-pulse  fa-fw\'></i> '.tr('Attendere...').'");
                $("#add_promemoria").prop("disabled", true);

                $.post(globals.rootdir + "/actions.php?id_plugin='.$plugin['id'].'&id_parent='.$id_record.'", {
                    op: "add-promemoria",
                    data_richiesta: "'.date('Y-m-d').'",
                    idtipointervento: $(".swal2-select").val()
                }).done(function(data) {
                    launch_modal("Nuovo promemoria", globals.rootdir + "/plugins/'.$plugin['directory'].'/pianficazione.php?id_plugin='.$plugin['id'].'&id_parent='.$id_record.'&id_record=" + data + "&add=1");

                    $("#add_promemoria").html(prev_html);
                    $("#add_promemoria").prop("disabled", false);
                });
            },
            function (dismiss) {}
        );
    }

	$("#add_promemoria").click(function() {
		askTipoIntervento();
	});
</script>';
