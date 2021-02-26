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

use Modules\Importazione\Import;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $id_import = filter('id_import');
        $import = Import::find($id_import);

        $id_record = $import->id;

        $upload = Uploads::upload($_FILES['file'], [
            'id_module' => $import->getModule()->id,
            'id_record' => $id_record,
        ]);
        break;

    case 'example':
        $id_import = filter('id_import');

        $import = Import::find($id_import);
        $import_manager = $import->class;

        $modulo_collegato = $import->moduloCollegato;
        if (!empty($import_manager)) {
            // Generazione percorso
            $file = $modulo_collegato->upload_directory.'/example-'.strtolower($modulo_collegato->title).'.csv';
            $filepath = base_dir().'/'.$file;

            // Generazione del file
            $import_manager::createExample($filepath);

            echo base_path().'/'.$file;
        }

        break;

    case 'import':
        // Individuazione del modulo
        $import = Import::find($id_record);
        $import_manager = $import->class;

        // Dati indicati
        $include_first_row = post('include_first_row');
        $fields = (array) post('fields');
        $page = post('page');

        $limit = 500;

        // Inizializzazione del lettore CSV
        $csv = new $import_manager($record->filepath);
        foreach ($fields as $key => $value) {
            $csv->setColumnAssociation($key, $value - 1);
        }

        // Generazione offset sulla base della pagina
        $offset = isset($page) ? $page * $limit : 0;

        // Ignora la prima riga se composta da header
        if ($offset == 0 && empty($include_first_row)) {
            ++$offset;
        }

        // Gestione automatica dei valori convertiti
        $primary_key = post('primary_key');
        $csv->setPrimaryKey($primary_key - 1);

        // Operazioni di inizializzazione per l'importazione
        if (!isset($page) || empty($page)) {
            $csv->init();
        }

        $count = $csv->importRows($offset, $limit);
        $more = $count == $limit;

        // Operazioni di finalizzazione per l'importazione
        if (!$more) {
            $csv->complete();
        }

        echo json_encode([
            'more' => $more,
            'count' => $count,
        ]);

        break;
}
