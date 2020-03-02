<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $print->title = post('title');
        $print->filename = post('filename');
        $print->options = post('options');
        $print->save();

        flash()->info(tr('Modifiche salvate correttamente'));

        break;
}
