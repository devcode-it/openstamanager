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

use Models\Cache;
use Modules\Aggiornamenti\Controlli\Controllo;
use Modules\Aggiornamenti\Controlli\DatiFattureElettroniche;
use Modules\Aggiornamenti\Controlli\PianoConti;
use Modules\Aggiornamenti\UpdateHook;

$id = post('id');

switch (filter('op')) {
    case 'check':
        $result = UpdateHook::isAvailable();
        $versione = false;
        if ($result) {
            $versione = $result[0].' ('.$result[1].')';
        }

        // Salvataggio della versione nella cache
        Cache::pool('Ultima versione di OpenSTAManager disponibile')->set($versione);

        echo $versione;

        break;

    case 'upload':
        include base_dir().'/modules/aggiornamenti/upload_modules.php';

        break;

    case 'controlli-disponibili':
        $controlli = [
            PianoConti::class,
            DatiFattureElettroniche::class,
        ];

        $results = [];
        foreach ($controlli as $key => $controllo) {
            $results[] = [
                'id' => $key,
                'class' => $controllo,
                'name' => (new $controllo())->getName(),
            ];
        }

        echo json_encode($results);

        break;

    case 'controlli-check':
        $class = post('controllo');

        // Controllo sulla classe
        if (!is_subclass_of($class, Controllo::class)) {
            echo json_encode([]);

            return;
        }

        $manager = new $class();
        $manager->check();

        echo json_encode($manager->getResults());

        break;

    case 'controlli-action':
        $class = post('controllo');
        $records = post('records');
        $params = post('params');

        // Controllo sulla classe
        if (!is_subclass_of($class, Controllo::class)) {
            echo json_encode([]);

            return;
        }

        $manager = new $class();
        $result = $manager->solve($records, $params);

        echo json_encode($result);

        break;
}
