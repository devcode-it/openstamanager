<?php

use Util\Ini;

include_once __DIR__.'/../../core.php';

function crea_form_componente($contenuto)
{
    $fields = Ini::getFields($contenuto);
    $title = array_shift($fields);

    foreach ($fields as $key => $value) {
        $fields[$key] = '<div class="col-md-4">'.$value.'</div>';
    }

    echo $title.'
    <div class="row">
        '.implode(PHP_EOL, $fields).'
        <script>restart_inputs()</script>
    </div>';
}
