<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Preventivi';

// Lettura info fattura
$records = $dbo->fetchArray('SELECT *, (SELECT descrizione FROM or_tipiordine WHERE or_tipiordine.id=idtipoordine) AS tipo_doc, (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento FROM or_ordini WHERE id='.prepare($idordine));

$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];

$numero_ord = $records[0]['numero'];
$numero = !empty($records[0]['numero_esterno']) ?$records[0]['numero_esterno'] : $records[0]['numero'];

// Sostituzioni specifiche
$custom = [
    'numero_doc' => $numero,
    'data' => Translator::dateToLocale($records[0]['data']),
    'pagamento' => $records[0]['tipo_pagamento'],
];

$mostra_prezzi = get_var('Stampa i prezzi sugli ordini');
