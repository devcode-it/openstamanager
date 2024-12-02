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

class PianoContiRagioneSociale extends Controllo
{
    public function getName()
    {
        return tr('Conti collegati alle anagrafiche non corrispondenti alle ragioni sociali');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function getOptions($record)
    {
        return [
            [
                'name' => tr('Risolvi'),
                'icon' => 'fa fa-check',
                'color' => 'success',
                'params' => [],
            ],
        ];
    }

    public function check()
    {
        $database = database();

        /**
         * Verifico se serve creare un conto per eventuali nuovi clienti o fornitori.
         */
        $anagrafiche_interessate = $database->fetchArray('
        SELECT
            `an_anagrafiche`.`idanagrafica` AS id,
            `an_anagrafiche`.`ragione_sociale`,
            `co_pianodeiconti3`.`descrizione` as nome_conto,
            `idconto_cliente`,
            `idconto_fornitore`
        FROM 
            `an_anagrafiche`
            INNER JOIN `co_pianodeiconti3` ON (`an_anagrafiche`.`idconto_cliente` = `co_pianodeiconti3`.`id` OR `an_anagrafiche`.`idconto_fornitore` = `co_pianodeiconti3`.`id`)
        WHERE
            `deleted_at` IS NULL
        GROUP BY `idanagrafica`, `co_pianodeiconti3`.`descrizione`');

        foreach ($anagrafiche_interessate as $anagrafica) {
            if ($anagrafica['nome_conto'] != $anagrafica['ragione_sociale']) {
                $descrizione = tr("Il conto collegato all'anagrafica corrente (_NOME_) non corrisponde alla ragione sociale dell'anagrafica", [
                    '_NOME_' => $anagrafica['nome_conto'],
                ]);

                $this->addResult([
                    'id' => $anagrafica['id'],
                    'nome' => \Modules::link('Anagrafiche', $anagrafica['id'], $anagrafica['ragione_sociale']),
                    'descrizione' => $descrizione,
                ]);
            }
        }
    }

    public function execute($record, $params = [])
    {
        $anagrafica = Anagrafica::find($record['id']);

        $anagrafica = Anagrafica::find($record['id']);

        database()->update('co_pianodeiconti3', [
            'descrizione' => $anagrafica->ragione_sociale,
        ], [
            'id' => $anagrafica->idconto_cliente,
        ]);

        database()->update('co_pianodeiconti3', [
            'descrizione' => $anagrafica->ragione_sociale,
        ], [
            'id' => $anagrafica->idconto_fornitore,
        ]);

        return true;
    }
}
