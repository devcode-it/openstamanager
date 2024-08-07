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

use Models\Module;
use Util\Query;

switch (get('op')) {
    case 'get_markers':
        $idanagrafica = get('idanagrafica');
        $checks = get('check');

        $where = [];
        // Filtro per anagrafica
        if (!empty($idanagrafica) && $idanagrafica != 'null') {
            $where[] = '`in_interventi`.`idanagrafica`='.prepare($idanagrafica);
        }

        // Filtri per stato
        $checks = explode(',', $checks);
        $where[] = "`in_statiintervento_lang`.`title` IN ('".implode("','", $checks)."')";

        $add_query = 'WHERE 1=1 AND '.implode(' AND ', $where);

        // Filtri per data
        $add_query .= ' |date_period(`orario_inizio`,`data_richiesta`)|';

        $query = 'SELECT *, `in_interventi`.`id` AS idintervento, `an_anagrafiche`.`lat` AS lat_anagrafica, `an_anagrafiche`.`lng` AS lng_anagrafica, `an_anagrafiche`.`indirizzo` AS indirizzo_anagrafica, `an_anagrafiche`.`cap` AS cap_anagrafica, `an_anagrafiche`.`citta` AS citta_anagrafica, `an_anagrafiche`.`provincia` AS provincia_anagrafica, `an_sedi`.`lat` AS lat_sede, `an_sedi`.`lng` AS lng_sede, `an_sedi`.`indirizzo` AS indirizzo_sede, `an_sedi`.`cap` AS cap_sede, `an_sedi`.`citta` AS citta_sede, `an_sedi`.`provincia` AS provincia_sede, `in_statiintervento_lang`.`title` AS stato FROM `in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` LEFT JOIN `an_sedi` ON `in_interventi`.`idsede_destinazione`=`an_sedi`.`id` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` '.$add_query;

        $query = Query::replacePlaceholder($query);
        $query = Modules::replaceAdditionals(Module::where('name', 'Interventi')->first()->id, $query);

        $records = $dbo->fetchArray($query);

        $rs = [];

        if (sizeof($records) > 0) {
            for ($i = 0; $i < sizeof($records); ++$i) {
                if (!empty($records[$i]['idsede_destinazione'])) {
                    $lat = $records[$i]['lat_sede'];
                    $lng = $records[$i]['lng_sede'];

                    $indirizzo = $records[$i]['indirizzo_sede'];
                    $cap = $records[$i]['cap_anagrafica_sede'];
                    $citta = $records[$i]['citta_anagrafica_sede'];
                    $provincia = $records[$i]['provincia_anagrafica_sede'];
                } else {
                    $lat = $records[$i]['lat_anagrafica'];
                    $lng = $records[$i]['lng_anagrafica'];

                    $indirizzo = $records[$i]['indirizzo_anagrafica'];
                    $cap = $records[$i]['cap_anagrafica'];
                    $citta = $records[$i]['citta_anagrafica'];
                    $provincia = $records[$i]['provincia_anagrafica'];
                }

                if ($lat != '0.00000000' && $lng != '0.00000000') {
                    $descrizione = '';

                    $descrizione .= '<i class="fa fa-user"></i> <b>Ragione sociale</b>:  <a href="'.$rootdir.'/editor.php?id_module='.Module::where('name', 'Anagrafiche')->first()->id.'&id_record='.$records[$i]['id'].'" target="_blank" > '.$records[$i]['ragione_sociale'].' <i class="fa fa-external-link"></i> </a>'."\n<br>";

                    if (!empty($indirizzo)) {
                        $descrizione .= '<i class="fa fa-map-signs"></i> <b>Indirizzo</b>: '.$indirizzo."\n<br>";
                    }
                    if (!empty($cap)) {
                        $descrizione .= ' '.$cap;
                    }
                    if (!empty($citta)) {
                        $descrizione .= ', '.$citta;
                    }
                    if (!empty($provincia)) {
                        $descrizione .= ' '.$provincia;
                    }

                    $descrizione .= '<hr>';

                    $descrizione .= '<a class="btn btn-info btn-block btn-xs" onclick="calcolaPercorso(\''.$indirizzo.' '.$cap.' '.$citta.' '.$provincia.'\')">
                                            <i class="fa fa-map-signs"></i> Calcola percorso
                                        </a>';

                    // dettagli intervento
                    $rs_sessioni = $dbo->fetchOne("SELECT MIN(orario_inizio) AS data, GROUP_CONCAT(DISTINCT ragione_sociale SEPARATOR ', ') AS tecnici FROM in_interventi_tecnici INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE idintervento=".prepare($records[$i]['idintervento']).' GROUP BY idintervento');

                    $descrizione .= '<hr>';
                    $descrizione .= '<b>Data</b>: '.(!empty($rs_sessioni['data']) ? Translator::dateToLocale($rs_sessioni['data']) : Translator::dateToLocale($records[$i]['data_richiesta'])).'<br>';
                    $descrizione .= '<b>Stato</b>: '.$records[$i]['stato'].'<br>';
                    $descrizione .= '<b>Richiesta</b>: '.substr(strip_tags((string) $records[$i]['richiesta']), 0, 200).'<br>';
                    if (!empty($rs_sessioni['tecnici'])) {
                        $descrizione .= '<b>Tecnici</b>: '.$rs_sessioni['tecnici'];
                    }

                    $descrizione .= '<hr>';

                    $descrizione .= '<a class="btn btn-info btn-block btn-xs" onclick="window.open(\''.$rootdir.'/editor.php?id_module='.Module::where('name', 'Interventi')->first()->id.'&id_record='.$records[$i]['idintervento'].'\');">
                                            <i class="fa fa-external-link"></i> Apri attività
                                        </a>';

                    $descrizione .= '<hr>';

                    $rs[] = ['descrizione' => $descrizione, 'lat' => $lat, 'lng' => $lng];
                }
            }
        }

        echo json_encode($rs);

        break;
}
