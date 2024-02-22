<?php

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
