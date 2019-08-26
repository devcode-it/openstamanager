<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

$documento = Fattura::find($id_record);
$banca = $documento->getBanca();

// Lettura info fattura
$record = $dbo->fetchOne('SELECT *,
    (SELECT descrizione FROM co_statidocumento WHERE id=idstatodocumento) AS stato_doc,
    (SELECT descrizione FROM co_tipidocumento WHERE id=idtipodocumento) AS tipo_doc,
    (SELECT descrizione FROM co_pagamenti WHERE id=idpagamento) AS tipo_pagamento,
    (SELECT dir FROM co_tipidocumento WHERE id=idtipodocumento) AS dir,
    (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causalet,
    (SELECT descrizione FROM dt_porto WHERE id=idporto) AS porto,
    (SELECT descrizione FROM dt_aspettobeni WHERE id=idaspettobeni) AS aspettobeni,
    (SELECT descrizione FROM dt_spedizione WHERE id=idspedizione) AS spedizione,
    (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idvettore) AS vettore,
    (SELECT id FROM co_banche WHERE id=idbanca) AS id_banca,
    (SELECT is_fiscale FROM zz_segments WHERE id = id_segment) AS is_fiscale
FROM co_documenti WHERE id='.prepare($id_record));

$record['rivalsainps'] = floatval($record['rivalsainps']);
$record['ritenutaacconto'] = floatval($record['ritenutaacconto']);
$record['bollo'] = floatval($record['bollo']);

$nome_banca = $banca['appoggiobancario'];
$iban_banca = $banca['codiceiban'];
$bic_banca = $banca['bic'];

$module_name = ($record['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';

$id_cliente = $record['idanagrafica'];
$id_sede = $record['idsede_partenza'];

$tipo_doc = $record['tipo_doc'];
$numero = !empty($record['numero_esterno']) ? $record['numero_esterno'] : $record['numero'];

// Caso particolare per le fatture pro forma
if (empty($record['is_fiscale'])) {
    $tipo_doc = tr('Fattura pro forma');
}

// Fix per le fattura accompagnatorie
$fattura_accompagnatoria = ($record['tipo_doc'] == 'Fattura accompagnatoria di vendita');
$tipo_doc = ($fattura_accompagnatoria) ? 'Fattura accompagnatoria di vendita' : $tipo_doc;

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($record['idsede_destinazione'])) {
    $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, nomesede, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).' AND id='.prepare($record['idsede_destinazione']));

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
    'tipo_doc' => Stringy\Stringy::create($tipo_doc)->toUpperCase(),
    'numero' => $numero,
    'data' => Translator::dateToLocale($record['data']),
    'pagamento' => $record['tipo_pagamento'],
    'c_destinazione' => $destinazione,
    'aspettobeni' => $record['aspettobeni'],
    'causalet' => $record['causalet'],
    'porto' => $record['porto'],
    'n_colli' => !empty($record['n_colli']) ? $record['n_colli'] : '',
    'spedizione' => $record['spedizione'],
    'vettore' => $record['vettore'],
    'appoggiobancario' => $nome_banca,
    'codiceiban' => $iban_banca,
    'bic' => $bic_banca,
];

// Accesso solo a:
// - cliente se è impostato l'idanagrafica di un Cliente
// - utente qualsiasi con permessi almeno in lettura sul modulo
// - admin
if ((Auth::user()['gruppo'] == 'Clienti' && $id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) || Modules::getPermission($module_name) == '-') {
    die(tr('Non hai i permessi per questa stampa!'));
}

if ($fattura_accompagnatoria) {
    $settings['footer-height'] += 40;
}
