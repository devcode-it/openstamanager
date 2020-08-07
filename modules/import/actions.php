<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $modulo_selezionato = Modules::get(filter('module'));
        $id_record = $modulo_selezionato->id;

        $upload = Uploads::upload($_FILES['file'], [
            'id_module' => $modulo_import->id,
            'id_record' => $id_record,
        ]);
        break;

    case 'example':
        $module = filter('module');
        $modulo_selezionato = Modules::get(filter('module'));
        $import_selezionato = $moduli_disponibili[$module];

        if (!empty($import_selezionato)) {
            // Generazione percorso
            $file = $modulo_selezionato->upload_directory.'/example-'.strtolower($modulo_selezionato->title).'.csv';
            $filepath = DOCROOT.'/'.$file;

            // Generazione del file
            $import_selezionato::createExample($filepath);

            echo ROOTDIR.'/'.$file;
        }

        break;

    case 'import':
        // Individuazione del modulo
        $modulo_selezionato = Modules::get($id_record);
        $import_selezionato = $moduli_disponibili[$modulo_selezionato->name];

        // Dati indicati
        $include_first_row = post('include_first_row');
        $fields = (array) post('fields');
        $page = post('page');

        $limit = 500;

        // Inizializzazione del lettore CSV
        $csv = new $import_selezionato($record->filepath);
        foreach ($fields as $key => $value) {
            $csv->setColumnAssociation($key, $value);
        }

        // Generazione offset sulla base della pagina
        $offset = isset($page) ? $page * $limit : 0;

        // Ignora la prima riga se composta da header
        if ($offset == 0 && empty($include_first_row)) {
            ++$offset;
        }

        // Gestione automatica dei valori convertiti
        $primary_key = post('primary_key');
        $csv->setPrimaryKey($primary_key);

        $count = $csv->importRows($offset, $limit);
        $more = $count == $limit;

        echo json_encode([
            'more' => $more,
            'count' => $count,
        ]);

        break;
}
