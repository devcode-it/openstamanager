<?php

include_once __DIR__.'/../../core.php';

/**
 * Recupera il totale delle ore spese per un intervento.
 *
 * @param [type] $idintervento
 */
function get_ore_intervento($idintervento)
{
    $dbo = Database::getConnection();
    $totale_ore = 0;

    $rs = $dbo->fetchArray('SELECT idintervento, TIMESTAMPDIFF( MINUTE, orario_inizio, orario_fine ) / 60 AS tot_ore FROM in_interventi_tecnici WHERE idintervento = '.prepare($idintervento));

    for ($i = 0; $i < count($rs); ++$i) {
        $totale_ore = $totale_ore + $rs[$i]['tot_ore'];
    }

    return $totale_ore;
}

/**
 * Funzione per collegare gli articoli, usati in un intervento, ai rispettivi impianti.
 *
 * @param [type] $idintervento
 * @param [type] $idimpianto
 * @param [type] $idarticolo
 * @param [type] $qta
 */
function link_componente_to_articolo($idintervento, $idimpianto, $idarticolo, $qta)
{
    global $docroot;
    $dbo = Database::getConnection();

    if (!empty($idimpianto) && !empty($idintervento)) {
        //Leggo la data dell'intervento
        $rs = $dbo->fetchArray("SELECT DATE_FORMAT(MIN(orario_inizio),'%Y-%m-%d') AS data FROM in_interventi_tecnici WHERE idintervento=".prepare($idintervento));
        $data = $rs[0]['data'];

        $rs = $dbo->fetchArray('SELECT componente_filename, contenuto FROM mg_articoli WHERE id='.prepare($idarticolo));

        //Se l'articolo aggiunto è collegato a un file .ini, aggiungo il componente all'impianto selezionato
        if (count($rs) == 1 && $rs[0]['componente_filename'] != '') {
            //Inserisco il componente tante volte quante la quantità degli articoli inseriti
            for ($q = 0; $q < $qta; ++$q) {
                $dbo->query('INSERT INTO my_impianto_componenti(idimpianto, idintervento, nome, data, filename, contenuto) VALUES ('.prepare($idimpianto).', '.prepare($idintervento).', '.prepare(\Util\Ini::getValue($rs[0]['componente_filename'], 'Nome')).', '.prepare($data).', '.prepare($rs[0]['componente_filename']).', '.prepare($rs[0]['contenuto']).')');
            }
        }
    }
}

function add_tecnico($idintervento, $idtecnico, $inizio, $fine, $idcontratto)
{
    $dbo = Database::getConnection();

    $rs = $dbo->fetchArray('SELECT idsede, idtipointervento FROM in_interventi WHERE id='.prepare($idintervento));
    $idtipointervento = $rs[0]['idtipointervento'];
    $idsede = $rs[0]['idsede'];

    // Calcolo km in base a quelli impostati nell'anagrafica
    // Nessuna sede
    if ($idsede == '-1') {
        $km = 0;
    }

    // Sede legale
    elseif (empty($idsede)) {
        $rs2 = $dbo->fetchArray('SELECT km FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
        $km = $rs2[0]['km'];
    }

    // Sede secondaria
    else {
        $rs2 = $dbo->fetchArray('SELECT km FROM an_sedi WHERE id='.prepare($idsede));
        $km = $rs2[0]['km'];
    }

    $km = empty($km) ? 0 : $km;

    // Calcolo il totale delle ore lavorate
    $diff = date_diff(date_create($inizio), date_create($fine));
    $ore = $diff->h + $diff->m / 60;

    // Leggo i costi unitari dalle tariffe se almeno un valore è stato impostato
    $rsc = $dbo->fetchArray('SELECT * FROM in_tariffe WHERE idtecnico='.prepare($idtecnico).' AND idtipointervento='.prepare($idtipointervento));

    if ($rsc[0]['costo_ore'] != 0 || $rsc[0]['costo_km'] != 0 || $rsc[0]['costo_dirittochiamata'] != 0 || $rsc[0]['costo_ore_tecnico'] != 0 || $rsc[0]['costo_km_tecnico'] != 0 || $rsc[0]['costo_dirittochiamata_tecnico'] != 0) {
        $costo_ore = $rsc[0]['costo_ore'];
        $costo_km = $rsc[0]['costo_km'];
        $costo_dirittochiamata = $rsc[0]['costo_dirittochiamata'];

        $costo_ore_tecnico = $rsc[0]['costo_ore_tecnico'];
        $costo_km_tecnico = $rsc[0]['costo_km_tecnico'];
        $costo_dirittochiamata_tecnico = $rsc[0]['costo_dirittochiamata_tecnico'];
    }

    // ...altrimenti se non c'è una tariffa per il tecnico leggo i costi globali
    else {
        $rsc = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE idtipointervento='.prepare($idtipointervento));

        $costo_ore = $rsc[0]['costo_orario'];
        $costo_km = $rsc[0]['costo_km'];
        $costo_dirittochiamata = $rsc[0]['costo_diritto_chiamata'];

        $costo_ore_tecnico = $rsc[0]['costo_orario_tecnico'];
        $costo_km_tecnico = $rsc[0]['costo_km_tecnico'];
        $costo_dirittochiamata_tecnico = $rsc[0]['costo_diritto_chiamata_tecnico'];
    }

    // Leggo i costi unitari da contratto se l'intervento è legato ad un contratto e c'è almeno un record...
    if (!empty($idcontratto)) {
        $rsc = $dbo->fetchArray('SELECT * FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($idcontratto).' AND idtipointervento='.prepare($idtipointervento));

        if (count($rsc) == 1) {
            $costo_ore = $rsc[0]['costo_ore'];
            $costo_km = $rsc[0]['costo_km'];
            $costo_dirittochiamata = $rsc[0]['costo_dirittochiamata'];

            $costo_ore_tecnico = $rsc[0]['costo_ore_tecnico'];
            $costo_km_tecnico = $rsc[0]['costo_km_tecnico'];
            $costo_dirittochiamata_tecnico = $rsc[0]['costo_dirittochiamata_tecnico'];
        }
    }

    // Inserisco le ore dei tecnici nella tabella "in_interventi_tecnici"
    $dbo->insert('in_interventi_tecnici', [
        'idintervento' => $idintervento,
        'idtipointervento' => $idtipointervento,
        'idtecnico' => $idtecnico,
        'km' => $km,
        'orario_inizio' => $inizio,
        'orario_fine' => $fine,
        'ore' => $ore,
        'prezzo_ore_unitario' => $costo_ore,
        'prezzo_km_unitario' => $costo_km,

        'prezzo_ore_consuntivo' => $costo_ore * $ore + $costo_dirittochiamata,
        'prezzo_km_consuntivo' => 0,
        'prezzo_dirittochiamata' => $costo_dirittochiamata,

        'prezzo_ore_unitario_tecnico' => $costo_ore_tecnico,
        'prezzo_km_unitario_tecnico' => $costo_km_tecnico,

        'prezzo_ore_consuntivo_tecnico' => $costo_ore_tecnico * $ore + $costo_dirittochiamata_tecnico,
        'prezzo_km_consuntivo_tecnico' => 0,
        'prezzo_dirittochiamata_tecnico' => $costo_dirittochiamata_tecnico,
    ]);
}
