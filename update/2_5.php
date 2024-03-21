<?php
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
include __DIR__.'/../config.inc.php';

// File e cartelle deprecate
$files = [
    'assets/src/js/wacom/sigCaptDialog/libs/',
    'modules/impianti/plugins/',
    'modules/voci_servizio/'
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

$tables = [
    'an_provenienze_lang',
    'an_relazioni_lang',
    'an_settori_lang',
    'an_tipianagrafiche_lang',
    'co_iva_lang',
    'co_pagamenti_lang',
    'co_staticontratti_lang',
    'co_statidocumento_lang',
    'co_statipreventivi_lang',
    'co_tipidocumento_lang',
    'co_tipi_scadenze_lang',
    'do_categorie_lang',
    'dt_aspettobeni_lang',
    'dt_causalet_lang',
    'dt_porto_lang',
    'dt_spedizione_lang',
    'dt_statiddt_lang',
    'dt_tipiddt_lang',
    'em_lists_lang',
    'em_templates_lang',
    'in_fasceorarie_lang',
    'in_statiintervento_lang',
    'in_tipiintervento_lang',
    'mg_articoli_lang',
    'mg_attributi_lang',
    'mg_categorie_lang',
    'mg_causali_movimenti_lang',
    'mg_combinazioni_lang',
    'or_statiordine_lang',
    'or_tipiordine_lang',
    'zz_cache_lang',
    'zz_currencies_lang',
    'zz_groups_lang',
    'zz_group_module_lang',
    'zz_hooks_lang',
    'zz_imports_lang',
    'zz_modules_lang',
    'zz_plugins_lang',
    'zz_prints_lang',
    'zz_segments_lang',
    'zz_settings_lang',
    'zz_tasks_lang',
    'zz_views_lang',
    'zz_widgets_lang',
];

foreach ($tables as $table) {
    $database->query('CREATE TEMPORARY TABLE `tmp` SELECT * FROM '.$table);
    $database->query('ALTER TABLE `tmp` DROP `id`');
    $database->query('UPDATE `tmp` SET `id_lang` = 2');
    $database->query('INSERT INTO '.$table.' SELECT NULL,tmp. * FROM tmp');
    $database->query('DROP TEMPORARY TABLE tmp');
}
