<?php

include_once __DIR__.'/../../core.php';
use Modules\Newsletter\Lista;

switch (post('op')) {
    case 'aggiorna-liste':

        foreach ($id_records as $id) {

                $lista = Lista::find($id);
        
                $query =  $lista->query;
                if (check_query($query)) {
                    $lista->query = html_entity_decode($query);
                }
        
                $lista->save();
        }

        flash()->info(tr('Liste aggiornate!'));

        break;
}


$operations['aggiorna-liste'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna liste').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero aggiornare le liste dei destinatari?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];


return $operations;