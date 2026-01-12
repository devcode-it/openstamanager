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
use Models\OperationLog;
use Modules\Aggiornamenti\Controlli\ColonneDuplicateViste;
use Modules\Aggiornamenti\Controlli\Controllo;
use Modules\Aggiornamenti\Controlli\DatiFattureElettroniche;
use Modules\Aggiornamenti\Controlli\FileHtaccess;
use Modules\Aggiornamenti\Controlli\IntegritaFile;
use Modules\Aggiornamenti\Controlli\PianoConti;
use Modules\Aggiornamenti\Controlli\PianoContiRagioneSociale;
use Modules\Aggiornamenti\Controlli\PluginDuplicati;
use Modules\Aggiornamenti\Controlli\ReaValidi;
use Modules\Aggiornamenti\Controlli\TabelleLanguage;
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
                ++$executed;
            } catch (Exception $e) {
                $errors[] = $query.' - '.$e->getMessage();
            }
        }
        $dbo->query('SET FOREIGN_KEY_CHECKS=1');

        if (empty($errors)) {
            $success_message = tr('Tutte le query sono state eseguite con successo (_NUM_ query).', [
                '_NUM_' => $executed,
            ]);

            flash()->info($success_message);

            // Log dell'operazione di risoluzione conflitti database
            OperationLog::setInfo('id_module', $id_module ?? null);
            OperationLog::setInfo('options', json_encode([
                'queries_executed' => $executed,
                'total_queries' => count($queries),
            ], JSON_UNESCAPED_UNICODE));
            OperationLog::build('risolvi-conflitti-database');

            echo json_encode([
                'success' => true,
                'message' => $success_message.'<br><br>'.tr('Query eseguite:').'<br>'.$debug_queries,
                'flash_message' => true,
            ]);
        } else {
            $error_message = tr('Si sono verificati errori durante l\'esecuzione di alcune query (_NUM_ su _TOTAL_).', [
                '_NUM_' => count($errors),
                '_TOTAL_' => count($queries),
            ]);

            flash()->error($error_message);

            // Log dell'errore nell'operazione di risoluzione conflitti database
            OperationLog::setInfo('id_module', $id_module ?? null);
            OperationLog::setInfo('options', json_encode([
                'queries_executed' => $executed,
                'total_queries' => count($queries),
                'errors_count' => count($errors),
            ], JSON_UNESCAPED_UNICODE));
            OperationLog::build('risolvi-conflitti-database-error');

            echo json_encode([
                'success' => false,
                'message' => $error_message.'<br>'.implode('<br>', $errors).'<br><br>'.tr('Query da eseguire:').'<br>'.$debug_queries,
                'flash_message' => true,
            ]);
        }

        exit;

    case 'check':
        try {
            $result = UpdateHook::isAvailable();
            $versione = false;
            if ($result) {
                $versione = $result[0].' ('.$result[1].')';
            }

            // Salvataggio della versione nella cache
            $cache = Cache::where('name', 'Ultima esecuzione del cron')->first();
            if ($cache) {
                $cache->set($versione);
            }

            echo $versione ?: 'none';
        } catch (Exception $e) {
            // Log dell'errore per debug
            error_log('Errore verifica aggiornamenti: '.$e->getMessage());

            // Restituisce un messaggio di errore specifico
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => tr('Errore durante la verifica degli aggiornamenti: _ERROR_', ['_ERROR_' => $e->getMessage()]),
            ]);
        }

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
            ReaValidi::class,
            DatiFattureElettroniche::class,
            ColonneDuplicateViste::class,
            PluginDuplicati::class,
            TabelleLanguage::class,
            IntegritaFile::class,
            FileHtaccess::class,
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
        $class = post('controllo', true);
        $controllo_name = post('controllo_name', true);

        // Controllo sulla classe
        if (!is_subclass_of($class, Controllo::class)) {
            echo json_encode([]);

            return;
        }

        $manager = new $class();
        $manager->check();

        // Creazione del log dell'operazione
        $nome_finale = !empty($controllo_name) ? $controllo_name : $manager->getName();

        OperationLog::setInfo('id_module', $id_module);
        OperationLog::setInfo('options', json_encode(['controllo_name' => $nome_finale], JSON_UNESCAPED_UNICODE));

        echo json_encode($manager->getResults());

        return;

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

    case 'controlli-action-global':
        $class = post('controllo');
        $params = post('params');

        // Controllo sulla classe
        if (!is_subclass_of($class, Controllo::class)) {
            echo json_encode([]);

            return;
        }

        $manager = new $class();
        $manager->check(); // Ricarica i risultati
        $result = $manager->solveGlobal($params);

        // Aggiunta del nome del controllo alle opzioni di log
        OperationLog::setInfo('options', json_encode(['controllo_name' => $manager->getName()], JSON_UNESCAPED_UNICODE));

        echo json_encode($result);

        break;

    case 'controlli-ultima-esecuzione':
        $controlli = [
            PianoConti::class,
            PianoContiRagioneSociale::class,
            ReaValidi::class,
            DatiFattureElettroniche::class,
            ColonneDuplicateViste::class,
            PluginDuplicati::class,
            TabelleLanguage::class,
            IntegritaFile::class,
            FileHtaccess::class,
        ];

        $results = [];
        foreach ($controlli as $key => $controllo) {
            $manager = new $controllo();
            $nome_controllo = $manager->getName();

            // Recupera l'ultima esecuzione da zz_operations
            // Cerca nel campo options che contiene JSON con controllo_name
            $query = 'SELECT created_at, id_utente FROM zz_operations WHERE options LIKE ? ORDER BY created_at DESC LIMIT 1';
            $operation = $database->fetchOne($query, ['%"controllo_name":"'.$nome_controllo.'"%']);

            // Recupera il nome dell'utente se disponibile
            $user_name = null;
            if ($operation && $operation['id_utente']) {
                $user_query = 'SELECT username FROM zz_users WHERE id = ?';
                $user = $database->fetchOne($user_query, [$operation['id_utente']]);
                $user_name = $user ? $user['username'] : null;
            }

            $result_item = [
                'id' => $key,
                'class' => $controllo,
                'name' => $nome_controllo,
                'last_execution' => $operation ? $operation['created_at'] : null,
                'last_user' => $user_name,
            ];

            // Aggiungi le date di filtro per il controllo DatiFattureElettroniche
            if ($controllo === DatiFattureElettroniche::class) {
                $result_item['period_start'] = $_SESSION['period_start'] ?? null;
                $result_item['period_end'] = $_SESSION['period_end'] ?? null;
            }

            $results[] = $result_item;
        }

        echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        break;
}
