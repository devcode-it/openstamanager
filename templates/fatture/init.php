<?php

include_once __DIR__.'/../../core.php';

// Lettura info fattura
$records = $dbo->fetchArray('SELECT *,
    (SELECT descrizione FROM co_statidocumento WHERE id=idstatodocumento) AS stato_doc,
    (SELECT descrizione FROM co_tipidocumento WHERE id=idtipodocumento) AS tipo_doc,
    (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento,
    (SELECT dir FROM co_tipidocumento WHERE id=idtipodocumento) AS dir,
    (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causalet,
    (SELECT descrizione FROM dt_porto WHERE id=idporto) AS porto,
    (SELECT descrizione FROM dt_aspettobeni WHERE id=idaspettobeni) AS aspettobeni,
    (SELECT descrizione FROM dt_spedizione WHERE id=idspedizione) AS spedizione,
    (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idvettore) AS vettore
FROM co_documenti WHERE id='.prepare($id_record));

$records[0]['rivalsainps'] = floatval($records[0]['rivalsainps']);
$records[0]['ritenutaacconto'] = floatval($records[0]['ritenutaacconto']);
$records[0]['bollo'] = floatval($records[0]['bollo']);

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

// Fix per le fattura accompagnatorie
$fattura_accompagnatoria = ($records[0]['tipo_doc'] == 'Fattura accompagnatoria di vendita');
$tipo_doc = ($fattura_accompagnatoria) ? 'Fattura accompagnatoria di vendita' : $tipo_doc;

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).(!empty($records[0]['idsede']) ? ' AND id='.prepare($records[0]['idsede']) : ''));

$destinazione = '';
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

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => Stringy\Stringy::create($tipo_doc)->toUpperCase(),
    'numero_doc' => $numero,
    'data' => Translator::dateToLocale($records[0]['data']),
    'pagamento' => $records[0]['tipo_pagamento'],
    'c_destinazione' => $destinazione,
    'aspettobeni' => $records[0]['aspettobeni'],
    'causalet' => $records[0]['causalet'],
    'porto' => $records[0]['porto'],
    'n_colli' => !empty($records[0]['n_colli']) ? $records[0]['n_colli'] : '',
    'spedizione' => $records[0]['spedizione'],
    'vettore' => $records[0]['vettore'],
];

// Controllo sui permessi
if ($id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) {
    die(tr('Non hai i permessi per questa stampa!'));
}
