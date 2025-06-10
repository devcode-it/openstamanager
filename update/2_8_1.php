<?php

include __DIR__.'/../config.inc.php';

$id_categoria = $dbo->fetchOne('SELECT `id` FROM `zz_files_categories` WHERE `name` = \'fattura elettronica\'');
if (empty($id_categoria)) {
    $dbo->query('INSERT INTO `zz_files_categories` (`name`) VALUES (\'Fattura elettronica\')');
}
