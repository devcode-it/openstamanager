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
use Modules\Fatture\Fattura;
use Util\XML;

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
        $duplicati = database()->fetchArray('SELECT `id_module`, `name` FROM `zz_views` GROUP BY `id_module`, `name` HAVING COUNT(`name`) > 1');

        foreach ($duplicati as $colonna){
            $modulo = Module::pool($colonna['id_module']);

            $this->addResult([
                'id' => $colonna['name'],
                'nome' => $modulo->title.': '.$colonna['name'],
                'descrizione' => tr('La colonna _NAME_ del modulo _MODULE_ esiste più volte', [
                    '_NAME_' => $modulo->title,
                    '_MODULE_' => $modulo->title,
                ]),
            ]);
        }
    }

    public function execute($record, $params = [])
    {
        return false;
    }
}
