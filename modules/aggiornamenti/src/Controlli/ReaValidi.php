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

namespace Modules\Aggiornamenti\Controlli;

use Modules\Anagrafiche\Anagrafica;

class ReaValidi extends Controllo
{
    public function getName()
    {
        return tr('Anagrafiche con codici R.E.A. non validi');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function getOptions($record)
    {
        return [];
    }

    public function check()
    {
        $database = database();

        /**
         * Verifico se i R.E.A. inseriti per le anagrafiche hanno una struttura valida.
         */
        $anagrafiche_interessate = $database->fetchArray('SELECT
            an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.codicerea,
            an_anagrafiche.ragione_sociale,
            GROUP_CONCAT(an_tipianagrafiche.descrizione) AS tipi_anagrafica
        FROM an_anagrafiche
           INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
           INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.idtipoanagrafica = an_tipianagrafiche_anagrafiche.idtipoanagrafica
        WHERE
            codicerea NOT REGEXP "([A-Za-z]{2})-([0-9]{1,20})" AND codicerea != ""
        AND
            deleted_at IS NULL
        GROUP BY an_anagrafiche.idanagrafica');

        foreach ($anagrafiche_interessate as $anagrafica) {

            /*$tipi = explode(',', $anagrafica['tipi_anagrafica']);
            $cliente = in_array('Cliente', $tipi) && empty($anagrafica['idconto_cliente']);
            $fornitore = in_array('Fornitore', $tipi) && empty($anagrafica['idconto_fornitore']);*/

            $this->addResult([
                'id' => $anagrafica['id'],
                'nome' => \Modules::link('Anagrafiche', $anagrafica['id'], $anagrafica['ragione_sociale']),
                'descrizione' => tr('Il codice REA "_REA_" non è valido', [
                    '_REA_' => $anagrafica['codicerea'],
                ]),
            ]);
          
        }
    }

    public function execute($record, $params = [])
    {
        return false;
    }
}
