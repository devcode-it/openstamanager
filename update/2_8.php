<?php

include __DIR__.'/../config.inc.php';

// Spostamento backup
$directory = 'backup/';
$files = glob($directory.'*.{zip}', GLOB_BRACE) ?: [];
$new_folder = 'files/backups/';
directory($new_folder);

foreach ($files as $file) {
    $filename = basename($file);
    rename($file, $new_folder.$filename);
}

// File e cartelle deprecate
$files = [
    'templates/bilancio/settings.php',
    'templates/contratti/settings.php',
    'templates/ddt/settings.php',
    'templates/libro_giornale/settings.php',
    'templates/magazzino_cespiti/settings.php',
    'templates/magazzino_inventario/settings.php',
    'templates/partitario_mastrino/settings.php',
    'templates/preventivi/settings.php',
    'templates/prima_nota/settings.php',
    'templates/scadenzario/settings.php',
    'backup/',
    'modules/marchi',
    'modules/impianti_marche',
    'modules/categorie_articoli',
    'modules/categorie_impianti',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

$module = Models\Module::where('name', 'Fatture di acquisto')->first();
$directory = 'files/fatture/vendite/';
$files = glob($directory.'*.{xml,pdf}', GLOB_BRACE) ?: [];
$new_folder = 'files/'.$module->attachments_directory.'/';
directory($new_folder);

$attachments = database()->fetchArray('SELECT `filename` FROM `zz_files` WHERE `id_module` = '.$module->id) ?: [];
$attachments_filenames = !empty($attachments) ? array_column($attachments, 'filename') : [];

foreach ($files as $file) {
    $filename = basename($file);
    // Sposta solo i file che hanno un record corrispondente nella tabella zz_files
    if (in_array($filename, $attachments_filenames)) {
        echo 'Spostamento file: '.$file.' -> '.$new_folder.$filename."\n";
        rename($file, $new_folder.$filename);
    }
}

$module = Models\Module::where('name', 'Fatture di vendita')->first();
$directory = 'files/fatture/';
$files = glob($directory.'*.{xml,pdf}', GLOB_BRACE) ?: [];
$new_folder = 'files/'.$module->attachments_directory.'/';
directory($new_folder);

$attachments = database()->fetchArray('SELECT `filename` FROM `zz_files` WHERE `id_module` = '.$module->id) ?: [];
$attachments_filenames = !empty($attachments) ? array_column($attachments, 'filename') : [];

foreach ($files as $file) {
    $filename = basename($file);
    // Sposta solo i file che hanno un record corrispondente nella tabella zz_files
    if (in_array($filename, $attachments_filenames)) {
        echo 'Spostamento file: '.$file.' -> '.$new_folder.$filename."\n";
        rename($file, $new_folder.$filename);
    }
}

// Verifica presenza conti
$conti_speciali_livello2 = [
    'Conti transitori' => [
        'Iva su vendite',
        'Iva su acquisti',
        'Iva indetraibile',
    ],
    'Conti compensativi' => [
        'Compensazione per autofattura',
    ],
    'Perdite e profitti' => [],
];

$conti_speciali_livello3 = [
    'Cassa e banca',
    'Crediti clienti e crediti diversi',
    'Debiti fornitori e debiti diversi',
];

foreach ($conti_speciali_livello2 as $conto_livello2 => $sottoconti) {
    $conto2 = database()->fetchOne('SELECT id FROM co_pianodeiconti2 WHERE descrizione = '.prepare($conto_livello2));

    if (empty($conto2)) {
        $conto1 = database()->fetchOne('SELECT id FROM co_pianodeiconti1 WHERE descrizione = "Patrimoniale"');

        if (!empty($conto1)) {
            $max_numero = database()->fetchOne('SELECT MAX(CAST(numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti2 WHERE idpianodeiconti1 = '.prepare($conto1['id']));
            $nuovo_numero = $max_numero ? $max_numero['max_numero'] + 1 : 1;
            $nuovo_numero = str_pad($nuovo_numero, 6, '0', STR_PAD_LEFT);
            $id_conto2 = database()->query('INSERT INTO co_pianodeiconti2 (numero, descrizione, idpianodeiconti1, dir) VALUES ('.prepare($nuovo_numero).', '.prepare($conto_livello2).', '.prepare($conto1['id']).', "entrata/uscita")');
            $conto2 = ['id' => database()->lastInsertedID()];
        }
    }

    foreach ($sottoconti as $sottoconto) {
        $conto3 = database()->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = '.prepare($sottoconto).' AND idpianodeiconti2 = '.prepare($conto2['id']));

        if (empty($conto3)) {
            $max_numero = database()->fetchOne('SELECT MAX(CAST(numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 WHERE idpianodeiconti2 = '.prepare($conto2['id']));
            $nuovo_numero = $max_numero ? $max_numero['max_numero'] + 1 : 1;
            $nuovo_numero = str_pad($nuovo_numero, 6, '0', STR_PAD_LEFT);
            database()->query('INSERT INTO co_pianodeiconti3 (numero, descrizione, idpianodeiconti2, dir, percentuale_deducibile) VALUES ('.prepare($nuovo_numero).', '.prepare($sottoconto).', '.prepare($conto2['id']).', "entrata/uscita", 100)');
            echo 'Creato conto di terzo livello: '.$sottoconto.' (numero: '.$nuovo_numero.")\n";
        }
    }
}

foreach ($conti_speciali_livello3 as $conto_livello3) {
    $conto3 = database()->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = '.prepare($conto_livello3));

    if (empty($conto3)) {
        $conto2 = database()->fetchOne('SELECT id FROM co_pianodeiconti2 WHERE idpianodeiconti1 = (SELECT id FROM co_pianodeiconti1 WHERE descrizione = "Patrimoniale") LIMIT 1');

        if (!empty($conto2)) {
            $max_numero = database()->fetchOne('SELECT MAX(CAST(numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 WHERE idpianodeiconti2 = '.prepare($conto2['id']));
            $nuovo_numero = $max_numero ? $max_numero['max_numero'] + 1 : 1;
            $nuovo_numero = str_pad($nuovo_numero, 6, '0', STR_PAD_LEFT);
            database()->query('INSERT INTO co_pianodeiconti3 (numero, descrizione, idpianodeiconti2, dir, percentuale_deducibile) VALUES ('.prepare($nuovo_numero).', '.prepare($conto_livello3).', '.prepare($conto2['id']).', "entrata/uscita", 100)');
            echo 'Creato conto di terzo livello speciale: '.$conto_livello3.' (numero: '.$nuovo_numero.")\n";
        }
    }
}

// Creazione record categorie allegati
$categories = $dbo->fetchArray('SELECT DISTINCT(BINARY `category`) AS `category` FROM `zz_files` ORDER BY `category`');
$categories = array_clean(array_column($categories, 'category'));
foreach ($categories as $categoria) {
    $dbo->insert('zz_files_categories', ['name' => $categoria]);
    $id = $dbo->lastInsertedId();
    $dbo->query('UPDATE `zz_files` SET `id_category` = '.$id.' WHERE `category` = '.prepare($categoria));
}
$dbo->query('ALTER TABLE `zz_files` DROP `category`');
