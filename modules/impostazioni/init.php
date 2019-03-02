<?php

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM `zz_settings` WHERE `sezione` = (SELECT sezione FROM `zz_settings` WHERE `id` = '.prepare($id_record).') AND `editable` = 1 ORDER BY `order`');
}
