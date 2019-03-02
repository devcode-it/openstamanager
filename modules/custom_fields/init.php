<?php

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM zz_fields WHERE id='.prepare($id_record));
}

// TODO: prevedere un utilizzo pratico del campo options
