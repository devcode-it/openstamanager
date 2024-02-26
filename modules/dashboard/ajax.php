<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

$modulo_interventi = Modules::get('Interventi');
$modulo_preventivi = Modules::get('Preventivi');
$modulo_eventi = Modules::get('Eventi');

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
            (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica) AS idzona,
            in_statiintervento.is_completato AS is_completato
        FROM in_interventi_tecnici
            INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id
            LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento = in_statiintervento.idstatointervento
        WHERE
            (
                (
                    in_interventi_tecnici.orario_inizio >= '.prepare($start).'
                    AND
                    in_interventi_tecnici.orario_fine <= '.prepare($end).'
                )
                OR
                (
                    in_interventi_tecnici.orario_inizio >= '.prepare($start).'
                    AND
                    in_interventi_tecnici.orario_inizio <= '.prepare($end).'
                )
                OR
                (
                    in_interventi_tecnici.orario_inizio <= '.prepare($start).'
                    AND
                    in_interventi_tecnici.orario_fine >= '.prepare($end).'
                )
                OR
                (
                    in_interventi_tecnici.orario_fine >= '.prepare($start).'
                    AND
                    in_interventi_tecnici.orario_fine <= '.prepare($end).'
                )
            )
            AND
            idtecnico IN('.implode(',', $tecnici).')
            AND
            in_interventi.idstatointervento IN('.implode(',', $stati).')
            AND
            in_interventi_tecnici.idtipointervento IN('.implode(',', $tipi).')
            '.Modules::getAdditionalsQuery('Interventi').'
        HAVING
            idzona IN ('.implode(',', $zone).')';
        $sessioni = $dbo->fetchArray($query);

        $results = [];
        foreach ($sessioni as $sessione) {
            if (setting('Visualizzazione colori sessioni') == 'Sfondo colore stato - bordo colore tecnico') {
                $backgroundcolor = strtoupper($sessione['colore']);
                $bordercolor = strtoupper($sessione['colore_tecnico']);
            } else {
                $backgroundcolor = strtoupper($sessione['colore_tecnico']);
                $bordercolor = strtoupper($sessione['colore']);
            }

            $results[] = [
                'id' => $sessione['id'],
                'title' => (($sessione['is_completato']) ? '<i class="fa fa-lock" aria-hidden="true"></i>' : '').' '.(($sessione['have_attachments']) ? '<i class="fa fa-paperclip" aria-hidden="true"></i>' : '').($sessione['is_completato'] || $sessione['have_attachments'] ? '<br>' : '').'<b>Int. '.$sessione['codice'].'</b> '.$sessione['cliente'].'<br><b>'.tr('Tecnici').':</b> '.$sessione['nome_tecnico'],
                'start' => $sessione['orario_inizio'],
                'end' => $sessione['orario_fine'],
                'extendedProps' => [
                    'link' => base_path().'/editor.php?id_module='.$modulo_interventi->id.'&id_record='.$sessione['idintervento'],
                    'idintervento' => $sessione['idintervento'],
                    'idtecnico' => $sessione['idtecnico'],
                ],
                'backgroundColor' => $backgroundcolor,
                'textColor' => color_inverse($backgroundcolor),
                'borderColor' => empty($bordercolor) ? '#FFFFFF' : $bordercolor,
                'allDay' => false,
            ];
        }

        if (setting('Visualizza informazioni aggiuntive sul calendario')) {
            // # Box allDay preventivi
            $query = 'SELECT
                `co_preventivi`.`id`,
                `co_preventivi`.`nome`,
                `co_preventivi`.`numero`,
                `co_preventivi`.`data_accettazione`,
                `co_preventivi`.`data_conclusione`,
                `co_statipreventivi`.`is_pianificabile`,
                `co_statipreventivi_lang`.`name` as stato,
                `co_statipreventivi`.`is_completato`,
                `an_anagrafiche`. `ragione_sociale` AS cliente,
                `zz_files`.`id` AS have_attachments
            FROM `co_preventivi`
                INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
                LEFT JOIN `zz_files` ON `zz_files`.`id_record` = `co_preventivi`.`id` AND `zz_files`.`id_module` = '.prepare($modulo_preventivi->id).'
                LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
                LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi_lang`.`id_record` = `co_statipreventivi`.`id` AND `co_statipreventivi_lang`.`id_lang` = '.prepare(setting('Lingua')).')
            WHERE
            (
                (`co_preventivi`.`data_accettazione` >= '.prepare($start).' AND `co_preventivi`.`data_accettazione` <= '.prepare($end).')
                OR (`co_preventivi`.`data_conclusione` >= '.prepare($start).' AND `co_preventivi`.`data_conclusione` <= '.prepare($end).')
            )';

            $preventivi = $dbo->fetchArray($query);

            foreach ($preventivi as $preventivo) {
                if ($preventivo['is_pianificabile'] == 1 || $preventivo['stato'] = 'In attesa di conferma') {
                    if (!empty($preventivo['data_accettazione']) && $preventivo['data_accettazione'] != '0000-00-00') {
                        $query.'AND `co_statipreventivi`.`is_pianificabile`=1';
                        $results[] = [
                            'id' => 'A_'.$modulo_preventivi->id.'_'.$preventivo['id'],
                            'idintervento' => $preventivo['id'],
                            'idtecnico' => '',
                            'title' => '<div style=\'position:absolute; top:7%; right:3%;\' > '.(($preventivo['is_completato']) ? '<i class="fa fa-lock" aria-hidden="true"></i>' : '<i class="fa fa-pencil" aria-hidden="true"></i>').' '.(($preventivo['have_attachments']) ? '<i class="fa fa-paperclip" aria-hidden="true"></i>' : '').'</div><b>'.tr('Accettazione prev.').' '.$preventivo['numero'].'</b> '.$preventivo['nome'].'<br><b>'.tr('Cliente').':</b> '.$preventivo['cliente'],
                            'start' => $preventivo['data_accettazione'],
                            // 'end' => $preventivo['data_accettazione'],
                            'url' => base_path().'/editor.php?id_module='.$modulo_preventivi->id.'&id_record='.$preventivo['id'],
                            'backgroundColor' => '#ff7f50',
                            'textColor' => color_inverse('#ff7f50'),
                            'borderColor' => '#ff7f50',
                            'allDay' => true,
                            'eventStartEditable' => false,
                            'editable' => false,
                        ];
                    }

                    if ($preventivo['data_accettazione'] != $preventivo['data_conclusione'] && $preventivo['data_conclusione'] != '0000-00-00' && !empty($preventivo['data_conclusione'])) {
                        $results[] = [
                            'id' => 'B_'.$modulo_preventivi->id.'_'.$preventivo['id'],
                            'idintervento' => $preventivo['id'],
                            'idtecnico' => '',
                            'title' => '<div style=\'position:absolute; top:7%; right:3%;\' > '.(($preventivo['is_completato']) ? '<i class="fa fa-lock" aria-hidden="true"></i>' : '<i class="fa fa-pencil" aria-hidden="true"></i>').' '.(($preventivo['have_attachments']) ? '<i class="fa fa-paperclip" aria-hidden="true"></i>' : '').'</div><b>'.tr('Conclusione prev.').' '.$preventivo['numero'].'</b> '.$preventivo['nome'].'<br><b>'.tr('Cliente').':</b> '.$preventivo['cliente'],
                            'start' => $preventivo['data_conclusione'],
                            // 'end' => $preventivo['data_conclusione'],
                            'url' => base_path().'/editor.php?id_module='.$modulo_preventivi->id.'&id_record='.$preventivo['id'],
                            'backgroundColor' => '#ff7f50',
                            'textColor' => color_inverse('#ff7f50'),
                            'borderColor' => '#ff7f50',
                            'allDay' => true,
                            'eventStartEditable' => false,
                            'editable' => false,
                        ];
                    }
                }
            }

            // # Box allDay eventi (escluse festività)
            $query = 'SELECT
                *
            FROM 
                `zz_events`
            WHERE
                `zz_events`.`is_bank_holiday` = 0
            AND (`zz_events`.`is_recurring` = 1 AND
                DAYOFYEAR(`zz_events`.`data`) BETWEEN DAYOFYEAR('.prepare($start).') AND IF(YEAR('.prepare($start).') = YEAR('.prepare($end).'), DAYOFYEAR('.prepare($end).'), DAYOFYEAR('.prepare(date('Y-m-d', strtotime($end.'-1 day'))).')) 
                )
            OR 
            (`zz_events`.`is_recurring` = 0 AND `zz_events`.`data` >= '.prepare($start).' AND  `zz_events`.`data` <= '.prepare($end).')';

            // echo $query;

            $eventi = $dbo->fetchArray($query);

            foreach ($eventi as $evento) {
                $results[] = [
                    'id' => $modulo_eventi->id.'_'.$evento['id'],
                    'title' => '<b>'.tr('Evento').':</b> '.$evento['nome'].'</b>',
                    'start' => ($evento['is_recurring'] ? date('Y-', strtotime($start)).date('m-d', strtotime($evento['data'])) : $evento['data']),
                    // 'end' => $evento['data'],
                    'extendedProps' => [
                        'link' => base_path().'/editor.php?id_module='.$modulo_eventi->id.'&id_record='.$evento['id'],
                        'idintervento' => $evento['id'],
                        'idtecnico' => '',
                    ],
                    'backgroundColor' => '#ffebcd',
                    'textColor' => color_inverse('#ffebcd'),
                    'borderColor' => '#ffebcd',
                    'allDay' => true,
                    'eventStartEditable' => false,
                    'editable' => false,
                ];
            }
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
            // FIXME: usare la classe e relativo metodo
            $dbo->query('UPDATE in_interventi_tecnici SET orario_inizio = '.prepare($orario_inizio).', orario_fine = '.prepare($orario_fine).', ore='.prepare($ore).' WHERE id='.prepare($sessione));
            echo 'ok';
        } else {
            echo tr('Attività completata, non è possibile modificarla!');
        }

        break;

    case 'tooltip_info':
        $id = filter('id_record');
        $allDay = filter('allDay');
        $timeStart = filter('timeStart');
        $timeEnd = filter('timeEnd');

        if ($allDay == 'false') {
            // Lettura dati intervento di riferimento
            $query = 'SELECT in_interventi_tecnici.idintervento, in_interventi.id, idtecnico, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi', null, false);
            $rs = $dbo->fetchArray($query);

            if (!empty($rs)) {
                $tecnici = [];
                foreach ($rs as $sessione) {
                    $tecnici[] = $sessione['nome_tecnico'].' ('.Translator::timestampToLocale($sessione['orario_inizio']).' - '.Translator::timeToLocale($sessione['orario_fine']).')';
                }

                // Lettura dati intervento
                $query = 'SELECT *, in_interventi.codice, an_anagrafiche.note AS note_anagrafica, idstatointervento AS parent_idstato, in_interventi.idtipointervento AS parent_idtipo, (SELECT GROUP_CONCAT(CONCAT(matricola, " - ", nome) SEPARATOR ", ") FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id=my_impianti_interventi.idimpianto WHERE my_impianti_interventi.idintervento='.prepare($id).' GROUP BY my_impianti_interventi.idintervento) AS impianti, (SELECT descrizione FROM in_statiintervento WHERE idstatointervento=parent_idstato) AS stato, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=parent_idtipo) AS tipo, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS idzona FROM in_interventi LEFT JOIN in_interventi_tecnici ON in_interventi.id =in_interventi_tecnici.idintervento LEFT JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi', null, false);
                $rs = $dbo->fetchArray($query);

                // correggo info indirizzo citta cap provincia con quelle della sede di destinazione
                if (!empty($rs[0]['idsede_destinazione'])) {
                    $sede = $database->fetchOne('SELECT * FROM an_sedi WHERE id = '.prepare($rs[0]['idsede_destinazione']));
                    $rs[0]['indirizzo'] = $sede['nomesede'].'<br>'.$sede['indirizzo'];
                    $rs[0]['cap'] = $sede['cap'];
                    $rs[0]['citta'] = $sede['citta'];
                    $rs[0]['provincia'] = $sede['provincia'];
                }

                $desc_tipointervento = $rs[0]['tipo'];

                $tooltip = '<b>'.tr('Numero intervento').'</b>: '.$rs[0]['codice'].'<br/>';

                $tooltip .= '<b>'.tr('Data richiesta').'</b>: '.Translator::timestampToLocale($rs[0]['data_richiesta']).'<br/>';

                if (!empty($rs[0]['data_scadenza'])) {
                    $tooltip .= '<b>'.tr('Data scadenza').'</b>: '.Translator::timestampToLocale($rs[0]['data_scadenza']).'<br/>';
                }

                $tooltip .= '<b>'.tr('Tipo intervento').'</b>: '.nl2br($desc_tipointervento).'<br/>';

                $tooltip .= '<b>'.tr('Tecnici').'</b>: '.implode(', ', $tecnici).'<br/>';

                if ($rs[0]['impianti'] != '') {
                    $tooltip .= '<b>'.tr('Impianti').'</b>: '.$rs[0]['impianti'].'<br/>';
                }

                if ($rs[0]['richiesta'] != '') {
                    $tooltip .= '<b>'.tr('Richiesta').'</b>:<div class=\'shorten\'> '.nl2br($rs[0]['richiesta']).'</div>';
                }

                if ($rs[0]['descrizione'] != '') {
                    $tooltip .= '<b>'.tr('Descrizione').'</b>:<div class=\'shorten\'> '.nl2br($rs[0]['descrizione']).'</div>';
                }

                if ($rs[0]['informazioniaggiuntive'] != '') {
                    $tooltip .= '<b>'.tr('Informazioni aggiuntive').'</b>: '.nl2br($rs[0]['informazioniaggiuntive']).'<br/>';
                }

                $tooltip .= '<b>'.tr('Ragione sociale').'</b>: '.nl2br($rs[0]['ragione_sociale']).'<br/>';

                if (!empty($rs[0]['telefono'])) {
                    $tooltip .= '<b>'.tr('Telefono').'</b>: '.nl2br($rs[0]['telefono']).'<br/>';
                }

                if (!empty($rs[0]['cellulare'])) {
                    $tooltip .= '<b>'.tr('Cellulare').'</b>: '.nl2br($rs[0]['cellulare']).'<br/>';
                }

                if (!empty($rs[0]['indirizzo']) || !empty($rs[0]['citta']) || !empty($rs[0]['provincia']) || !empty($rs[0]['cap'])) {
                    $tooltip .= '<b>'.tr('Indirizzo').'</b>: '.nl2br($rs[0]['indirizzo'].' - '.$rs[0]['cap'].' '.$rs[0]['citta'].' ('.$rs[0]['provincia'].')').'<br/>';
                }

                if (!empty($rs[0]['note_anagrafica'])) {
                    $tooltip .= '<b>'.tr('Note anagrafica').'</b>: '.nl2br($rs[0]['note_anagrafica']).'<br/>';
                }
            }
        } else {
            $query = 'SELECT
                `co_preventivi`.`nome`,
                `co_preventivi`.`numero`,
                `co_preventivi`.`data_accettazione`,
                `co_preventivi`.`data_conclusione`,
                `an_anagrafiche`.`ragione_sociale` AS cliente,
                `zz_files`.`id` AS have_attachments
            FROM 
                `co_preventivi`
                INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
                LEFT JOIN zz_files ON zz_files.id_record = co_preventivi.id AND zz_files.id_module = '.prepare($modulo_preventivi->id).'
                LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
                LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi_lang`.`id_record` = `co_statipreventivi`.`id` AND `co_statipreventivi_lang`.`id_lang` = '.prepare(setting('Lingua')).')
            WHERE 
                `co_preventivi`.`id`='.prepare($id);

            $rs = $dbo->fetchArray($query);

            if (!empty($rs[0]['cliente'])) {
                $tooltip = '<b>Prev. '.$rs[0]['numero'].'</b> '.$rs[0]['nome'].''.(($rs[0]['have_attachments']) ? ' <i class="fa fa-paperclip" aria-hidden="true"></i>' : '').'<br><b>'.tr('Cliente').':</b> '.$rs[0]['cliente'];
            } else {
                $tooltip = tr('Rilascia per aggiungere l\'attività...');
            }
        }

        $tooltip .= '
            <script type="text/javascript">
                $(".shorten").shorten({
                    moreText: "'.tr('Mostra tutto').'",
                    lessText: "'.tr('Comprimi').'",
                    showChars : 200
                });
            </script>';

        echo $tooltip;

        break;

    case 'carica_interventi':
        $mese = filter('mese');

        $solo_promemoria_assegnati = setting('Visualizza solo promemoria assegnati');
        $id_tecnico = null;
        if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
            $id_tecnico = $user['idanagrafica'];
        }

        // Promemoria da contratti con stato pianificabile
        $query_promemoria_contratti = "SELECT
            `co_promemoria`.`id`,
            `idcontratto`,
            `richiesta`,
            `co_contratti`.`nome` AS nome_contratto,
            `co_contratti`.`numero` AS numero_contratto,
            `co_contratti`.`data_bozza` AS data_contratto,
            DATE_FORMAT( `data_richiesta`, '%m%Y') AS mese,
            `data_richiesta`,
            `an_anagrafiche`.`ragione_sociale`,
            'promemoria' AS ref,
            `in_tipiintervento`.`descrizione` AS tipo_intervento
        FROM `co_promemoria`
            INNER JOIN `co_contratti` ON `co_promemoria`.`idcontratto` = `co_contratti`.`id`
            INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
            INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            LEFT JOIN `in_tipiintervento` ON `co_promemoria`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`
        WHERE `idintervento` IS NULL AND `co_staticontratti`.`is_pianificabile` = 1
        ORDER BY `data_richiesta` ASC";
        $promemoria_contratti = $dbo->fetchArray($query_promemoria_contratti);

        // Promemoria da interventi con stato NON completato
        $query_interventi = "SELECT `in_interventi`.`id`,
            `in_interventi`.`richiesta`,
            `in_interventi`.`id_contratto` AS idcontratto,
            `in_interventi_tecnici_assegnati`.`id_tecnico` AS id_tecnico,
            `tecnico`.`ragione_sociale` AS ragione_sociale_tecnico,
            DATE_FORMAT(IF(`in_interventi`.`data_scadenza` IS NULL, `in_interventi`.`data_richiesta`, `in_interventi`.`data_scadenza`), '%m%Y') AS mese,
            IF(`in_interventi`.`data_scadenza` IS NULL, `in_interventi`.`data_richiesta`, `in_interventi`.`data_scadenza`) AS data_richiesta,
            `in_interventi`.`data_scadenza`,
            `an_anagrafiche`.`ragione_sociale`,
            `tecnico`.`colore`,
            'intervento' AS ref,
            `in_tipiintervento`.`descrizione` AS tipo_intervento
        FROM 
            `in_interventi`
            INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`
            INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`";

        // Visualizzo solo promemoria del tecnico loggato
        if (!empty($id_tecnico) && !empty($solo_promemoria_assegnati)) {
            $query_interventi .= '
        INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi`.`id` = `in_interventi_tecnici_assegnati`.`id_intervento` AND `id_tecnico` = '.prepare($id_tecnico);
        } else {
            $query_interventi .= '
        LEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi`.`id` = `in_interventi_tecnici_assegnati`.`id_intervento`';
        }

        $query_interventi .= '
            LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
            INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`idstatointervento`
            LEFT JOIN `an_anagrafiche` AS tecnico ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `tecnico`.`idanagrafica`
        WHERE 
            `in_statiintervento`.`is_completato` = 0
        GROUP BY 
            `in_interventi`.`id`, `in_interventi_tecnici_assegnati`.`id_tecnico`
        HAVING 
            COUNT(`in_interventi_tecnici`.`id`) = 0
        ORDER BY 
            `data_richiesta` ASC';
        $promemoria_interventi = $dbo->fetchArray($query_interventi);

        $promemoria = array_merge($promemoria_contratti, $promemoria_interventi);

        if (!empty($promemoria)) {
            $prev_mese = '';

            // Elenco interventi da pianificare
            foreach ($promemoria as $sessione) {
                if ($sessione['mese'] == $mese || $mese == 'all') {
                    if (date('Ymd', strtotime($sessione['data_scadenza'])) < date('Ymd') and !empty($sessione['data_scadenza'])) {
                        $class = 'danger';
                    } else {
                        $class = 'primary';
                    }

                    $link = null;
                    if ($sessione['ref'] == 'intervento') {
                        $modulo_riferimento = 'Interventi';
                        $id_riferimento = $sessione['id'];
                    } else {
                        $modulo_riferimento = 'Contratti';
                        $id_riferimento = $sessione['idcontratto'];
                    }

                    echo '
                    <div id="id-'.$sessione['id'].'" class="fc-event fc-event-'.$class.'" data-id="'.$sessione['id'].'" data-idcontratto="'.$sessione['idcontratto'].'" data-ref="'.$sessione['ref'].'" data-id_tecnico="'.$sessione['id_tecnico'].'">'.($sessione['ref'] == 'intervento' ? Modules::link($modulo_riferimento, $id_riferimento, '<i class="fa fa-wrench"></i>', null, 'title="'.tr('Visualizza scheda').'" class="btn btn-'.$class.' btn-xs pull-right"') : Modules::link($modulo_riferimento, $id_riferimento, '<i class="fa fa-file-text-o"></i>', null, 'title="'.tr('Visualizza scheda').'" class="btn btn-'.$class.' btn-xs pull-right"')).'
                        <b>'.$sessione['ragione_sociale'].'</b>
                        <br>'.dateFormat($sessione['data_richiesta']).' ('.$sessione['tipo_intervento'].')
                        <div class="request">'.(!empty($sessione['richiesta']) ? ' - '.$sessione['richiesta'] : '').'</div>
                        '.(!empty($sessione['numero_contratto']) ? '<span class="label label-'.$class.'">'.tr('Contratto numero: ').$sessione['numero_contratto'].tr(' del ').dateFormat($sessione['data_contratto']).'<span>' : '').' '.(!empty($sessione['data_scadenza'] && $sessione['data_scadenza'] != '0000-00-00 00:00:00') ? '<span class="label label-'.$class.'" >'.tr('Entro il: ').dateFormat($sessione['data_scadenza']).'</span>' : '').' '.(!empty($sessione['id_tecnico']) ? '<span class="label" style="color:'.color_inverse($sessione['colore']).';background-color:'.$sessione['colore'].';" >'.tr('Tecnico').': '.$sessione['ragione_sociale_tecnico'].'</span>' : '').'
                    </div>';
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

    case 'calendario_eventi':
        $start = filter('start');
        $end = filter('end');

        // TODO: Problema con anni bisestili Es. 2024-02-29 e 2023-03-01 sono entrambi il 60 esimo giorno dell'anno.
        $query = 'SELECT *, DAYOFYEAR(`zz_events`.`data`) AS d, DAYOFYEAR('.prepare($start).') AS st, DAYOFYEAR('.prepare($end).') AS fi FROM `zz_events` 
            WHERE `zz_events`.`is_bank_holiday` = 1 
            AND 
            (`zz_events`.`is_recurring` = 1 
            AND DAYOFYEAR(`zz_events`.`data`) BETWEEN DAYOFYEAR('.prepare($start).') AND IF(YEAR('.prepare($start).') = YEAR('.prepare($end).'), DAYOFYEAR('.prepare($end).'), DAYOFYEAR('.prepare(date('Y-m-d', strtotime($end.'-1 day'))).')) )
            OR 
            (`zz_events`.`is_recurring` = 0 AND `zz_events`.`data` >= '.prepare($start).' AND  `zz_events`.`data` <= '.prepare($end).')';

        $eventi = $dbo->fetchArray($query);

        $results = [];
        foreach ($eventi as $evento) {
            $results[] = [
            'id' => $evento['id'],
            'title' => $evento['nome'],
            'start' => ($evento['is_recurring'] ? date('Y-', strtotime($start)).date('m-d', strtotime($evento['data'])) : $evento['data']),
            // 'end' => date('Y-m-d', strtotime($evento['data']. '+1 day')),
            'display' => 'background',
            'allDay' => true,
            'overlap' => true,
            ];
        }

        echo json_encode($results);

        break;
}
