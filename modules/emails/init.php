<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM em_templates WHERE id='.prepare($id_record).' AND deleted_at IS NULL');
}
