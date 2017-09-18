<?php

include_once __DIR__.'/../../core.php';

$records = $dbo->fetchArray('SELECT * FROM an_referenti WHERE id='.prepare($id_record));
