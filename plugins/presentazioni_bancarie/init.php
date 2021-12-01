<?php

include_once __DIR__.'/../../core.php';

$records = get('records', true);
$records = $records ? explode(',', $records) : [];
$record = $records[0] ?: null;
