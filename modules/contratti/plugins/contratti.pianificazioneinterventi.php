<?php

include_once __DIR__.'/../../../core.php';

// Pianificazione intervento
switch (filter('op')) {
    case 'pianifica':
        $data_richiesta = filter('data_richiesta');
        $idtipointervento = filter('idtipointervento');
        $richiesta = filter('richiesta');
        $idsede = filter('idsede_c');

        $query = 'INSERT INTO `co_righe_contratti`(`idcontratto`, `idtipointervento`, `data_richiesta`, `richiesta`, `idsede`) VALUES('.prepare($id_record).', '.prepare($idtipointervento).', '.prepare($data_richiesta).', '.prepare($richiesta).', '.prepare($idsede).')';

        if (isset($id_record)) {
            if ($dbo->query($query)) {
                $_SESSION['infos'][] = tr('Intervento pianificato!');
            } else {
                $_SESSION['errors'][] = tr("Errore durante l'aggiunta dell'intervento!");
            }
        }
        break;

    // Eliminazione pianificazione
    case 'depianifica':
        $id = filter('id');

        $dbo->query('DELETE FROM `co_righe_contratti` WHERE id='.prepare($id));
        $_SESSION['infos'][] = tr('Pianificazione eliminata!');

        redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);

        break;

    //Eliminazione tutti i promemoria di questo contratto con non hanno l'intervento associato
    case 'delete-promemoria':

        $dbo->query('DELETE FROM `co_righe_contratti` WHERE idcontratto = '.$id_record.' AND idintervento IS NULL');
        $_SESSION['errors'][] = tr('Tutti i promemoria non associati sono stati eliminati!');

        redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);

        break;

            //pianificazione
     case 'pianificazione':

            $idcontratto_riga = filter('idcontratto_riga');
            $intervallo = filter('intervallo');
            $parti_da_oggi = post('parti_da_oggi');

            if (!empty($idcontratto_riga) && !empty($intervallo)) {
                $qp = 'SELECT *, (SELECT idanagrafica FROM co_contratti WHERE id = '.$id_record.' ) AS idanagrafica, (SELECT data_conclusione FROM co_contratti WHERE id = '.$id_record.' ) AS data_conclusione, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_righe_contratti.idtipointervento) AS tipointervento FROM co_righe_contratti WHERE id = '.$idcontratto_riga;
                $rsp = $dbo->fetchArray($qp);

                $idtipointervento = $rsp[0]['idtipointervento'];
                $idsede = $rsp[0]['idsede'];
                $richiesta = $rsp[0]['richiesta'];

                //mi serve per la pianificazione interventi
                $idanagrafica = $rsp[0]['idanagrafica'];

                $data_conclusione = $rsp[0]['data_conclusione'];
                $data_richiesta = $rsp[0]['data_richiesta'];

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
                            if (count($dbo->fetchArray("SELECT id FROM co_righe_contratti WHERE data_richiesta = '".$data_richiesta."' AND idtipointervento = '".$idtipointervento."' AND idcontratto = '".$id_record."' ")) == 0) {
                                $query = 'INSERT INTO `co_righe_contratti`(`idcontratto`, `idtipointervento`, `data_richiesta`, `richiesta`, `idsede`) VALUES('.prepare($id_record).', '.prepare($idtipointervento).', '.prepare($data_richiesta).', '.prepare($richiesta).', '.prepare($idsede).')';

                                if ($dbo->query($query)) {
                                    $idriga = $dbo->lastInsertedID();

                                    $_SESSION['infos'][] = tr('Promemoria intervento pianificato!');

                                    //pianificare anche l' intervento?
                                    if ($post['pianifica_intervento']) {
                                        /*$orario_inizio = post('orario_inizio');
                                        $orario_fine = post('orario_fine');*/

                                        //$idanagrafica = 2;

                                        //intervento sempre nello stato "In programmazione"
                                        $idstatointervento = 'WIP';

                                        //calcolo codice intervento
                                        $formato = get_var('Formato codice intervento');
                                        $template = str_replace('#', '%', $formato);

                                        $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice=(SELECT MAX(CAST(codice AS SIGNED)) FROM in_interventi) AND codice LIKE '.prepare($template).' ORDER BY codice DESC LIMIT 0,1');
                                        $codice = get_next_code($rs[0]['codice'], 1, $formato);

                                        if (empty($codice)) {
                                            $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice LIKE '.prepare($template).' ORDER BY codice DESC LIMIT 0,1');

                                            $codice = get_next_code($rs[0]['codice'], 1, $formato);
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
                                        $dbo->query('UPDATE co_righe_contratti SET idintervento='.prepare($idintervento).' WHERE id='.prepare($idriga));

                                        // $_SESSION['infos'][] = tr('Intervento '.$codice.' pianificato correttamente.');

                                        $_SESSION['infos'][] = tr('Interventi pianificati correttamente.');
                                    }
                                    //fine if pianificazione intervento
                                } else {
                                    $_SESSION['errors'][] = tr('Errore durante esecuzione query di pianificazione.  #'.$idcontratto_riga);
                                }
                            } else {
                                $_SESSION['warnings'][] = tr('Esiste già un promemoria pianificato per il '.readDate($data_richiesta).'.');
                            }
                        }
                        //fine controllo nuova data richiesta
                    }
                    //fine ciclo while
                } else {
                    $_SESSION['errors'][] = tr('Nessuna data di conclusione del contratto oppure quest\'ultima è già trascorsa, impossibile pianificare nuovi promemoria.');
                }
                //fine controllo data_conclusione
            } else {
                $_SESSION['errors'][] = tr('Errore durante la pianificazione.  #'.$idcontratto_riga);
            }

            redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);
    break;
}

// Righe già inserite
$qp = 'SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_righe_contratti.idtipointervento) AS tipointervento FROM co_righe_contratti WHERE idcontratto='.prepare($id_record).' ORDER BY data_richiesta ASC';
$rsp = $dbo->fetchArray($qp);

$pianificabile = $dbo->fetchNum('SELECT id FROM co_staticontratti WHERE pianificabile = 1 AND descrizione = '.prepare($records[0]['stato']));

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
                    <th>'.tr('Entro il').'</th>
                    <th>'.tr('Tipo intervento').'</th>
                    <th>'.tr('Descrizione').'</th>
                    <th>'.tr('Intervento collegato').'</th>
                    <th>'.tr('Sede').'</th>
                    <th>'.tr('Opzioni').'</th>
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

        if (date('Y', strtotime($records[0]['data_conclusione'])) < 1971) {
            $records[0]['data_conclusione'] = '';
        }
        echo '
                <tr>
                    <td>'.Translator::dateToLocale($rsp[$i]['data_richiesta']).'<!--br><small>'.Translator::dateToLocale($records[0]['data_conclusione']).'</small--></td>
                    <td>'.$rsp[$i]['tipointervento'].'</td>
                    <td>'.nl2br($rsp[$i]['richiesta']).'</td>
                    <td>'.$info_intervento.'</td>
                    <td>'.$info_sede.'</td>
                    <td align="right">';

        echo '
				<button type="button" class="btn btn-warning btn-sm" title="Pianifica..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica\', \''.$rootdir.'/modules/contratti/plugins/addpianficazione.php?id_module='.Modules::get('Contratti')['id'].'&id_plugin='.Plugins::get('Pianificazione interventi')['id'].'&ref=interventi_contratti&id_record='.$id_record.'&idcontratto_riga='.$rsp[$i]['id'].'\');"'.((!empty($pianificabile) && strtotime($records[0]['data_conclusione'])) ? '' : ' disabled').'><i class="fa fa-clock-o"></i></button>';

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

    if (count($rsp) > 0) {
        echo '<br><div class="pull-right"><button type="button"  title="Elimina tutti i promemoria per questo contratto che non sono associati ad intervento." class="btn btn-danger ask tip" data-op="delete-promemoria" >
					<i class="fa fa-trash"></i> '.tr('Elimina promemoria').'
				</button></div>';
    }
}

/*
    Nuovo intervento
*/
echo '
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
                            {[ "type": "date", "placeholder": "'.tr('Entro il').'", "name": "data_richiesta", "required": 1, "value": "" ]}
                        </td>
                        <td>
                            {[ "type": "select", "placeholder": "'.tr('Tipo intervento').'", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "'.$rsp[0]['idtipointervento'].'" ]}
                        </td>
                        <td>
                            {[ "type": "textarea", "placeholder": "'.tr('Descrizione').'", "name": "richiesta" ]}
                        </td>
                        <td>
                            {[ "type": "select", "placeholder": "'.tr('Sede').'", "name": "idsede_c", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$records[0]['idanagrafica'].'", "value": "0" ]}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="pull-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
            </div>
            <div class="clearfix"></div>
        </form>
    </div>
</div>';
