<?php

include_once __DIR__.'/../../core.php';

// Lettura info ddt
$q = 'SELECT *,
    (SELECT dir FROM dt_tipiddt WHERE id=idtipoddt) AS dir,
    (SELECT descrizione FROM dt_tipiddt WHERE id=idtipoddt) AS tipo_doc,
    (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causalet,
    (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento,
    (SELECT descrizione FROM dt_porto WHERE id=idporto) AS porto,
    (SELECT descrizione FROM dt_aspettobeni WHERE id=idaspettobeni) AS aspettobeni,
    (SELECT descrizione FROM dt_spedizione WHERE id=idspedizione) AS spedizione,
    (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idvettore) AS vettore
FROM dt_ddt WHERE id='.prepare($id_record);
$records = $dbo->fetchArray($q);

$module_name = ($records[0]['dir'] == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';

$id_cliente = $records[0]['idanagrafica'];

$tipo_doc = $records[0]['tipo_doc'];
if (empty($records[0]['numero_esterno'])) {
    $numero = 'pro-forma '.$numero;
    $tipo_doc = tr('Ddt pro-forma', [], ['upper' => true]);
} else {
    $numero = !empty($records[0]['numero_esterno']) ? $records[0]['numero_esterno'] : $records[0]['numero'];
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($records[0]['idsede_destinazione'])) {
    $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, nomesede, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).' AND id='.prepare($records[0]['idsede_destinazione']));

    if (!empty($rsd[0]['nomesede'])) {
        $destinazione .= $rsd[0]['nomesede'].'<br/>';
    }
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
    'tipo_doc' => $tipo_doc,
    'numero' => $numero,
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

// Accesso solo a:
// - cliente se è impostato l'idanagrafica di un Cliente
// - utente qualsiasi con permessi almeno in lettura sul modulo
// - admin
if ((Auth::user()['gruppo'] == 'Clienti' && $id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) || Modules::getPermission($module_name) == '-') {
    die(tr('Non hai i permessi per questa stampa!'));
}
