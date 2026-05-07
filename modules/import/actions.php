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

use Models\Module;
use Modules\FileAdapters\FileAdapter;
use Modules\Importazione\Import;

include_once __DIR__.'/../../core.php';

$modulo_import = Module::find($id_module);

switch (filter('op')) {
    case 'add':
        $id_import = filter('id_import');
        $import = Import::find($id_import);
        $id_adapter = FileAdapter::getLocalConnector()->id;

        $id_record = $import->id;

        Uploads::upload($_FILES['file'], [
            'id_module' => $id_module,
            'id_record' => $id_record,
            'id_adapter' => $id_adapter,
        ]);

        break;

    case 'example':
        $id_import = filter('id_import');

        $import = Import::find($id_import);
        $import_manager = $import->class;

        if (!empty($import_manager)) {
            try {
                // Generazione percorso
                $file = $modulo_import->upload_directory.'/example-'.strtolower((string) $import->getTranslation('title')).'.csv';
                $filepath = base_dir().'/'.$file;

                // Generazione del file
                $import_manager::createExample($filepath);

                // Crea un record nella tabella zz_files senza usare la colonna id_category
                $database = database();
                $database->query('INSERT INTO `zz_files` (`name`, `original`, `id_module`, `id_plugin`, `id_record`, `size`, `id_adapter`, `filename`, `created_by`, `created_at`)
                    VALUES ("example-'.strtolower((string) $import->getTranslation('title')).'", "example-'.strtolower((string) $import->getTranslation('title')).'.csv", '.$id_module.', NULL, '.$id_import.', '.filesize($filepath).', 1, "'.basename($filepath).'", '.auth_osm()->getUser()->id.', NOW())');

                echo base_path_osm().'/'.$file;
            } catch (Exception $e) {
                // Log dell'errore
                error_log('Errore durante la generazione del file di esempio: '.$e->getMessage());

                // Risposta di errore
                echo json_encode([
                    'error' => true,
                    'message' => tr('Si è verificato un errore durante la generazione del file di esempio: ').$e->getMessage(),
                ]);
            }
        }

        break;

    case 'import':
        try {
            // Individuazione del modulo
            $import = Import::find($id_record);
            $import_manager = $import->class;

            // Dati indicati
            $include_first_row = post('include_first_row');
            $fields = (array) post('fields');
            $page = post('page');

            $limit = 500;

            // Inizializzazione del lettore CSV
            $filepath = base_dir().'/files/'.$record->directory.'/'.$record->filename;
            $csv = new $import_manager($filepath);
            foreach ($fields as $key => $value) {
                $available_fields = $csv->getAvailableFields();
                $field_index = null;
                foreach ($available_fields as $index => $field_info) {
                    if ($field_info['field'] === $value) {
                        $field_index = $index;
                        break;
                    }
                }
                if ($field_index !== null) {
                    $csv->setColumnAssociation($key, $field_index);
                }
            }

            $offset = isset($page) ? $page * $limit : 0;

            if ($offset == 0 && empty($include_first_row)) {
                ++$offset;
            }

            // Gestione automatica dei valori convertiti
            $primary_key = post('primary_key');
            if (!empty($primary_key)) {
                $available_fields = $csv->getAvailableFields();
                $primary_key_index = null;
                foreach ($available_fields as $index => $field_info) {
                    if ($field_info['field'] === $primary_key) {
                        $primary_key_index = $index;
                        break;
                    }
                }
                if ($primary_key_index !== null) {
                    $csv->setPrimaryKey($primary_key_index);
                }
            }

            // Verifica che tutti i campi obbligatori siano mappati
            if (!isset($page) || empty($page)) {
                if (!$csv->areRequiredFieldsMapped()) {
                    $is_anagrafica_import = str_contains($csv::class, 'Anagrafiche');
                    $error_message = $is_anagrafica_import ?
                        tr('Alcuni campi obbligatori non sono stati mappati. La ragione sociale è obbligatoria e almeno uno tra telefono e partita IVA deve essere mappato.') :
                        tr('Alcuni campi obbligatori non sono stati mappati');

                    echo json_encode([
                        'error' => true,
                        'message' => $error_message,
                    ]);
                    exit;
                }

                // Operazioni di inizializzazione per l'importazione
                $csv->init();
            }

            $result = $csv->importRows($offset, $limit, post('update_record'), post('add_record'));
            $more = $result['total'] == $limit;

            if (!isset($result['imported'])) {
                echo json_encode([
                    'error' => true,
                    'message' => tr('Errore durante l\'importazione: il risultato non contiene i dati attesi.'),
                ]);
                exit;
            }

            // Salva i record falliti in un file CSV durante ogni batch (accumulativo)
            $failed_records_path = '';
            $failed_records_filename = post('failed_records_filename');

            if (!empty($csv->getFailedRecords())) {
                // Crea la directory per le anomalie se non esiste
                $anomalie_dir = base_dir().'/files/anomalie';
                if (!is_dir($anomalie_dir)) {
                    mkdir($anomalie_dir, 0777, true);
                }

                // Usa il nome del file passato dal batch precedente, oppure generane uno nuovo
                if (empty($failed_records_filename)) {
                    $failed_records_filename = 'anomalie_'.date('Ymd_His').'_'.basename($filepath);
                }

                $failed_records_path = $anomalie_dir.'/'.$failed_records_filename;

                // Salva i record falliti con errori specifici se il metodo è disponibile
                // Il file sarà accumulativo grazie alla modalità append in saveFailedRecordsWithErrors
                if (method_exists($csv, 'saveFailedRecordsWithErrors')) {
                    $csv->saveFailedRecordsWithErrors($failed_records_path);
                } else {
                    $csv->saveFailedRecords($failed_records_path);
                }

                // Converti il percorso assoluto in relativo per l'URL
                $failed_records_path = 'files/anomalie/'.$failed_records_filename;
            }

            // Operazioni di finalizzazione per l'importazione
            if (!$more) {
                $csv->complete();

                echo json_encode([
                    'more' => $more,
                    'imported' => $result['imported'],
                    'failed' => $result['failed'],
                    'total' => $result['total'],
                    'failed_records_path' => $failed_records_path,
                ]);
            } else {
                echo json_encode([
                    'more' => $more,
                    'imported' => $result['imported'],
                    'failed' => $result['failed'],
                    'total' => $result['total'],
                    'failed_records_filename' => $failed_records_filename,
                ]);
            }
        } catch (Exception $e) {
            error_log('Errore durante importazione CSV: '.$e->getMessage()."\n".$e->getTraceAsString());
            echo json_encode([
                'error' => true,
                'message' => tr('Si è verificato un errore durante l\'importazione: ').$e->getMessage(),
            ]);
        }

        break;
}
