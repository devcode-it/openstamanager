<?php

include_once __DIR__.'/../../core.php';

// Lettura info fattura
$records = $dbo->fetchArray('SELECT *,
    (SELECT descrizione FROM co_statidocumento WHERE id=idstatodocumento) AS stato_doc,
    (SELECT descrizione FROM co_tipidocumento WHERE id=idtipodocumento) AS tipo_doc,
    (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento,
    (SELECT dir FROM co_tipidocumento WHERE id=idtipodocumento) AS dir
FROM co_documenti WHERE id='.prepare($id_record));

$module_name = ($records[0]['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';

$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];

$tipo_doc = $records[0]['tipo_doc'];
if ($records[0]['stato_doc'] != 'Bozza') {
    $numero = !empty($records[0]['numero_esterno']) ? $records[0]['numero_esterno'] : $records[0]['numero'];
} else {
    $tipo_doc = tr('Fattura pro forma');
    $numero = 'PRO-'.$records[0]['numero'];
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($records[0]['idsede'])) {
    $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).' AND id='.prepare($records[0]['idsede']));

    if (!empty($rsd[0]['indirizzo'])) {
        $destinazione .= $rsd[0]['indirizzo'].'<br/>';
    }
    if (!empty($rsd[0]['indirizzo2'])) {
        $destinazione .= $rsd[0]['indirizzo2'].'<br/>';
    }
    if (!empty($rsd[0]['cap'])) {
        $destinazione .= $rsd[0]['cap'].' ';
    }
    if (!empty($rsd[0]['citta'])) {
        $destinazione .= $rsd[0]['citta'];
    }
    if (!empty($rsd[0]['provincia'])) {
        $destinazione .= ' ('.$rsd[0]['provincia'].')';
    }
}

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => Stringy\Stringy::create($tipo_doc)->toUpperCase(),
    'numero_doc' => $numero,
    'data' => Translator::dateToLocale($records[0]['data']),
    'pagamento' => $records[0]['tipo_pagamento'],
    'c_destinazione' => $destinazione,
];

// Controllo sui permessi
if ($id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) {
    die(tr('Non hai i permessi per questa stampa!'));
}
