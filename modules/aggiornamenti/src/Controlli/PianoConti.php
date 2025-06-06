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

class PianoConti extends Controllo
{
    public function getName()
    {
        return tr('Piano dei Conti collegato alle anagrafiche');
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
        $anagrafiche_interessate = $database->fetchArray('SELECT
            `an_anagrafiche`.`idanagrafica` AS id,
            `an_anagrafiche`.`idconto_cliente`,
            `an_anagrafiche`.`idconto_fornitore`,
            `an_anagrafiche`.`ragione_sociale`,
            GROUP_CONCAT(`an_tipianagrafiche_lang`.`title`) AS tipi_anagrafica
        FROM `an_anagrafiche`
            INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
            LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        WHERE
            (`idconto_cliente` = 0 OR `idconto_cliente` IS NULL OR `idconto_fornitore` = 0 OR `idconto_fornitore` IS NULL)
            AND
            `deleted_at` IS NULL
        GROUP BY `an_anagrafiche`.`idanagrafica`');

        foreach ($anagrafiche_interessate as $anagrafica) {
            $tipi = explode(',', (string) $anagrafica['tipi_anagrafica']);
            $cliente = in_array('Cliente', $tipi);
            $fornitore = in_array('Fornitore', $tipi);
            $is_esistente = 0;
            $descrizione = 0;

            if ($cliente || $fornitore) {
                $is_esistente = $database->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE id = '.$anagrafica['idconto_cliente'].' OR id = '.$anagrafica['idconto_fornitore']);
                $descrizione = null;

                if (($cliente && $fornitore) && (empty($anagrafica['idconto_cliente']) || empty($anagrafica['idconto_fornitore']) || !$is_esistente)) {
                    $descrizione = tr("L'anagrafica corrente non ha impostati i conti relativi al Piano dei Conti");
                } elseif ($cliente && (empty($anagrafica['idconto_cliente'])) || !$is_esistente) {
                    $descrizione = tr("L'anagrafica corrente non ha impostati il conto Cliente relativo al Piano dei Conti");
                } elseif ($fornitore && (empty($anagrafica['idconto_fornitore'])) || !$is_esistente) {
                    $descrizione = tr("L'anagrafica corrente non ha impostati il conto Fornitore relativo al Piano dei Conti");
                }
            }

            if (!empty($descrizione)) {
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

        if ($anagrafica->isTipo('Cliente')) {
            Anagrafica::fixCliente($anagrafica);
        }

        if ($anagrafica->isTipo('Fornitore')) {
            Anagrafica::fixFornitore($anagrafica);
        }

        return true;
    }
}
