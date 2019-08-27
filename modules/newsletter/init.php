<?php

use Modules\Newsletter\Newsletter;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $newsletter = Newsletter::find($id_record);

    $record = $newsletter->toArray();
}
