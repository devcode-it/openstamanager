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
        $duplicati = database()->fetchArray('SELECT `idmodule_to`, `name` FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') GROUP BY `idmodule_to`, `name` HAVING COUNT(`name`) > 1');

        foreach ($duplicati as $plugin) {
            $modulo = Module::find($plugin['idmodule_to']);

            $this->addResult([
                'id' => $plugin->getTranslation('name'),
                'nome' => $modulo->getTranslation('title').': '.$plugin->getTranslation('name'),
                'descrizione' => tr('Il plugin _NAME_ del modulo _MODULE_ esiste più volte', [
                    '_NAME_' => $plugin->getTranslation('name'),
                    '_MODULE_' => $modulo->getTranslation('title'),
                ]),
            ]);
        }
    }

    public function execute($record, $params = [])
    {
        return false;
    }
}
