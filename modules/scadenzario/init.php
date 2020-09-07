<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM co_scadenziario WHERE id = '.prepare($id_record));
    $documento = Fattura::find($record['iddocumento']);

    // Scelgo la query in base alla scadenza
    if (!empty($documento)) {
        $scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = '.prepare($documento->id).' ORDER BY scadenza ASC');
        $totale_da_pagare = $documento->netto;
    } else {
        $scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE id = '.prepare($id_record).' ORDER BY scadenza ASC');
        $totale_da_pagare = sum(array_column($scadenze, 'da_pagare'));
    }

    if ($scadenze[0]['id'] != $id_record) {
        redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$scadenze[0]['id']);
    }
}
