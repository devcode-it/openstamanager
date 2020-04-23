<?php

include_once __DIR__.'/../../core.php';

use Modules\Checklists\Checklist;

if (isset($id_record)) {
    $record = Checklist::find($id_record);
}
