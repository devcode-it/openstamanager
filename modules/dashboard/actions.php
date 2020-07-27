<?php

include_once __DIR__.'/../../core.php';

if (!isset($user['idanagrafica'])) {
    $user['idanagrafica'] = '';
}

switch (get('op')) {
    // Lettura calendario tecnici
    case 'get_current_month':
        $start = get('start');
        $end = get('end');

        $stati = (array) $_SESSION['dashboard']['idstatiintervento'];
        $stati[] = prepare('');

        $tipi = (array) $_SESSION['dashboard']['idtipiintervento'];

        $query = 'SELECT in_interventi_tecnici.id, in_interventi_tecnici.idintervento, in_interventi.codice, colore, idtecnico, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT id FROM zz_files WHERE id_record=in_interventi.id AND id_module = '.Modules::get('Interventi')['id'].' LIMIT 1) AS have_attachments, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore_tecnico, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS idzona FROM in_interventi_tecnici INNER JOIN (in_interventi LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento) ON in_interventi_tecnici.idintervento=in_interventi.id WHERE ( (in_interventi_tecnici.orario_inizio >= '.prepare($start).' AND in_interventi_tecnici.orario_fine <= '.prepare($end).') OR (in_interventi_tecnici.orario_inizio >= '.prepare($start).' AND in_interventi_tecnici.orario_inizio <= '.prepare($end).') OR (in_interventi_tecnici.orario_fine >= '.prepare($start).' AND in_interventi_tecnici.orario_fine <= '.prepare($end).')) AND idtecnico IN('.implode(',', $_SESSION['dashboard']['idtecnici']).') AND in_interventi.idstatointervento IN('.implode(',', $stati).') AND in_interventi_tecnici.idtipointervento IN('.implode(',', $tipi).') '.Modules::getAdditionalsQuery('Interventi').' HAVING idzona IN ('.implode(',', $_SESSION['dashboard']['idzone']).')';
        $rs = $dbo->fetchArray($query);

        $results = [];
        foreach ($rs as $r) {
            $results[] = [
                'id' => $r['id'],
                'idintervento' => $r['idintervento'],
                'idtecnico' => $r['idtecnico'],
                'title' => '<b>Int. '.$r['codice'].'</b> '.$r['cliente'].'<br><b>'.tr('Tecnici').':</b> '.$r['nome_tecnico'].' '.(($r['have_attachments']) ? '<i class="fa fa-paperclip" aria-hidden="true"></i>' : ''),
                'start' => $r['orario_inizio'],
                'end' => $r['orario_fine'],
                'url' => $rootdir.'/editor.php?id_module='.Modules::get('Interventi')['id'].'&id_record='.$r['idintervento'],
                'backgroundColor' => $r['colore'],
                'textColor' => color_inverse($r['colore']),
                'borderColor' => ($r['colore_tecnico'] == '#FFFFFF') ? color_darken($r['colore_tecnico'], 100) : $r['colore_tecnico'],
                'allDay' => false,
            ];
        }

        echo json_encode($results);

        break;

    case 'update_intervento':
        $sessione = get('id');
        $idintervento = get('idintervento');
        $orario_inizio = get('timeStart');
        $orario_fine = get('timeEnd');

        // Aggiornamento prezzo totale
        $q = 'SELECT in_interventi_tecnici.prezzo_ore_unitario, idtecnico, in_statiintervento.is_completato FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento =  in_statiintervento.idstatointervento WHERE in_interventi.id='.prepare($idintervento).' AND in_statiintervento.is_completato = 0 '.Modules::getAdditionalsQuery('Interventi');
        $rs = $dbo->fetchArray($q);
        $prezzo_ore = 0.00;

        for ($i = 0; $i < count($rs); ++$i) {
            $prezzo_ore_unitario = $rs[$i]['prezzo_ore_unitario'];
            $ore = calcola_ore_intervento($orario_inizio, $orario_fine);

            $prezzo_ore += $ore * $prezzo_ore_unitario;
        }

        if (count($rs) > 0) {
            // Aggiornamento orario tecnico
            $dbo->query('UPDATE in_interventi_tecnici SET orario_inizio = '.prepare($orario_inizio).', orario_fine = '.prepare($orario_fine).', ore='.prepare($ore).', prezzo_ore_consuntivo='.prepare($t * $prezzo_ore_unitario).' WHERE id='.prepare($sessione));
            echo 'ok';
        } else {
            echo tr('Attività completata, non è possibile modificarla!');
        }

        break;

    case 'get_more_info':
        $id = get('id');
        $timeStart = get('timeStart');
        $timeEnd = get('timeEnd');

        //Lettura dati intervento di riferimento
        $query = 'SELECT in_interventi_tecnici.idintervento, in_interventi.id, idtecnico, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi');
        $rs = $dbo->fetchArray($query);

        if (!empty($rs)) {
            $tecnici = [];
            foreach ($rs as $r) {
                $tecnici[] = $r['nome_tecnico'].' ('.Translator::timestampToLocale($r['orario_inizio']).' - '.Translator::timeToLocale($r['orario_fine']).')';
            }

            // Lettura dati intervento
            $query = 'SELECT *, in_interventi.codice, idstatointervento AS parent_idstato, in_interventi.idtipointervento AS parent_idtipo, (SELECT GROUP_CONCAT(CONCAT(matricola, " - ", nome) SEPARATOR ", ") FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id=my_impianti_interventi.idimpianto WHERE my_impianti_interventi.idintervento='.prepare($id).' GROUP BY my_impianti_interventi.idintervento) AS impianti, (SELECT descrizione FROM in_statiintervento WHERE idstatointervento=parent_idstato) AS stato, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=parent_idtipo) AS tipo, (SELECT nomesede FROM an_sedi WHERE id=idsede_destinazione) AS sede, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS idzona FROM in_interventi LEFT JOIN in_interventi_tecnici ON in_interventi.id =in_interventi_tecnici.idintervento LEFT JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi');
            $rs = $dbo->fetchArray($query);

            $desc_tipointervento = $rs[0]['tipo'];

            $tooltip_text = '<b>'.tr('Numero intervento').'</b>: '.$rs[0]['codice'].'<br/>';
            $tooltip_text .= '<b>'.tr('Ragione sociale').'</b>: '.nl2br($rs[0]['ragione_sociale']).'<br/>';

            if (!empty($rs[0]['telefono'])) {
                $tooltip_text .= '<b>'.tr('Telefono').'</b>: '.nl2br($rs[0]['telefono']).'<br/>';
            }

            if (!empty($rs[0]['cellulare'])) {
                $tooltip_text .= '<b>'.tr('Cellulare').'</b>: '.nl2br($rs[0]['cellulare']).'<br/>';
            }

            if (!empty($rs[0]['indirizzo']) || !empty($rs[0]['citta']) || !empty($rs[0]['provincia'])) {
                $tooltip_text .= '<b>'.tr('Indirizzo').'</b>: '.nl2br($rs[0]['indirizzo'].' '.$rs[0]['citta'].' ('.$rs[0]['provincia'].')').'<br/>';
            }

            if (!empty($rs[0]['note'])) {
                $tooltip_text .= '<b>'.tr('Note').'</b>: '.nl2br($rs[0]['note']).'<br/>';
            }

            $tooltip_text .= '<b>'.tr('Data richiesta').'</b>: '.Translator::timestampToLocale($rs[0]['data_richiesta']).'<br/>';

            $tooltip_text .= '<b>'.tr('Tipo intervento').'</b>: '.nl2br($desc_tipointervento).'<br/>';

            $tooltip_text .= '<b>'.tr('Tecnici').'</b>: '.implode(', ', $tecnici).'<br/>';

            if ($rs[0]['impianti'] != '') {
                $tooltip_text .= '<b>'.tr('Impianti').'</b>: '.$rs[0]['impianti'].'<br/>';
            }

            if ($rs[0]['richiesta'] != '') {
                $tooltip_text .= '<b>'.tr('Richiesta').'</b>: '.nl2br($rs[0]['richiesta']).'<br/>';
            }

            if ($rs[0]['descrizione'] != '') {
                $tooltip_text .= '<b>'.tr('Descrizione').'</b>: '.nl2br($rs[0]['descrizione']).'<br/>';
            }

            if ($rs[0]['informazioniaggiuntive'] != '') {
                $tooltip_text .= '<b>'.tr('Informazioni aggiuntive').'</b>: '.nl2br($rs[0]['informazioniaggiuntive']).'<br/>';
            }

            echo $tooltip_text;
        }

    break;

    case 'load_intreventi':
        $mese = $_GET['mese'];

        // Righe inserite
        $qp = "SELECT
            co_promemoria.id,
            idcontratto,
            richiesta,co_contratti.nome AS nomecontratto,
            DATE_FORMAT( data_richiesta, '%m%Y') AS mese,
            data_richiesta,
            an_anagrafiche.ragione_sociale,
            'promemoria' AS ref,
            (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_promemoria.idtipointervento) AS tipointervento
        FROM co_promemoria
            INNER JOIN co_contratti ON co_promemoria.idcontratto=co_contratti.id
            INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica
        WHERE
              idcontratto IN(SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1)) AND
              idintervento IS NULL
        ORDER BY data_richiesta ASC";

        $rsp = $dbo->fetchArray($qp);

        $interventi = $dbo->fetchArray("SELECT id, richiesta, id_contratto AS idcontratto, DATE_FORMAT(IF(data_scadenza IS NULL, data_richiesta, data_scadenza),'%m%Y') AS mese, IF(data_scadenza IS NULL, data_richiesta, data_scadenza)AS data_richiesta, data_scadenza, an_anagrafiche.ragione_sociale, 'intervento' AS ref, (SELECT descrizione FROM in_tipiintervento WHERE in_tipiintervento.idtipointervento=in_interventi.idtipointervento) AS tipointervento FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE (SELECT COUNT(*) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id) = 0 ORDER BY data_richiesta ASC");

        $rsp = array_merge($rsp, $interventi);

        if (!empty($rsp)) {
            $prev_mese = '';

            // Elenco interventi da pianificare
            foreach ($rsp as $r) {
                if ($r['mese'] == $mese) {
                    if (date('Ymd', strtotime($r['data_richiesta'])) < date('Ymd')) {
                        $class = 'danger';
                    } else {
                        $class = 'primary';
                    }

                    echo '
                    <div class="fc-event fc-event-'.$class.'" data-id="'.$r['id'].'" data-idcontratto="'.$r['idcontratto'].'" data-ref="'.$r['ref'].'">'.(($r['ref'] == 'intervento') ? '<i class=\'fa fa-wrench pull-right\'></i>' : '<i class=\'fa fa-file-text-o pull-right\'></i>').'
                        <b>'.$r['ragione_sociale'].'</b><br>'.Translator::dateToLocale($r['data_richiesta']).' ('.$r['tipointervento'].')<div class="request" >'.(!empty($r['richiesta']) ? ' - '.$r['richiesta'] : '').'</div>'.(!empty($r['nomecontratto']) ? '<br><b>Contratto:</b> '.$r['nomecontratto'] : '').
                        (!empty($r['data_scadenza'] and $r['data_scadenza'] != '0000-00-00 00:00:00') ? '<br><small>'.tr('entro il: ').Translator::dateToLocale($r['data_scadenza']).'</small>' : '').
                        (($r['ref'] == 'intervento') ? (Modules::link('Interventi', $r['id'], '<i class="fa fa-eye"></i>', null, 'title="'.tr('Visualizza scheda').'" class="btn btn-'.$class.' btn-xs pull-right"')).'<br>' : (Modules::link('Contratti', $r['idcontratto'], '<i class="fa fa-eye"></i>', null, 'title="'.tr('Visualizza scheda').'" class="btn btn-'.$class.' btn-xs pull-right"')).'<br>').
                    '</div>';
                }
            } ?>
            <script type="text/javascript">
                $(".request").shorten({
                    moreText: '<?php echo tr('Mostra tutto'); ?>',
                    lessText: '<?php echo tr('Comprimi'); ?>',
                    showChars : 200
                });
            </script>
            <?php
        } else {
            echo '<br><small class="help-block">'.tr('Non ci sono interventi da pianificare per questo mese').'</small>';
        }

        break;
}
