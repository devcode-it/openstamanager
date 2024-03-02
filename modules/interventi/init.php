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

use Modules\Interventi\Intervento;

if (isset($id_record)) {
    $intervento = Intervento::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
        `in_interventi`.`descrizione` AS descrizione,
        `in_interventi`.`codice` AS codice,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica,
        `in_statiintervento`.`is_completato` AS flag_completato,
        `in_statiintervento`.`colore` AS colore,
        IF((`in_interventi`.`idsede_destinazione` = 0), `an_anagrafiche`.`idzona`, `an_sedi`.`idzona`) AS idzona,
        `in_interventi`.`idanagrafica` as idanagrafica,
        `in_interventi`.`id_preventivo` as idpreventivo,
        `in_interventi`.`id_contratto` as idcontratto,
        `in_interventi`.`id_ordine` as idordine
    FROM 
        in_interventi
        INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
        LEFT JOIN `an_sedi` ON `in_interventi`.`idsede_destinazione` = `an_sedi`.`id`
        INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
    WHERE 
        `in_interventi`.`id`='.prepare($id_record));

    
    //Pulsante Precedente e Successivo all'interno della scheda attività
    //Ricavo la query del modulo
    $module_query = Util\Query::getQuery(Models\Module::getCurrent());
    
    //Aggiunto eventuali filtri applicati alla vista
    if (!empty(getSearchValues($id_module))) {
        $having = [];
        $search_values = getSearchValues($id_module);
        foreach($search_values as $key => $value) {
            $having[] = '`'.$key.'` LIKE "%'.$value.'%"';
        }
        foreach($having as $condition) {
            $module_query = str_replace('2=2', '2=2 AND '.$condition,  $module_query);
        }

        //Controllo se questo id_record è presente all'interno dei risultati, altrimenti non considero i filtri applicati alla vista
        /*$query_test = str_replace('1=1', '1=1 AND `in_interventi`.`id` ='.prepare($id_record),  $module_query);
        if (count($database->FetchArray($query_test))>0)
            $module_query =  $module_query;
        else 
            $module_query = Util\Query::getQuery(Models\Module::getCurrent());*/
    }
    
    //Mi ricavo la posizione di questo id_record
    $database->FetchArray('SET @posizione = 0;');
    $posizioni = $database->FetchArray($module_query);
    foreach($posizioni as $posizione) {
        if ($posizione['id'] == $id_record)
            $posizione_attuale = $posizione['posizione']; 
    }
    
    $prev_query = str_replace('2=2', '2=2 AND `posizione` ='.$posizione_attuale-1,  $module_query);
    $database->FetchArray('SET @posizione = 0;');
    $prev = $database->FetchOne($prev_query)['id'];
    
    $next_query = str_replace('2=2', '2=2 AND `posizione` ='.$posizione_attuale+1,  $module_query);
    $database->FetchArray('SET @posizione = 0;');
    $next = $database->FetchOne($next_query)['id'];
   

}
