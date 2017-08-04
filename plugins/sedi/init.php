<?php

include_once __DIR__.'/../../core.php';

$records = $dbo->fetchArray('SELECT * FROM an_sedi WHERE an_sedi.id='.prepare($id_record).' ORDER BY an_sedi.id DESC');
