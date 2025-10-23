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

use Models\Module;

class ColonneDuplicateViste extends Controllo
{
    public function getName()
    {
        return tr('Colonne duplicate per le Viste');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function check()
    {
        // 1. Controllo duplicati nel campo 'name' della tabella zz_views
        $duplicati_name = database()->fetchArray('
            SELECT `id_module`, `name`, COUNT(*) as `count`
            FROM `zz_views`
            WHERE `name` IS NOT NULL AND `name` != ""
            GROUP BY `id_module`, `name`
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicati_name as $colonna) {
            $modulo = Module::find($colonna['id_module']);

            $this->addResult([
                'id' => 'name_'.$colonna['id_module'].'_'.$colonna['name'],
                'nome' => $modulo->getTranslation('title').': '.$colonna['name'].' (name)',
                'descrizione' => tr('La colonna _NAME_ del modulo _MODULE_ esiste _COUNT_ volte nella tabella zz_views (campo name)', [
                    '_NAME_' => $colonna['name'],
                    '_MODULE_' => $modulo->getTranslation('title'),
                    '_COUNT_' => $colonna['count'],
                ]),
            ]);
        }

        // 2. Controllo record duplicati in zz_views_lang con stesso id_record e id_lang
        $duplicati_record_lang = database()->fetchArray('
            SELECT `zz_views`.`id_module`, `zz_views_lang`.`id_record`, `zz_views_lang`.`id_lang`, COUNT(*) as `count`
            FROM `zz_views_lang`
            INNER JOIN `zz_views` ON `zz_views`.`id` = `zz_views_lang`.`id_record`
            GROUP BY `zz_views_lang`.`id_record`, `zz_views_lang`.`id_lang`
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicati_record_lang as $colonna) {
            $modulo = Module::find($colonna['id_module']);
            $lingua = database()->fetchOne('SELECT `name` FROM `zz_langs` WHERE `id` = '.prepare($colonna['id_lang']));
            $vista = database()->fetchOne('SELECT `name` FROM `zz_views` WHERE `id` = '.prepare($colonna['id_record']));

            // Estrai solo la parte principale del nome della lingua (es. "English" da "English (English)")
            $nome_lingua = explode(' (', (string) $lingua['name'])[0];

            $this->addResult([
                'id' => 'record_lang_'.$colonna['id_record'].'_'.$colonna['id_lang'],
                'nome' => $modulo->getTranslation('title').': '.$vista['name'].' ('.$nome_lingua.')',
                'descrizione' => tr('La vista _NAME_ del modulo _MODULE_ ha _COUNT_ traduzioni duplicate per la lingua _LANG_', [
                    '_NAME_' => $vista['name'],
                    '_MODULE_' => $modulo->getTranslation('title'),
                    '_COUNT_' => $colonna['count'],
                    '_LANG_' => $nome_lingua,
                ]),
            ]);
        }
    }

    /**
     * Indica se questo controllo supporta azioni globali.
     */
    public function hasGlobalActions()
    {
        return true;
    }

    public function execute($record, $params = []): never
    {
        // La risoluzione singola non è supportata per questo controllo
        // Utilizzare solo la risoluzione globale tramite il pulsante "Risolvi tutti i conflitti"
        throw new \Exception(tr('La risoluzione singola non è supportata. Utilizzare la risoluzione globale.'));
    }

    /**
     * Risolve tutti i conflitti eliminando i record più vecchi e mantenendo quello più recente.
     */
    public function solveGlobal($params = [])
    {
        $results = [];
        $database = database();

        try {
            // 1. Risolvi duplicati nel campo 'name' della tabella zz_views
            $duplicati_name = $database->fetchArray('
                SELECT `id`, `id_module`, `name`, `created_at`
                FROM `zz_views`
                WHERE `name` IS NOT NULL AND `name` != ""
                AND (`id_module`, `name`) IN (
                    SELECT `id_module`, `name`
                    FROM `zz_views`
                    WHERE `name` IS NOT NULL AND `name` != ""
                    GROUP BY `id_module`, `name`
                    HAVING COUNT(*) > 1
                )
                ORDER BY `id_module`, `name`, `id` DESC
            ');

            $grouped_name = [];
            foreach ($duplicati_name as $record) {
                $key = $record['id_module'].'_'.$record['name'];
                $grouped_name[$key][] = $record;
            }

            foreach ($grouped_name as $group) {
                if (count($group) > 1) {
                    // Mantieni il primo record (più recente per ID) ed elimina gli altri
                    for ($i = 1; $i < count($group); ++$i) {
                        $database->query('DELETE FROM `zz_views` WHERE `id` = '.prepare($group[$i]['id']));
                        $results['name_'.$group[$i]['id']] = true;
                    }
                }
            }

            // 2. Risolvi record duplicati in zz_views_lang con stesso id_record e id_lang
            $duplicati_record_lang = $database->fetchArray('
                SELECT `id`, `id_record`, `id_lang`
                FROM `zz_views_lang`
                WHERE (`id_record`, `id_lang`) IN (
                    SELECT `id_record`, `id_lang`
                    FROM `zz_views_lang`
                    GROUP BY `id_record`, `id_lang`
                    HAVING COUNT(*) > 1
                )
                ORDER BY `id_record`, `id_lang`, `id` DESC
            ');

            $grouped_record_lang = [];
            foreach ($duplicati_record_lang as $record) {
                $key = $record['id_record'].'_'.$record['id_lang'];
                $grouped_record_lang[$key][] = $record;
            }

            foreach ($grouped_record_lang as $group) {
                if (count($group) > 1) {
                    // Mantieni il primo record (più recente per ID) ed elimina gli altri
                    for ($i = 1; $i < count($group); ++$i) {
                        $database->query('DELETE FROM `zz_views_lang` WHERE `id` = '.prepare($group[$i]['id']));
                        $results['record_lang_'.$group[$i]['id']] = true;
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
            throw new \Exception(tr('Errore durante la risoluzione dei conflitti: _ERROR_', ['_ERROR_' => $e->getMessage()]));
        }
    }
}
