<?php

include_once __DIR__.'/../../../core.php';

include_once Modules::filepath('Articoli', 'modutil.php');

// Pianificazione intervento
switch (filter('op')) {
    case 'add-pianifica':

        $data_richiesta = filter('data_richiesta');
        $query = 'INSERT INTO `co_contratti_promemoria` ( `idcontratto`, `data_richiesta` ) VALUES ('.prepare($id_record).', '.prepare($data_richiesta).')';

        if ($dbo->query($query)) {
        } else {
            flash()->error(tr("Errore durante l'aggiunta del promemoria!"));
        }
    break;

    case 'edit-pianifica':

        $idcontratto_riga = filter('idcontratto_riga');

        $data_richiesta = filter('data_richiesta');

        $idtipointervento = filter('idtipointervento');
        $richiesta = filter('richiesta');
        $idsede = filter('idsede_c');
        $idimpianti = implode(',', post('idimpianti'));

        $query = 'UPDATE co_contratti_promemoria SET idtipointervento='.prepare($idtipointervento).', data_richiesta='.prepare($data_richiesta).', richiesta='.prepare($richiesta).',  idsede='.prepare($idsede).', idimpianti='.prepare($idimpianti).'   WHERE id = '.prepare($idcontratto_riga);

        if (isset($id_record)) {
            if ($dbo->query($query)) {
                flash()->info(tr('Promemoria inserito!'));
            } else {
                flash()->error(tr('Errore durante la modifica del promemoria!'));
            }
        }

        redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);

        break;

    // Eliminazione pianificazione
    case 'depianifica':

        $id = filter('id');

        $dbo->query('DELETE FROM `co_contratti_promemoria` WHERE id='.prepare($id));
        $dbo->query('DELETE FROM `co_righe_contratti_materiali` WHERE id_riga_contratto='.prepare($id));
        $dbo->query('DELETE FROM `co_righe_contratti_articoli` WHERE id_riga_contratto='.prepare($id));

        flash()->info(tr('Pianificazione eliminata!'));

        redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);

        break;

    //Eliminazione tutti i promemoria di questo contratto con non hanno l'intervento associato
    case 'delete-promemoria':

        $dbo->query('DELETE FROM `co_contratti_promemoria` WHERE idcontratto = '.$id_record.' AND idintervento IS NULL');
        $dbo->query('DELETE FROM `co_righe_contratti_materiali` WHERE id_riga_contratto IN (SELECT id FROM `co_contratti_promemoria` WHERE idcontratto = '.$id_record.' AND idintervento IS NULL ) ');
        $dbo->query('DELETE FROM `co_righe_contratti_articoli` WHERE id_riga_contratto IN (SELECT id FROM `co_contratti_promemoria` WHERE idcontratto = '.$id_record.' AND idintervento IS NULL ) ');

        flash()->error(tr('Tutti i promemoria non associati sono stati eliminati!'));

        redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);

        break;

      //pianificazione ciclica
     case 'pianificazione':

            $idcontratto_riga = filter('idcontratto_riga');
            $intervallo = filter('intervallo');
            $parti_da_oggi = post('parti_da_oggi');

            if (!empty($idcontratto_riga) && !empty($intervallo)) {
                $qp = 'SELECT *, (SELECT idanagrafica FROM co_contratti WHERE id = '.$id_record.' ) AS idanagrafica, (SELECT data_conclusione FROM co_contratti WHERE id = '.$id_record.' ) AS data_conclusione, '.
                '(SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_contratti_promemoria.idtipointervento) AS tipointervento FROM co_contratti_promemoria '.
                'WHERE co_contratti_promemoria.id = '.$idcontratto_riga;
                $rsp = $dbo->fetchArray($qp);

                $idtipointervento = $rsp[0]['idtipointervento'];
                $idsede = $rsp[0]['idsede'];
                $richiesta = $rsp[0]['richiesta'];

                $data_richiesta = $rsp[0]['data_richiesta'];
                $idimpianti = $rsp[0]['idimpianti'];

                //mi serve per la pianificazione dei promemoria
                $data_conclusione = $rsp[0]['data_conclusione'];

                //mi serve per la pianificazione interventi
                $idanagrafica = $rsp[0]['idanagrafica'];

                //se voglio pianificare anche le date precedenti ad oggi (parto da questo promemoria)
                if ($parti_da_oggi) {
                    //oggi
                    $min_date = date('Y-m-d');
                } else {
                    $min_date = date('Y-m-d', strtotime($data_richiesta));
                }

                //inizio controllo data_conclusione, data valida e maggiore della $min_date
                if ((date('Y', strtotime($data_conclusione)) > 1970) && (date('Y-m-d', strtotime($min_date)) < date('Y-m-d', strtotime($data_conclusione)))) {
                    //Ciclo partendo dalla data_richiesta fino all data conclusione del contratto
                    while (date('Y-m-d', strtotime($data_richiesta)) < date('Y-m-d', strtotime($data_conclusione))) {
                        //calcolo nuova data richiesta
                        $data_richiesta = date('Y-m-d', strtotime($data_richiesta.' + '.intval($intervallo).' days'));

                        //controllo nuova data richiesta --> solo  date maggiori o uguali di [oggi o data richiesta iniziale] ma che non superano la data di fine del contratto
                        if ((date('Y-m-d', strtotime($data_richiesta)) >= $min_date) && (date('Y-m-d', strtotime($data_richiesta)) <= date('Y-m-d', strtotime($data_conclusione)))) {
                            //Controllo che non esista già un promemoria idcontratto, idtipointervento e data_richiesta.
                            if (count($dbo->fetchArray("SELECT id FROM co_contratti_promemoria WHERE data_richiesta = '".$data_richiesta."' AND idtipointervento = '".$idtipointervento."' AND idcontratto = '".$id_record."' ")) == 0) {
                                //inserisco il nuovo promemoria
                                $query = 'INSERT INTO `co_contratti_promemoria`(`idcontratto`, `idtipointervento`, `data_richiesta`, `richiesta`, `idsede`, `idimpianti` ) VALUES('.prepare($id_record).', '.prepare($idtipointervento).', '.prepare($data_richiesta).', '.prepare($richiesta).', '.prepare($idsede).', '.prepare($idimpianti).')';

                                if ($dbo->query($query)) {
                                    $idriga = $dbo->lastInsertedID();

                                    //copio anche righe materiali nel nuovo promemoria
                                    $dbo->query('INSERT INTO co_righe_contratti_materiali (descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,	desc_iva,iva,id_riga_contratto,sconto,sconto_unitario,tipo_sconto) SELECT descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,	desc_iva,iva,'.$idriga.',sconto,sconto_unitario,tipo_sconto FROM co_righe_contratti_materiali WHERE id_riga_contratto = '.$idcontratto_riga.'  ');

                                    //copio righe articoli nel nuovo promemoria
                                    $dbo->query('INSERT INTO co_righe_contratti_articoli (idarticolo, id_riga_contratto,descrizione,prezzo_acquisto,prezzo_vendita,sconto,	sconto_unitario,	tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto) SELECT idarticolo, '.$idriga.',descrizione,prezzo_acquisto,prezzo_vendita,sconto,sconto_unitario,tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto FROM co_righe_contratti_articoli WHERE id_riga_contratto = '.$idcontratto_riga.'  ');

                                    flash()->info(tr('Promemoria intervento pianificato!'));

                                    //pianificare anche l' intervento?
                                    if (post('pianifica_intervento')) {
                                        /*$orario_inizio = post('orario_inizio');
                                        $orario_fine = post('orario_fine');*/

                                        //$idanagrafica = 2;

                                        //intervento sempre nello stato "In programmazione"
                                        $idstatointervento = 'WIP';

                                        //calcolo codice intervento
                                        $formato = setting('Formato codice intervento');
                                        $template = str_replace('#', '%', $formato);

                                        $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice=(SELECT MAX(CAST(codice AS SIGNED)) FROM in_interventi) AND codice LIKE '.prepare($template).' ORDER BY codice DESC LIMIT 0,1');
                                        if (!empty($rs[0]['codice'])) {
                                            $codice = Util\Generator::generate($formato, $rs[0]['codice']);
                                        }

                                        if (empty($codice)) {
                                            $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice LIKE '.prepare($template).' ORDER BY codice DESC LIMIT 0,1');
                                            $codice = Util\Generator::generate($formato, $rs[0]['codice']);
                                        }

                                        // Creo intervento
                                        $dbo->insert('in_interventi', [
                                                    'idanagrafica' => $idanagrafica,
                                                    'idclientefinale' => post('idclientefinale') ?: 0,
                                                    'idstatointervento' => $idstatointervento,
                                                    'idtipointervento' => $idtipointervento,
                                                    'idsede' => $idsede ?: 0,
                                                    'idautomezzo' => $idautomezzo ?: 0,

                                                    'codice' => $codice,
                                                    'data_richiesta' => $data_richiesta,
                                                    'richiesta' => $richiesta,
                                                ]);

                                        $idintervento = $dbo->lastInsertedID();

                                        $idtecnici = post('idtecnico');

                                        //aggiungo i tecnici
                                        foreach ($idtecnici as $idtecnico) {
                                            add_tecnico($idintervento, $idtecnico, $data_richiesta.' '.post('orario_inizio'), $data_richiesta.' '.post('orario_fine'), $id_record);
                                        }

                                        //collego l'intervento ai promemoria
                                        $dbo->query('UPDATE co_contratti_promemoria SET idintervento='.prepare($idintervento).' WHERE id='.prepare($idriga));

                                        //copio le righe dal promemoria all'intervento
                                        $dbo->query('INSERT INTO in_righe_interventi (descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,idintervento,sconto,sconto_unitario,tipo_sconto) SELECT descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,'.$idintervento.',sconto,sconto_unitario,tipo_sconto FROM co_righe_contratti_materiali WHERE id_riga_contratto = '.$idcontratto_riga.'  ');

                                        //copio  gli articoli dal promemoria all'intervento
                                        $dbo->query('INSERT INTO mg_articoli_interventi (idarticolo, idintervento,descrizione,prezzo_acquisto,prezzo_vendita,sconto,	sconto_unitario,	tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto) SELECT idarticolo, '.$idintervento.',descrizione,prezzo_acquisto,prezzo_vendita,sconto,sconto_unitario,tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto FROM co_righe_contratti_articoli WHERE id_riga_contratto = '.$idcontratto_riga.'  ');

                                        //copio  gli allegati dal promemoria all'intervento
                                        $dbo->query('INSERT INTO zz_files (nome,filename,original,category,id_module,id_record) SELECT t.nome, t.filename, t.original, t.category,  '.Modules::get('Interventi')['id'].', '.$idintervento.' FROM zz_files t WHERE t.id_record = '.$idcontratto_riga.' AND t.id_plugin = '.$id_plugin.'');

                                        // Decremento la quantità per ogni articolo copiato
                                        $rs_articoli = $dbo->fetchArray('SELECT * FROM mg_articoli_interventi WHERE idintervento = '.$idintervento.' ');
                                        foreach ($rs_articoli as $rs_articolo) {
                                            add_movimento_magazzino($rs_articolo['idarticolo'], -force_decimal($rs_articolo['qta']), ['idautomezzo' => $rs_articolo['idautomezzo'], 'idintervento' => $idintervento]);
                                        }

                                        // Collego gli impianti del promemoria all' intervento appena inserito
                                        if (!empty($idimpianti)) {
                                            $rs_idimpianti = explode(',', $idimpianti);
                                            foreach ($rs_idimpianti as $idimpianto) {
                                                $dbo->query('INSERT INTO my_impianti_interventi (idintervento, idimpianto) VALUES ('.$idintervento.', '.prepare($idimpianto).' )');
                                            }
                                        }

                                        // flash()->info(tr('Intervento '.$codice.' pianificato correttamente.'));

                                        flash()->info(tr('Interventi pianificati correttamente.'));
                                    }
                                    //fine if pianificazione intervento
                                } else {
                                    flash()->error(tr('Errore durante esecuzione query di pianificazione.  #'.$idcontratto_riga));
                                }
                            } else {
                                flash()->warning(tr('Esiste già un promemoria pianificato per il '.Translator::dateToLocale($data_richiesta).'.'));
                            }
                        }
                        //fine controllo nuova data richiesta
                    }
                    //fine ciclo while
                } else {
                    flash()->error(tr('Nessuna data di conclusione del contratto oppure quest\'ultima è già trascorsa, impossibile pianificare nuovi promemoria.'.$qp));
                }
                //fine controllo data_conclusione
            } else {
                flash()->error(tr('Errore durante la pianificazione.  #'.$idcontratto_riga));
            }

            redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);
    break;
}

// Righe già inserite
$qp = 'SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_contratti_promemoria.idtipointervento) AS tipointervento FROM co_contratti_promemoria WHERE idcontratto='.prepare($id_record).' ORDER BY data_richiesta ASC';
$rsp = $dbo->fetchArray($qp);

$pianificabile = $dbo->fetchNum('SELECT id FROM co_staticontratti WHERE pianificabile = 1 AND descrizione = '.prepare($record['stato']));

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><span class="tip" title="'.tr('I promemoria  verranno visualizzati sulla \'Dashboard\' e serviranno per semplificare la pianificazione del giorno dell\'intervento, ad esempio nel caso di interventi con cadenza mensile.').'"" >'.tr('Pianificazione interventi').' <i class="fa fa-question-circle-o"></i></span> </h3>
    </div>
    <div class="box-body">
        <p>'.tr('Puoi <b>pianificare dei "promemoria" o direttamente gli interventi</b> da effettuare entro determinate scadenze. Per poter pianificare i promemoria il contratto deve essere <b>attivo</b> e la <b>data di conclusione</b> definita').'.</p>';

// Nessun intervento pianificato
if (count($rsp) != 0) {
    echo '<br><h5>'.tr('Lista promemoria ed eventuali interventi associati').':</h5>';
    echo '
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
    for ($i = 0; $i < sizeof($rsp); ++$i) {
        //  Sede
        if ($rsp[$i]['idsede'] == '-1') {
            echo '- '.('Nessuna').' -';
        } elseif (empty($rsp[$i]['idsede'])) {
            $info_sede = tr('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($rsp[$i]['idsede']));

            $info_sede = $rsp2[0]['descrizione'];
        }

        // Intervento svolto
        if (!empty($rsp[$i]['idintervento'])) {
            $rsp2 = $dbo->fetchArray('SELECT id, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data FROM in_interventi WHERE id='.prepare($rsp[$i]['idintervento']));

            $info_intervento = Modules::link('Interventi', $rsp2[0]['id'], tr('Intervento num. _NUM_ del _DATE_', [
                '_NUM_' => $rsp2[0]['codice'],
                '_DATE_' => Translator::dateToLocale($rsp2[0]['data']),
            ]));

            $disabled = 'disabled';
        } else {
            $info_intervento = '- '.('Nessuno').' -';
            $disabled = '';
        }

        //data_conclusione contratto
        if (date('Y', strtotime($record['data_conclusione'])) < 1971) {
            $record['data_conclusione'] = '';
        }

        //info impianti
        $info_impianti = '';
        if (!empty($rsp[$i]['idimpianti'])) {
            $rsp3 = $dbo->fetchArray('SELECT id, matricola, nome FROM my_impianti WHERE id IN ('.($rsp[$i]['idimpianti']).')');
            if (!empty($rsp3)) {
                for ($a = 0; $a < count($rsp3); ++$a) {
                    $info_impianti .= Modules::link('MyImpianti', $rsp3[$a]['id'], tr('_NOME_ (_MATRICOLA_)', [
                        '_NOME_' => $rsp3[$a]['nome'],
                        '_MATRICOLA_' => $rsp3[$a]['matricola'],
                    ])).'<br>';
                }
            }
        }

        //info materiali/articoli
        $rsp4 = $dbo->fetchArray('SELECT id, descrizione,qta,um,prezzo_vendita, \'\' AS idarticolo FROM co_righe_contratti_materiali WHERE id_riga_contratto = '.prepare($rsp[$i]['id']).'
		UNION SELECT id, descrizione,qta,um,prezzo_vendita, idarticolo FROM co_righe_contratti_articoli WHERE id_riga_contratto = '.prepare($rsp[$i]['id']));

        $info_materiali = '';
        if (!empty($rsp4)) {
            for ($b = 0; $b < count($rsp4); ++$b) {
                $info_materiali .= tr(' _QTA_ _UM_ x _DESC_', [
                    '_DESC_' => ((!empty($rsp4[$b]['idarticolo'])) ? Modules::link('Articoli', $rsp4[$b]['idarticolo'], $rsp4[$b]['descrizione']) : $rsp4[$b]['descrizione']),
                    '_QTA_' => Translator::numberToLocale($rsp4[$b]['qta']),
                    '_UM_' => $rsp4[$b]['um'],
                    '_PREZZO_' => $rsp4[$b]['prezzo_vendita'],
                ]).'<br>';
            }
        }

        //info allegati
        $rsp5 = $dbo->fetchArray('SELECT nome, original  FROM zz_files WHERE id_record = '.prepare($rsp[$i]['id']).' AND id_plugin = '.$id_plugin);

        $info_allegati = '';
        if (!empty($rsp5)) {
            for ($b = 0; $b < count($rsp5); ++$b) {
                $info_allegati .= tr(' _NOME_ (_ORIGINAL_)', [
                    '_ORIGINAL_' => $rsp5[$b]['original'],
                    '_NOME_' => $rsp5[$b]['nome'],
                ]).'<br>';
            }
        }

        echo '
                <tr>
                    <td>'.Translator::dateToLocale($rsp[$i]['data_richiesta']).'<!--br><small>'.Translator::dateToLocale($record['data_conclusione']).'</small--></td>
                    <td>'.$rsp[$i]['tipointervento'].'</td>
                    <td>'.nl2br($rsp[$i]['richiesta']).'</td>
                    <td>'.$info_intervento.'</td>
                    <td>'.$info_sede.'</td>
					 <td>'.$info_impianti.'</td>
					<td>'.$info_materiali.'</td>
					<td>'.$info_allegati.'</td>
                    <td align="right">';

        echo '
				<button type="button" class="btn btn-warning btn-sm" title="Pianifica..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica\', \''.$rootdir.'/modules/contratti/plugins/addpianficazione.php?id_module='.Modules::get('Contratti')['id'].'&id_plugin='.Plugins::get('Pianificazione interventi')['id'].'&ref=interventi_contratti&id_record='.$id_record.'&idcontratto_riga='.$rsp[$i]['id'].'\');"'.((!empty($pianificabile) && strtotime($record['data_conclusione'])) ? '' : ' disabled').'><i class="fa fa-clock-o"></i></button>';

        echo '
					<button type="button"  '.$disabled.'  class="btn btn-primary btn-sm '.$disabled.' " title="Pianifica intervento ora..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica intervento\', \''.$rootdir.'/add.php?id_module='.Modules::get('Interventi')['id'].'&ref=interventi_contratti&idcontratto='.$id_record.'&idcontratto_riga='.$rsp[$i]['id'].'\');"'.(!empty($pianificabile) ? '' : ' disabled').'><i class="fa fa-calendar"></i></button>';

        echo '
					<button type="button"  '.$disabled.' title="Elimina promemoria..." class="btn btn-danger btn-sm ask '.$disabled.' " data-op="depianifica" data-id="'.$rsp[$i]['id'].'">
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

    if (count($rsp) > 0) {
        echo '	<button type="button"  title="Elimina tutti i promemoria per questo contratto che non sono associati ad intervento." class="btn btn-danger ask tip" data-op="delete-promemoria" >
						<i class="fa fa-trash"></i> '.tr('Elimina promemoria').'
					</button>';
    }

    echo '</div>';
}

    echo '	<button type="button"  title="Aggiungi un nuovo promemoria da pianificare." data-toggle="tooltip" class="btn btn-primary"  id="add_promemoria">
						<i class="fa fa-plus"></i> '.tr('Nuovo promemoria').'
					</button>';

/*
    Nuovo intervento
*/
/*echo '
        <br><h5>'.tr('Pianifica un nuovo promemoria per un intervento').':</h5>
        <form action="" method="post">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="pianifica">

            <table class="table table-condensed table-striped table-hover">
                <thead>
                    <tr>
                        <th>'.tr('Entro il').'*</th>
                        <th>'.tr('Tipo intervento').'*</th>
                        <th>'.tr('Descrizione').'</th>
                        <th>'.tr('Sede').'</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {[ "type": "date", "placeholder": "'.tr('Entro il').'", "name": "data_richiesta", "required": 1 ]}
                        </td>
                        <td>
                            {[ "type": "select", "placeholder": "'.tr('Tipo intervento').'", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "'.$rsp[0]['idtipointervento'].'" ]}
                        </td>
                        <td>
                            {[ "type": "textarea", "placeholder": "'.tr('Descrizione').'", "name": "richiesta" ]}
                        </td>
                        <td>
                            {[ "type": "select", "placeholder": "'.tr('Sede').'", "name": "idsede_c", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$record['idanagrafica'].'", "value": "0" ]}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="pull-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
            </div>
            <div class="clearfix"></div>
        </form>';*/

echo '
    </div>
</div>';

?>

<script type="text/javascript">

	$( "#add_promemoria" ).click(function() {

		swal({
			title: '<?php echo tr('Aggiungere un nuovo promemoria?'); ?>',
			type: "info",
			showCancelButton: true,
			confirmButtonText: '<?php echo tr('Aggiungi'); ?>',
		   confirmButtonClass: 'btn btn-lg btn-success',
		}).then(
			function (result) {
				prev_html = $("#add_promemoria").html();
				$("#add_promemoria").html("<i class='fa fa-spinner fa-pulse  fa-fw'></i> <?php echo tr('Attendere...'); ?>");
				$("#add_promemoria").prop('disabled', true);

				$.post( "<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get('Contratti')['id']; ?>&id_record=<?php echo $id_record; ?>", { backto: "record-edit", op: "add-pianifica", data_richiesta: '<?php echo date('Y-m-d'); ?>' })
				  .done(function( data ) {
					launch_modal('Nuovo promemoria', '<?php echo $rootdir; ?>/modules/contratti/plugins/addpianficazione.php?id_module=<?php echo Modules::get('Contratti')['id']; ?>&id_plugin=<?php echo Plugins::get('Pianificazione interventi')['id']; ?>&ref=interventi_contratti&id_record=<?php echo $id_record; ?>', 1, '#bs-popup');

					$("#add_promemoria").html(prev_html);
					$("#add_promemoria").prop('disabled', false);

				  });
			},
			function (dismiss) {}
		);


	});
</script>
