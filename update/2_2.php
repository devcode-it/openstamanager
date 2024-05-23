<?php

/**
 * Verifico se serve creare un conto per eventuali nuovi clienti o fornitori.
 */
$rs = $dbo->fetchArray('SELECT idanagrafica, ragione_sociale, (SELECT GROUP_CONCAT(an_tipianagrafiche.descrizione) FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica=an_anagrafiche.idanagrafica) AS idtipianagrafica FROM an_anagrafiche WHERE idconto_cliente=0 OR idconto_fornitore=0');

for ($i = 0; $i < sizeof($rs); ++$i) {
    if (in_array('Cliente', explode(',', (string) $rs[$i]['idtipianagrafica']))) {
        // Calcolo il codice conto più alto
        $rs2 = $dbo->fetchArray("SELECT MAX( CAST(numero AS UNSIGNED) ) AS max_numero, idpianodeiconti2 FROM co_pianodeiconti3 WHERE numero=CAST(numero AS UNSIGNED) AND idpianodeiconti2=(SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi')");
        $numero = str_pad($rs2[0]['max_numero'] + 1, 6, '0', STR_PAD_LEFT);
        $idpianodeiconti2 = $rs2[0]['idpianodeiconti2'];

        // Creo il nuovo conto
        $dbo->query('INSERT INTO co_pianodeiconti3( numero, descrizione, idpianodeiconti2, can_delete, can_edit ) VALUES( "'.$numero.'", "'.$rs[$i]['ragione_sociale'].'", "'.$idpianodeiconti2.'", 1, 1 )');
        $idconto = $dbo->lastInsertedID();

        // Collego questo conto al cliente
        $dbo->query('UPDATE an_anagrafiche SET idconto_cliente="'.$idconto.'" WHERE idanagrafica="'.$rs[$i]['idanagrafica'].'"');
    }

    if (in_array('Fornitore', explode(',', (string) $rs[$i]['idtipianagrafica']))) {
        // Calcolo il codice conto più alto
        $rs2 = $dbo->fetchArray("SELECT MAX( CAST(numero AS UNSIGNED) ) AS max_numero, idpianodeiconti2 FROM co_pianodeiconti3 WHERE numero=CAST(numero AS UNSIGNED) AND idpianodeiconti2=(SELECT id FROM co_pianodeiconti2 WHERE descrizione='Debiti fornitori e debiti diversi')");
        $numero = str_pad($rs2[0]['max_numero'] + 1, 6, '0', STR_PAD_LEFT);
        $idpianodeiconti2 = $rs2[0]['idpianodeiconti2'];

        // Creo il nuovo conto
        $dbo->query('INSERT INTO co_pianodeiconti3( numero, descrizione, idpianodeiconti2, can_delete, can_edit ) VALUES( "'.$numero.'", "'.$rs[$i]['ragione_sociale'].'", "'.$idpianodeiconti2.'", 1, 1 )');
        $idconto = $dbo->lastInsertedID();

        // Collego questo conto al cliente
        $dbo->query('UPDATE an_anagrafiche SET idconto_fornitore="'.$idconto.'" WHERE idanagrafica="'.$rs[$i]['idanagrafica'].'"');
    }
}

// Sposto tutti i movimenti delle fatture dal riepilogativo (clienti o fornitori) al relativo conto di ogni anagrafica
$rs = $dbo->fetchArray('SELECT co_movimenti.id, co_documenti.idanagrafica, dir FROM (co_movimenti INNER JOIN co_documenti ON co_movimenti.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento WHERE NOT iddocumento=0');

for ($i = 0; $i < sizeof($rs); ++$i) {
    if ($rs[$i]['dir'] == 'entrata') {
        $query = 'UPDATE co_movimenti SET idconto=(SELECT idconto_cliente FROM an_anagrafiche WHERE idanagrafica="'.$rs[$i]['idanagrafica'].'") WHERE id="'.$rs[$i]['id']."\" AND idconto=(SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo clienti')";
    } else {
        $query = 'UPDATE co_movimenti SET idconto=(SELECT idconto_fornitore FROM an_anagrafiche WHERE idanagrafica="'.$rs[$i]['idanagrafica'].'") WHERE id="'.$rs[$i]['id']."\" AND idconto=(SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo fornitori')";
    }

    $dbo->query($query);
}

// Aggiungo il flag "Attiva aggiornamenti" se manca (nella migrazione della 2.0 non c'è)
$rs = $dbo->fetchArray("SELECT idimpostazione FROM zz_impostazioni WHERE nome='Attiva aggiornamenti'");

if (sizeof($rs) == 0) {
    $dbo->query("INSERT INTO `zz_impostazioni` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ( 'Attiva aggiornamenti', '1', 'boolean', '0', 'Generali')");
}

// Spostamento ore di lavoro e diritto di chiamata dei preventivi nella tabella
$idiva = $dbo->fetchArray("SELECT valore FROM zz_impostazioni WHERE nome='Iva predefinita'")[0]['valore'];

$rs = $dbo->fetchArray('SELECT percentuale, indetraibile FROM co_iva WHERE id="'.$idiva.'"');
$percentuale = $rs[0]['percentuale'];
$indetraibile = $rs[0]['indetraibile'];

$rs = $dbo->fetchArray('SELECT * FROM co_preventivi WHERE ore_lavoro > 0 OR costo_diritto_chiamata > 0');

for ($i = 0; $i < sizeof($rs); ++$i) {
    // Ore lavoro
    if ($rs[$i]['ore_lavoro'] > 0) {
        $imponibile = $rs[$i]['costo_orario'] * $rs[$i]['ore_lavoro'];

        $iva = $imponibile / 100 * $percentuale;
        $iva_indetraibile = $imponibile / 100 * $indetraibile;

        $dbo->query('INSERT INTO co_righe_preventivi( idpreventivo, idiva, iva, iva_indetraibile, descrizione, subtotale, sconto, um, qta ) VALUES( "'.$rs[$i]['id'].'", "'.$idiva.'", "'.$iva.'", "'.$iva_indetraibile.'", "Ore lavoro", "'.$imponibile.'", "0.00", "ore", "'.$rs[$i]['ore_lavoro'].'" )');
    }

    // Ore diritto chiamata
    if ($rs[$i]['costo_diritto_chiamata'] > 0) {
        $imponibile = $rs[$i]['costo_diritto_chiamata'];

        $iva = $imponibile / 100 * $percentuale;
        $iva_indetraibile = $imponibile / 100 * $indetraibile;

        $dbo->query('INSERT INTO co_righe_preventivi( idpreventivo, idiva, iva, iva_indetraibile, descrizione, subtotale, sconto, um, qta ) VALUES( "'.$rs[$i]['id'].'", "'.$idiva.'", "'.$iva.'", "'.$iva_indetraibile.'", "Diritto chiamata", "'.$imponibile.'", "0.00", "", "'.$rs[$i]['costo_diritto_chiamata'].'" )');
    }
}

// Eliminazione vecchi file
$files = [
    base_dir().'/share/themes/default/css/font-awesome.css',
    base_dir().'/modules/preventivi/js/',
];
delete($files);

/*
* Spostamento agente di riferimento su nuova tabella an_anagrafiche_agenti
*/
$rs = $dbo->fetchArray('SELECT idanagrafica, idagente FROM an_anagrafiche WHERE NOT idagente=0');

for ($i = 0; $i < sizeof($rs); ++$i) {
    $dbo->query('INSERT INTO an_anagrafiche_agenti( idanagrafica, idagente ) VALUES( "'.$rs[$i]['idanagrafica'].'", "'.$rs[$i]['idagente'].'" )');
}

/**
 * 2016-11-09 (r1509)
 * Creo le associazioni fra i tipi di intervento e i contratti.
 */
$rsc = $dbo->fetchArray('SELECT id FROM co_contratti');

for ($c = 0; $c < sizeof($rsc); ++$c) {
    $rsi = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE (costo_orario!=0 OR costo_km!=0 OR costo_diritto_chiamata!=0)');

    for ($i = 0; $i < sizeof($rsi); ++$i) {
        $dbo->query('INSERT INTO co_contratti_tipiintervento( idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico ) VALUES( "'.$rsc[$c]['id'].'", "'.$rsi[$i]['idtipointervento'].'", "'.$rsi[$i]['costo_orario'].'", "'.$rsi[$i]['costo_km'].'", "'.$rsi[$i]['costo_diritto_chiamata'].'", "'.$rsi[$i]['costo_orario_tecnico'].'", "'.$rsi[$i]['costo_km_tecnico'].'", "'.$rsi[$i]['costo_diritto_chiamata_tecnico'].'" )');
    }
}
