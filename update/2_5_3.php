<?php

include_once __DIR__.'/core.php';

// Controllo se è presente il campo nome in zz_groups
$has_nome = database()->columnExists('zz_groups', 'nome');
$has_name = database()->columnExists('zz_groups', 'name');

if ($has_name && $has_nome) {
    $database->query('ALTER TABLE `zz_groups` DROP `name`');
}
