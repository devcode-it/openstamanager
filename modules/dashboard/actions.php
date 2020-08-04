<?php

include_once __DIR__.'/../../core.php';

$modulo_interventi = Modules::get('Interventi');

if (!isset($user['idanagrafica'])) {
    $user['idanagrafica'] = '';
}

switch (filter('op')) {
    // Lettura calendario tecnici
    case 'interventi_periodo':
        $start = filter('start');
        $end = filter('end');

        $stati = (array) $_SESSION['dashboard']['idstatiintervento'];
        $stati[] = prepare('');

        $tipi = (array) $_SESSION['dashboard']['idtipiintervento'];
        $zone = (array) $_SESSION['dashboard']['idzone'];
        $tecnici = (array) $_SESSION['dashboard']['idtecnici'];

        $query = 'SELECT
            in_interventi_tecnici.id,
            in_interventi_tecnici.idintervento,
            in_interventi.codice,
            colore,
            in_interventi_tecnici.idtecnico,
            in_interventi_tecnici.orario_inizio,
            in_interventi_tecnici.orario_fine,
            (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = idtecnico) AS nome_tecnico,
            (SELECT id FROM zz_files WHERE id_record = in_interventi.id AND id_module = '.prepare($modulo_interventi->id).' LIMIT 1) AS have_attachments,
            (SELECT colore FROM an_anagrafiche WHERE idanagrafica = idtecnico) AS colore_tecnico, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente,
            (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica) AS idzona
        FROM in_interventi_tecnici
            INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id
            LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento = in_statiintervento.idstatointervento
        WHERE
            (
                (in_interventi_tecnici.orario_inizio >= '.prepare($start).' AND in_interventi_tecnici.orario_fine <= '.prepare($end).')
            OR (in_interventi_tecnici.orario_inizio >= '.prepare($start).' AND in_interventi_tecnici.orario_inizio <= '.prepare($end).')
            OR (in_interventi_tecnici.orario_fine >= '.prepare($start).' AND in_interventi_tecnici.orario_fine <= '.prepare($end).')
        ) AND idtecnico IN('.implode(',', $tecnici).')
        AND in_interventi.idstatointervento IN('.implode(',', $stati).')
        AND in_interventi_tecnici.idtipointervento IN('.implode(',', $tipi).')
        '.Modules::getAdditionalsQuery('Interventi').'
    HAVING idzona IN ('.implode(',', $zone).')';
        $sessioni = $dbo->fetchArray($query);

        $results = [];
        foreach ($sessioni as $sessione) {
            $results[] = [
                'id' => $sessione['id'],
                'idintervento' => $sessione['idintervento'],
                'idtecnico' => $sessione['idtecnico'],
                'title' => '<b>Int. '.$sessione['codice'].'</b> '.$sessione['cliente'].'<br><b>'.tr('Tecnici').':</b> '.$sessione['nome_tecnico'].' '.(($sessione['have_attachments']) ? '<i class="fa fa-paperclip" aria-hidden="true"></i>' : ''),
                'start' => $sessione['orario_inizio'],
                'end' => $sessione['orario_fine'],
                'url' => ROOTDIR.'/editor.php?id_module='.$modulo_interventi->id.'&id_record='.$sessione['idintervento'],
                'backgroundColor' => $sessione['colore'],
                'textColor' => color_inverse($sessione['colore']),
                'borderColor' => ($sessione['colore_tecnico'] == '#FFFFFF') ? color_darken($sessione['colore_tecnico'], 100) : $sessione['colore_tecnico'],
                'allDay' => false,
            ];
        }

        echo json_encode($results);

        break;

    case 'modifica_intervento':
        $sessione = filter('id');
        $idintervento = filter('idintervento');
        $orario_inizio = filter('timeStart');
        $orario_fine = filter('timeEnd');

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

    case 'info_intervento':
        $id = filter('id');
        $timeStart = filter('timeStart');
        $timeEnd = filter('timeEnd');

        // Lettura dati intervento di riferimento
        $query = 'SELECT in_interventi_tecnici.idintervento, in_interventi.id, idtecnico, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi');
        $rs = $dbo->fetchArray($query);

        if (!empty($rs)) {
            $tecnici = [];
            foreach ($rs as $sessione) {
                $tecnici[] = $sessione['nome_tecnico'].' ('.Translator::timestampToLocale($sessione['orario_inizio']).' - '.Translator::timeToLocale($sessione['orario_fine']).')';
            }

            // Lettura dati intervento
            $query = 'SELECT *, in_interventi.codice, idstatointervento AS parent_idstato, in_interventi.idtipointervento AS parent_idtipo, (SELECT GROUP_CONCAT(CONCAT(matricola, " - ", nome) SEPARATOR ", ") FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id=my_impianti_interventi.idimpianto WHERE my_impianti_interventi.idintervento='.prepare($id).' GROUP BY my_impianti_interventi.idintervento) AS impianti, (SELECT descrizione FROM in_statiintervento WHERE idstatointervento=parent_idstato) AS stato, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=parent_idtipo) AS tipo, (SELECT nomesede FROM an_sedi WHERE id=idsede_destinazione) AS sede, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS idzona FROM in_interventi LEFT JOIN in_interventi_tecnici ON in_interventi.id =in_interventi_tecnici.idintervento LEFT JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi');
            $rs = $dbo->fetchArray($query);

            $desc_tipointervento = $rs[0]['tipo'];

            $tooltip = '<b>'.tr('Numero intervento').'</b>: '.$rs[0]['codice'].'<br/>';
            $tooltip .= '<b>'.tr('Ragione sociale').'</b>: '.nl2br($rs[0]['ragione_sociale']).'<br/>';

            if (!empty($rs[0]['telefono'])) {
                $tooltip .= '<b>'.tr('Telefono').'</b>: '.nl2br($rs[0]['telefono']).'<br/>';
            }

            if (!empty($rs[0]['cellulare'])) {
                $tooltip .= '<b>'.tr('Cellulare').'</b>: '.nl2br($rs[0]['cellulare']).'<br/>';
            }

            if (!empty($rs[0]['indirizzo']) || !empty($rs[0]['citta']) || !empty($rs[0]['provincia'])) {
                $tooltip .= '<b>'.tr('Indirizzo').'</b>: '.nl2br($rs[0]['indirizzo'].' '.$rs[0]['citta'].' ('.$rs[0]['provincia'].')').'<br/>';
            }

            if (!empty($rs[0]['note'])) {
                $tooltip .= '<b>'.tr('Note').'</b>: '.nl2br($rs[0]['note']).'<br/>';
            }

            $tooltip .= '<b>'.tr('Data richiesta').'</b>: '.Translator::timestampToLocale($rs[0]['data_richiesta']).'<br/>';

            $tooltip .= '<b>'.tr('Tipo intervento').'</b>: '.nl2br($desc_tipointervento).'<br/>';

            $tooltip .= '<b>'.tr('Tecnici').'</b>: '.implode(', ', $tecnici).'<br/>';

            if ($rs[0]['impianti'] != '') {
                $tooltip .= '<b>'.tr('Impianti').'</b>: '.$rs[0]['impianti'].'<br/>';
            }

            if ($rs[0]['richiesta'] != '') {
                $tooltip .= '<b>'.tr('Richiesta').'</b>: '.nl2br($rs[0]['richiesta']).'<br/>';
            }

            if ($rs[0]['descrizione'] != '') {
                $tooltip .= '<b>'.tr('Descrizione').'</b>: '.nl2br($rs[0]['descrizione']).'<br/>';
            }

            if ($rs[0]['informazioniaggiuntive'] != '') {
                $tooltip .= '<b>'.tr('Informazioni aggiuntive').'</b>: '.nl2br($rs[0]['informazioniaggiuntive']).'<br/>';
            }

            echo $tooltip;
        }
        break;

    case 'carica_interventi':
        $mese = filter('mese');

        $solo_promemoria_assegnati = setting('Mostra promemoria attività ai soli Tecnici assegnati');
        $id_tecnico = null;
        if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
            $id_tecnico = $user['idanagrafica'];
        }

        // Righe inserite
        $qp = "SELECT
            co_promemoria.id,
            idcontratto,
            richiesta,co_contratti.nome AS nomecontratto,
            DATE_FORMAT( data_richiesta, '%m%Y') AS mese,
            data_richiesta,
            an_anagrafiche.ragione_sociale,
            'promemoria' AS ref,
            (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento = co_promemoria.idtipointervento) AS tipointervento
        FROM co_promemoria
            INNER JOIN co_contratti ON co_promemoria.idcontratto = co_contratti.id
            INNER JOIN an_anagrafiche ON co_contratti.idanagrafica = an_anagrafiche.idanagrafica
        WHERE idintervento IS NULL AND
              idcontratto IN (SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1))
        ORDER BY data_richiesta ASC";
        $promemoria_contratti = $dbo->fetchArray($qp);

        $query_interventi = "SELECT id, richiesta, id_contratto AS idcontratto, DATE_FORMAT(IF(data_scadenza IS NULL, data_richiesta, data_scadenza),'%m%Y') AS mese, IF(data_scadenza IS NULL, data_richiesta, data_scadenza)AS data_richiesta, data_scadenza, an_anagrafiche.ragione_sociale, 'intervento' AS ref, (SELECT descrizione FROM in_tipiintervento WHERE in_tipiintervento.idtipointervento=in_interventi.idtipointervento) AS tipointervento
    FROM in_interventi
        INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica";

    if (!empty($id_tecnico) && !empty($solo_promemoria_assegnati)) {
        $query_interventi .= '
        INNER JOIN in_interventi_tecnici_assegnati ON in_interventi.id = in_interventi_tecnici_assegnati.id_intervento AND id_tecnico = '.prepare($id_tecnico);
    }

    $query_interventi .= '
        WHERE (SELECT COUNT(*) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id) = 0 ORDER BY data_richiesta ASC';
        $promemoria_interventi = $dbo->fetchArray($query_interventi);

        $promemoria = array_merge($promemoria_contratti, $promemoria_interventi);
        if (!empty($promemoria)) {
            $prev_mese = '';

            // Elenco interventi da pianificare
            foreach ($promemoria as $sessione) {
                if ($sessione['mese'] == $mese) {
                    if (date('Ymd', strtotime($sessione['data_richiesta'])) < date('Ymd')) {
                        $class = 'danger';
                    } else {
                        $class = 'primary';
                    }

                    echo '
                    <div class="fc-event fc-event-'.$class.'" data-id="'.$sessione['id'].'" data-idcontratto="'.$sessione['idcontratto'].'" data-ref="'.$sessione['ref'].'">'.(($sessione['ref'] == 'intervento') ? '<i class=\'fa fa-wrench pull-right\'></i>' : '<i class=\'fa fa-file-text-o pull-right\'></i>').'
                        <b>'.$sessione['ragione_sociale'].'</b><br>'.Translator::dateToLocale($sessione['data_richiesta']).' ('.$sessione['tipointervento'].')<div class="request" >'.(!empty($sessione['richiesta']) ? ' - '.$sessione['richiesta'] : '').'</div>'.(!empty($sessione['nomecontratto']) ? '<br><b>Contratto:</b> '.$sessione['nomecontratto'] : '').
                        (!empty($sessione['data_scadenza'] and $sessione['data_scadenza'] != '0000-00-00 00:00:00') ? '<br><small>'.tr('entro il: ').Translator::dateToLocale($sessione['data_scadenza']).'</small>' : '').
                        (($sessione['ref'] == 'intervento') ? (Modules::link('Interventi', $sessione['id'], '<i class="fa fa-eye"></i>', null, 'title="'.tr('Visualizza scheda').'" class="btn btn-'.$class.' btn-xs pull-right"')).'<br>' : (Modules::link('Contratti', $sessione['idcontratto'], '<i class="fa fa-eye"></i>', null, 'title="'.tr('Visualizza scheda').'" class="btn btn-'.$class.' btn-xs pull-right"')).'<br>').
                    '</div>';
                }
            }

            echo '
            <script type="text/javascript">
                $(".request").shorten({
                    moreText: "'.tr('Mostra tutto').'",
                    lessText: "'.tr('Comprimi').'",
                    showChars : 200
                });
            </script>';
        } else {
            echo '<br><small class="help-block">'.tr('Non ci sono interventi da pianificare per questo mese').'</small>';
        }

        break;
}
