<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Modules\Interventi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Auth;
use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Interventi extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface
{
    public function retrieve($request)
    {
        // Periodo per selezionare interventi
        $today = date('Y-m-d');
        $period_end = date('Y-m-d', strtotime($today.' +7 days'));
        $period_start = date('Y-m-d', strtotime($today.' -2 months'));
        $user = Auth::user();

        // AND `in_statiintervento`.`is_completato`=0
        $query = "SELECT `in_interventi`.`id`,
            `in_interventi`.`codice`,
            `in_interventi`.`data_richiesta`,
            `in_interventi`.`richiesta`,
            `in_interventi`.`descrizione`,
            `in_interventi`.`idtipointervento`,
            `in_interventi`.`idanagrafica`,
            `in_interventi`.`idsede_destinazione`,
            `in_interventi`.`idstatointervento`,
            `in_interventi`.`informazioniaggiuntive`,
            `in_interventi`.`idclientefinale`,
            `in_interventi`.`firma_file`,
            IF(firma_data = '0000-00-00 00:00:00', '', firma_data) AS `firma_data`,
            `in_interventi`.firma_nome,
            (SELECT GROUP_CONCAT(CONCAT(my_impianti.matricola, ' - ', my_impianti.nome) SEPARATOR ', ') FROM (my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto=my_impianti.id) WHERE my_impianti_interventi.idintervento = `in_interventi`.`id`) AS `impianti`,
            (SELECT MAX(`orario_fine`) FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`) AS `data`,
            (SELECT GROUP_CONCAT(DISTINCT ragione_sociale SEPARATOR ', ') FROM `in_interventi_tecnici` INNER JOIN `an_anagrafiche` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` WHERE `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`) AS `tecnici`,
            `in_statiintervento`.`colore` AS `bgcolor`,
            `in_statiintervento`.`descrizione` AS `stato`,
            `in_interventi`.`idtipointervento` AS `tipo`
        FROM `in_interventi`
            INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`idstatointervento`
            INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            LEFT JOIN `an_sedi` ON `in_interventi`.`idsede_destinazione` = `an_sedi`.`id`
        WHERE EXISTS(SELECT `orario_fine` FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` AND `orario_fine` BETWEEN :period_start AND :period_end AND idtecnico LIKE :idtecnico)";

        $query .= '
        HAVING 2=2
        ORDER BY `in_interventi`.`data_richiesta` DESC';

        $parameters = [
            ':period_end' => $period_end,
            ':period_start' => $period_start,
            ':idtecnico' => $user->idanagrafica,
        ];

        $module = Modules::get('Interventi');

        $query = Modules::replaceAdditionals($module->id, $query);

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];

        $anagrafica = Anagrafica::find($data['id_anagrafica']);
        $tipo = TipoSessione::find($data['id_tipo_intervento']);
        $stato = Stato::find($data['id_stato_intervento']);

        $intervento = Intervento::build($anagrafica, $tipo, $stato, $data['data_richiesta']);

        $intervento->richiesta = $data['richiesta'];
        $intervento->descrizione = $data['descrizione'];
        $intervento->informazioniaggiuntive = $data['informazioni_aggiuntive'];
        $intervento->save();

        return [
            'id' => $intervento->id,
            'codice' => $intervento->codice,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $intervento = Intervento::find($data['id']);

        $intervento->idstatointervento = $data['id_stato_intervento'];
        $intervento->descrizione = $data['descrizione'];
        $intervento->informazioniaggiuntive = $data['informazioni_aggiuntive'];
        $intervento->save();
    }
}
