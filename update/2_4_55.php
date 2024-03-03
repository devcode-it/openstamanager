<?php
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
include __DIR__.'/../config.inc.php';

// File e cartelle deprecate
$files = [
    'assets/src/js/wacom/sigCaptDialog/libs/',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

/* Fix per file sql di update aggiornato dopo rilascio 2.4.35 */
$has_column = null;
$col_righe = $database->fetchArray('SHOW COLUMNS FROM `zz_groups`');
$has_column = array_search('id_module_start', array_column($col_righe, 'Field'));
if (empty($has_column)) {
    $database->query('ALTER TABLE `zz_groups` ADD `id_module_start` INT NULL AFTER `editable`');
}


if ($backup_dir){
    /* Rinomino i file zip all'interno della cartella di backup, aggiungendo "FULL" alla fine del nome*/
    $filesystem = new SymfonyFilesystem();
    //glob viene utilizzata per ottenere la lista dei file zip all'interno della cartella $backup_dir.
    $files = glob($backup_dir . '/*.zip');

    foreach ($files as $file) {
        $fileName = basename($file);
        
        if (strpos($fileName, 'FULL') === false) {
            $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . ' FULL.zip';
            $newFilePath = $backup_dir . '/' . $newFileName;
            
            $filesystem->rename($file, $newFilePath);
        }
    }
}else{
    echo "Impossibile completare l'aggiornamento. Variabile <b>$backup_dir</b> non impostata.\n";
}