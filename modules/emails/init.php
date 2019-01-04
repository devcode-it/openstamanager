<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM zz_emails WHERE id='.prepare($id_record).' AND deleted_at IS NULL');
}
