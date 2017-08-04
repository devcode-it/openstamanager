<?php

include_once __DIR__.'/../../core.php';

function genera_form_componente($contenuto)
{
    $fields = \Util\Ini::getFields($contenuto);
    $title = array_shift($fields);

    foreach ($fields as $key => $value) {
        $fields[$key] = '<div class="col-md-4">'.$value.'</div>';
    }

    echo $title.PHP_EOL.implode(PHP_EOL, $fields).PHP_EOL.'<script src="'.ROOTDIR.'/lib/init.js"></script>';
}
