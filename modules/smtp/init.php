<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM zz_smtps WHERE id='.prepare($id_record).' AND deleted_at IS NULL');
}
