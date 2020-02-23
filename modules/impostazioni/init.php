<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM `zz_settings` WHERE `sezione` = (SELECT sezione FROM `zz_settings` WHERE `id` = '.prepare($id_record).') ORDER BY `order`');
}
