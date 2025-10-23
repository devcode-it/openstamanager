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

class PluginDuplicati extends Controllo
{
    public function getName()
    {
        return tr('Plugin duplicati per i Moduli');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function check()
    {
        // 1. Controllo duplicati nel campo 'name' della tabella zz_plugins
        $duplicati_name = database()->fetchArray('
            SELECT `idmodule_to`, `name`, COUNT(*) as `count`
            FROM `zz_plugins`
            WHERE `name` IS NOT NULL AND `name` != ""
            GROUP BY `idmodule_to`, `name`
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicati_name as $plugin) {
            $modulo = Module::find($plugin['idmodule_to']);

            $this->addResult([
                'id' => 'name_'.$plugin['idmodule_to'].'_'.$plugin['name'],
                'nome' => $modulo->getTranslation('title').': '.$plugin['name'].' (name)',
                'descrizione' => tr('Il plugin _NAME_ del modulo _MODULE_ esiste _COUNT_ volte nella tabella zz_plugins (campo name)', [
                    '_NAME_' => $plugin['name'],
                    '_MODULE_' => $modulo->getTranslation('title'),
                    '_COUNT_' => $plugin['count'],
                ]),
            ]);
        }

        // 2. Controllo plugin diversi con stesso title nello stesso modulo e lingua
        $duplicati_title_diversi = database()->fetchArray('
            SELECT `zz_plugins`.`idmodule_to`, `zz_plugins_lang`.`title`, `zz_plugins_lang`.`id_lang`,
                   COUNT(DISTINCT `zz_plugins_lang`.`id_record`) as `count_plugin`,
                   GROUP_CONCAT(DISTINCT `zz_plugins`.`name`) as `nomi_plugin`
            FROM `zz_plugins_lang`
            INNER JOIN `zz_plugins` ON `zz_plugins`.`id` = `zz_plugins_lang`.`id_record`
            WHERE `zz_plugins_lang`.`title` IS NOT NULL AND `zz_plugins_lang`.`title` != ""
            GROUP BY `zz_plugins`.`idmodule_to`, `zz_plugins_lang`.`title`, `zz_plugins_lang`.`id_lang`
            HAVING COUNT(DISTINCT `zz_plugins_lang`.`id_record`) > 1
        ');

        foreach ($duplicati_title_diversi as $plugin) {
            $modulo = Module::find($plugin['idmodule_to']);
            $lingua = database()->fetchOne('SELECT `name` FROM `zz_langs` WHERE `id` = '.prepare($plugin['id_lang']));

            // Estrai solo la parte principale del nome della lingua (es. "English" da "English (English)")
            $nome_lingua = explode(' (', (string) $lingua['name'])[0];

            $this->addResult([
                'id' => 'title_diversi_'.$plugin['idmodule_to'].'_'.$plugin['id_lang'].'_'.md5((string) $plugin['title']),
                'nome' => $modulo->getTranslation('title').': '.$plugin['title'].' ('.$nome_lingua.')',
                'descrizione' => tr('Il titolo "_TITLE_" del modulo _MODULE_ è usato da _COUNT_ plugin diversi (_PLUGINS_) nella lingua _LANG_', [
                    '_TITLE_' => $plugin['title'],
                    '_MODULE_' => $modulo->getTranslation('title'),
                    '_COUNT_' => $plugin['count_plugin'],
                    '_PLUGINS_' => $plugin['nomi_plugin'],
                    '_LANG_' => $nome_lingua,
                ]),
            ]);
        }

        // 3. Controllo record duplicati in zz_plugins_lang con stesso id_record e id_lang
        $duplicati_record_lang = database()->fetchArray('
            SELECT `zz_plugins`.`idmodule_to`, `zz_plugins_lang`.`id_record`, `zz_plugins_lang`.`id_lang`, COUNT(*) as `count`
            FROM `zz_plugins_lang`
            INNER JOIN `zz_plugins` ON `zz_plugins`.`id` = `zz_plugins_lang`.`id_record`
            GROUP BY `zz_plugins_lang`.`id_record`, `zz_plugins_lang`.`id_lang`
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicati_record_lang as $plugin) {
            $modulo = Module::find($plugin['idmodule_to']);
            $lingua = database()->fetchOne('SELECT `name` FROM `zz_langs` WHERE `id` = '.prepare($plugin['id_lang']));
            $plugin_record = database()->fetchOne('SELECT `name` FROM `zz_plugins` WHERE `id` = '.prepare($plugin['id_record']));

            // Estrai solo la parte principale del nome della lingua (es. "English" da "English (English)")
            $nome_lingua = explode(' (', (string) $lingua['name'])[0];

            $this->addResult([
                'id' => 'record_lang_'.$plugin['id_record'].'_'.$plugin['id_lang'],
                'nome' => $modulo->getTranslation('title').': '.$plugin_record['name'].' ('.$nome_lingua.')',
                'descrizione' => tr('Il plugin _NAME_ del modulo _MODULE_ ha _COUNT_ traduzioni duplicate per la lingua _LANG_', [
                    '_NAME_' => $plugin_record['name'],
                    '_MODULE_' => $modulo->getTranslation('title'),
                    '_COUNT_' => $plugin['count'],
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
            // 1. Risolvi duplicati nel campo 'name' della tabella zz_plugins
            $duplicati_name = $database->fetchArray('
                SELECT `id`, `idmodule_to`, `name`
                FROM `zz_plugins`
                WHERE `name` IS NOT NULL AND `name` != ""
                AND (`idmodule_to`, `name`) IN (
                    SELECT `idmodule_to`, `name`
                    FROM `zz_plugins`
                    WHERE `name` IS NOT NULL AND `name` != ""
                    GROUP BY `idmodule_to`, `name`
                    HAVING COUNT(*) > 1
                )
                ORDER BY `idmodule_to`, `name`, `id` DESC
            ');

            $grouped_name = [];
            foreach ($duplicati_name as $record) {
                $key = $record['idmodule_to'].'_'.$record['name'];
                $grouped_name[$key][] = $record;
            }

            foreach ($grouped_name as $group) {
                if (count($group) > 1) {
                    // Mantieni il primo record (più recente per ID) ed elimina gli altri
                    for ($i = 1; $i < count($group); ++$i) {
                        $database->query('DELETE FROM `zz_plugins` WHERE `id` = '.prepare($group[$i]['id']));
                        $results['name_'.$group[$i]['id']] = true;
                    }
                }
            }

            // 2. Risolvi record duplicati in zz_plugins_lang con stesso id_record e id_lang
            $duplicati_record_lang = $database->fetchArray('
                SELECT `id`, `id_record`, `id_lang`
                FROM `zz_plugins_lang`
                WHERE (`id_record`, `id_lang`) IN (
                    SELECT `id_record`, `id_lang`
                    FROM `zz_plugins_lang`
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
                        $database->query('DELETE FROM `zz_plugins_lang` WHERE `id` = '.prepare($group[$i]['id']));
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
