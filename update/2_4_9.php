<?php

include_once DOCROOT.'/modules/fatture/modutil.php';

// Fatture di vendita (direzione "entrata")
$results = $dbo->fetchArray("SELECT co_documenti.id, co_statidocumento.descrizione AS stato_fattura
FROM co_documenti
    INNER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id 
    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id 
WHERE co_statidocumento.dir = 'entrata' AND co_statidocumento.descrizione IN ('Emessa', 'Parzialmente pagato', 'Pagato', 'Bozza', 'Annullata')");

foreach ($results as $result) {
    $scadenze = $dbo->fetchArray('SELECT id, da_pagare, pagato, scadenza FROM co_scadenziario WHERE iddocumento = '.prepare($result['id']));

    // Se esiste la scadenza la elimino
    if (!empty($scadenze)) {
        elimina_scadenza($result['id']);
    }

    $is_pagato = null;
    if ($result['stato_fattura'] == 'Pagato') {
        $is_pagato = true;
    } elseif (in_array($result['stato_fattura'], ['Emessa', 'Parzialmente pagato'])) {
        $is_pagato = false;
    }

    // Aggiungo la scadenza e la segno eventualmente come pagata
    if (isset($is_pagato)) {
        aggiungi_scadenza($result['id'], null, $is_pagato);
    }

    if (!empty($scadenze) && $result['stato_fattura'] == 'Parzialmente pagato') {
        foreach ($scadenza as $scadenze) {
            $dbo->query('UPDATE co_scadenziario SET pagato = '.prepare($scadenza['pagato']).' WHERE scadenza = '.prepare($scadenza['scadenza']).' AND iddocumento = '.prepare($result['id']));
        }
    }
}

// File e cartelle deprecate
$files = [
    'plugins/xml/AT_v1.0.xml',
    'plugins/xml/DT_v1.0.xml',
    'plugins/xml/EC_v1.0.xml',
    'plugins/xml/MC_v1.0.xml',
    'plugins/xml/MT_v1.0.xml',
    'plugins/xml/NE_v1.0.xml',
    'plugins/xml/NS_v1.0.xml',
    'plugins/xml/RC_v1.0.xml',
    'plugins/xml/SE_v1.0.xml',
    'plugins/exportFE/view.php',
    'plugins/exportFE/src/stylesheet-1.2.1.xsl',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
