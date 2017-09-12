<?php

include_once __DIR__.'/../../core.php';

// Lettura info ddt
$q = 'SELECT *, (SELECT dir FROM dt_tipiddt WHERE id=idtipoddt) AS dir, (SELECT descrizione FROM dt_tipiddt WHERE id=idtipoddt) AS tipo_doc, (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causalet, (SELECT descrizione FROM dt_porto WHERE id=idporto) AS porto, (SELECT descrizione FROM dt_aspettobeni WHERE id=idaspettobeni) AS aspettobeni, (SELECT descrizione FROM dt_spedizione WHERE id=idspedizione) AS spedizione, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idvettore) AS vettore FROM dt_ddt WHERE id='.prepare($idddt);
$rs = $dbo->fetchArray($q);

$module_name = ($rs[0]['dir'] == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';

$id_cliente = $rs[0]['idanagrafica'];
$id_sede = $rs[0]['idsede'];

$numero = !empty($rs[0]['numero_esterno']) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];

if (empty($rs[0]['numero_esterno'])) {
    $numero = 'pro-forma '.$numero;
    $tipo_doc = 'DDT PRO-FORMA';
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($rs[0]['idsede'])) {
    $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).' AND id='.prepare($rs[0]['idsede']));

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
    'tipo_doc' => strtoupper($tipo_doc),
    'numero_doc' => $numero,
    'data' => Translator::numberToLocale($rs[0]['data']),
    'pagamento' => $rs[0]['tipo_pagamento'],
    'c_destinazione' => $destinazione,
    'aspettobeni' => $rs[0]['aspettobeni'],
    'causalet' => $rs[0]['causalet'],
    'porto' => $rs[0]['porto'],
    'n_colli' => !empty($rs[0]['n_colli']) ? $rs[0]['n_colli'] : '',
    'spedizione' => $rs[0]['spedizione'],
    'vettore' => $rs[0]['vettore'],
];

// Controllo sui permessi
if ($id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) {
    die(tr('Non hai i permessi per questa stampa!'));
}

$mostra_prezzi = get_var("Stampa i prezzi sui ddt");
