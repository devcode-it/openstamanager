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
use Modules\Aggiornamenti\Controlli\ColonneDuplicateViste;
use Modules\Aggiornamenti\Controlli\Controllo;
use Modules\Aggiornamenti\Controlli\DatiFattureElettroniche;
use Modules\Aggiornamenti\Controlli\PianoConti;
use Modules\Aggiornamenti\Controlli\PianoContiRagioneSociale;
use Modules\Aggiornamenti\Controlli\PluginDuplicati;
use Modules\Aggiornamenti\Controlli\ReaValidi;
use Modules\Aggiornamenti\UpdateHook;

$id = post('id');

switch (filter('op')) {
    case 'risolvi-conflitti-database':
        $queries_json = post('queries');
        if (empty($queries_json)) {
            echo json_encode([
                'success' => false,
                'message' => tr('Nessuna query ricevuta.'),
            ]);
            break;
        }

        $queries = json_decode($queries_json, true);
        if (empty($queries)) {
            echo json_encode([
                'success' => false,
                'message' => tr('Nessuna query da eseguire.'),
            ]);
            break;
        }

        if (empty($queries)) {
            echo json_encode([
                'success' => false,
                'message' => tr('Nessuna query valida da eseguire.'),
            ]);
            break;
        }

        $debug_queries = implode('<br>', $queries);

        $dbo->query('SET FOREIGN_KEY_CHECKS=0');

        $errors = [];
        $executed = 0;

        foreach ($queries as $query) {
            try {
                $dbo->query($query);
                $executed++;
            } catch (Exception $e) {
                $errors[] = $query . ' - ' . $e->getMessage();
            }
        }
        $dbo->query('SET FOREIGN_KEY_CHECKS=1');

        if (empty($errors)) {
            $success_message = tr('Tutte le query sono state eseguite con successo (_NUM_ query).', [
                '_NUM_' => $executed,
            ]);

            flash()->info($success_message);

            echo json_encode([
                'success' => true,
                'message' => $success_message . '<br><br>' . tr('Query eseguite:') . '<br>' . $debug_queries,
                'flash_message' => true,
            ]);
        } else {
            $error_message = tr('Si sono verificati errori durante l\'esecuzione di alcune query (_NUM_ su _TOTAL_).', [
                '_NUM_' => count($errors),
                '_TOTAL_' => count($queries),
            ]);

            flash()->error($error_message);

            echo json_encode([
                'success' => false,
                'message' => $error_message . '<br>' . implode('<br>', $errors) . '<br><br>' . tr('Query da eseguire:') . '<br>' . $debug_queries,
                'flash_message' => true,
            ]);
        }

        exit;

    case 'check':
        $result = UpdateHook::isAvailable();
        $versione = false;
        if ($result) {
            $versione = $result[0].' ('.$result[1].')';
        }

        // Salvataggio della versione nella cache
        Cache::where('name', 'Ultima esecuzione del cron')->first()->set($versione);
        echo $versione;

        break;

    case 'upload':
        if (setting('Attiva aggiornamenti')) {
            include base_dir().'/modules/aggiornamenti/upload_modules.php';
        } else {
            flash()->error(tr('Non è permesso il caricamento di aggiornamenti o moduli!'));
        }

        break;

    case 'controlli-disponibili':
        $controlli = [
            PianoConti::class,
            PianoContiRagioneSociale::class,
            DatiFattureElettroniche::class,
            ColonneDuplicateViste::class,
            PluginDuplicati::class,
            ReaValidi::class,
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
