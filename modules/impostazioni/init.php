<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM `zz_settings` WHERE `sezione`=(SELECT sezione FROM `zz_settings` WHERE `idimpostazione`='.prepare($id_record).") AND `editable`='1'");
}
