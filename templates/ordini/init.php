<?php

include_once __DIR__.'/../../core.php';

use Modules\Ordini\Ordine;

$documento = Ordine::find($id_record);

$id_cliente = $documento['idanagrafica'];
$id_sede = $documento['idsede'];

$numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
$pagamento = $dbo->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.prepare($documento->idpagamento));

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => Stringy\Stringy::create($documento->tipo->descrizione)->toUpperCase(),
    'numero' => $numero,
    'data' => Translator::dateToLocale($documento['data']),
    'pagamento' => $pagamento['descrizione'],
];
