<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'aggiorna-listino':
        $sconto = post('sconto');
        foreach ($id_records as $id_record) {
            $dbo->query('UPDATE mg_prezzi_articoli SET sconto_percentuale='.prepare($sconto).' WHERE id='.prepare($id_record));
        }
        flash()->info(tr('Sconti modificati correttamente!'));
    break;
}

return [
    'aggiorna-listino' => [
        'text' => '<span>'.tr('Modifica sconto').'</span>',
        'data' => [
            'title' => tr('Inserisci lo sconto per questi articoli'),
            'msg' => '{[ "type": "text", "label": "<small>'.tr('Nuovo sconto').'</small>","icon-after":"%", "name": "sconto" ]}',
            'button' => tr('Modifica'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],
];
