<?php

use Models\Module;

// Migrazione immagini da zz_marche a zz_files
$database = database();
$id_module = Module::where('name', 'Marche')->first()->id;

if (!empty($id_module)) {
    $marche = $database->fetchArray('SELECT `id`, `immagine` FROM `zz_marche` WHERE `immagine` IS NOT NULL AND `immagine` != ""');

    foreach ($marche as $marca) {
        $file_exists = $database->selectOne('zz_files', ['id'], [
            'id_module' => $id_module,
            'id_record' => $marca['id'],
            'filename' => $marca['immagine'],
        ]);

        if (empty($file_exists)) {
            $database->insert('zz_files', [
                'id_module' => $id_module,
                'id_record' => $marca['id'],
                'name' => 'Immagine',
                'filename' => $marca['immagine'],
                'original' => $marca['immagine'],
                'key' => 'cover',
            ]);
        } else {
            $database->update('zz_files', [
                'key' => 'cover',
            ], [
                'id_module' => $id_module,
                'id_record' => $marca['id'],
                'filename' => $marca['immagine'],
            ]);
        }
    }

    $database->query('ALTER TABLE `zz_marche` DROP COLUMN `immagine`');
}

// Migrazione firme da in_interventi a zz_files
$id_module = Module::where('name', 'Interventi')->first()->id;

if (!empty($id_module)) {
    // Recupera tutti i file di firma con key nel formato signature_nome_data
    $files_firma = $database->fetchArray('SELECT `id`, `id_record`, `key` FROM `zz_files` WHERE `id_module` = ? AND `key` LIKE "signature_%"', [$id_module]);

    foreach ($files_firma as $file) {
        // Estrae nome e data dalla key (formato: signature_nome_data)
        $key_parts = explode('_', $file['key']);
        if (count($key_parts) >= 3) {
            // Rimuove 'signature' dall'inizio
            array_shift($key_parts);

            // L'ultimo elemento è la data, tutto il resto è il nome
            $data_firma = array_pop($key_parts);
            $firma_nome = implode('_', $key_parts);

            // Aggiorna l'intervento con i dati estratti dalla key
            $database->update('in_interventi', [
                'firma_data' => $data_firma,
                'firma_nome' => $firma_nome,
            ], ['id' => $file['id_record']]);

            // Aggiorna la key del file a 'signature'
            $database->update('zz_files', [
                'key' => 'signature',
            ], ['id' => $file['id']]);
        }
    }
}