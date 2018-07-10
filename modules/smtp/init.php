<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM zz_smtps WHERE id='.prepare($id_record).' AND deleted = 0');
}
