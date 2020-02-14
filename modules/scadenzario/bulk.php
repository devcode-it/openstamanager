<?php

include_once __DIR__.'/../../core.php';

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'scadenzario',
        'url' => $rootdir.'/add.php?id_module='.Modules::get('Prima nota')['id'],
    ],
];

return $operations;
