<?php

use Modules\Aggiornamenti\Controlli\TabelleLanguage;
use Models\Module;

$controllo = new TabelleLanguage();
$controllo->check();
$controllo->solveGlobal();

// Migrazione immagini da mg_articoli a zz_files
$database = database();
$id_module = Module::where('name', 'Articoli')->first()->id;

if (!empty($id_module)) {
    $articoli = $database->fetchArray('SELECT `id`, `immagine` FROM `mg_articoli` WHERE `immagine` IS NOT NULL');

    foreach ($articoli as $articolo) {
        $file_exists = $database->selectOne('zz_files', ['id'], [
            'id_module' => $id_module,
            'id_record' => $articolo['id'],
            'filename' => $articolo['immagine'],
        ]);

        if (empty($file_exists)) {
            $database->insert('zz_files', [
                'id_module' => $id_module,
                'id_record' => $articolo['id'],
                'nome' => 'Immagine',
                'filename' => $articolo['immagine'],
                'original' => $articolo['immagine'],
                'key' => 'cover',
            ]);
        } else {
            $database->update('zz_files', [
                'key' => 'cover',
            ], [
                'id_module' => $id_module,
                'id_record' => $articolo['id'],
                'filename' => $articolo['immagine'],
            ]);
        }
    }

    $database->query('ALTER TABLE `mg_articoli` DROP COLUMN `immagine`');
}
