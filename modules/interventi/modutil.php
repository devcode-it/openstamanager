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
    $ore = ($diff->h + ($diff->i/60) );

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

    // Azzeramento forzato del diritto di chiamata nel caso questa non sia la prima sessione dell'intervento per il giorno di inizio [Luca]
    $rs = $dbo->fetchArray('SELECT id FROM in_interventi_tecnici WHERE (DATE(orario_inizio)=DATE('.prepare($inizio).') OR DATE(orario_fine)=DATE('.prepare($inizio).')) AND idintervento='.prepare($idintervento));
    if (!empty($rs)) {
        $costo_dirittochiamata_tecnico = 0;
        $costo_dirittochiamata = 0;
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

function get_costi_intervento($id_intervento)
{
    $dbo = Database::getConnection();

    $decimals = Settings::get('Cifre decimali per importi');
    
    $idiva = get_var('Iva predefinita');
    $rs_iva = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));

    $tecnici = $dbo->fetchArray('SELECT
    COALESCE(SUM(
        ROUND(prezzo_ore_consuntivo_tecnico, '.$decimals.')
    ), 0) AS manodopera_costo,
    COALESCE(SUM(
        ROUND(prezzo_ore_consuntivo, '.$decimals.')
    ), 0) AS manodopera_addebito,
    COALESCE(SUM(
        ROUND(prezzo_ore_consuntivo, '.$decimals.') - ROUND(sconto, '.$decimals.')
    ), 0) AS manodopera_scontato,

    COALESCE(SUM(
        ROUND(prezzo_dirittochiamata_tecnico, '.$decimals.')
    ), 0) AS dirittochiamata_costo,
    COALESCE(SUM(
        ROUND(prezzo_dirittochiamata, '.$decimals.')
    ), 0) AS dirittochiamata_addebito,
    COALESCE(SUM(
        ROUND(prezzo_dirittochiamata, '.$decimals.')
    ), 0) AS dirittochiamata_scontato,

    COALESCE(SUM(
        ROUND(prezzo_km_consuntivo_tecnico, '.$decimals.')
    ), 0) AS viaggio_costo,
    COALESCE(SUM(
        ROUND(prezzo_km_consuntivo, '.$decimals.')
    ), 0) viaggio_addebito,
    COALESCE(SUM(
        ROUND(prezzo_km_consuntivo, '.$decimals.') - ROUND(scontokm, '.$decimals.')
    ), 0) AS viaggio_scontato

    FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento));

    $articoli = $dbo->fetchArray('SELECT
    COALESCE(SUM(
        ROUND(prezzo_acquisto, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS ricambi_costo,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS ricambi_addebito,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.') - ROUND(sconto, '.$decimals.')
    ), 0) AS ricambi_scontato,
    ROUND(
        (SELECT percentuale FROM co_iva WHERE co_iva.id=mg_articoli_interventi.idiva), '.$decimals.'
        ) AS ricambi_iva

    FROM mg_articoli_interventi WHERE idintervento='.prepare($id_intervento));

    $altro = $dbo->fetchArray('SELECT
    COALESCE(SUM(
        ROUND(prezzo_acquisto, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS altro_costo,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS altro_addebito,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.') - ROUND(sconto, '.$decimals.')
    ), 0) AS altro_scontato,
    ROUND(
        (SELECT percentuale FROM co_iva WHERE co_iva.id=in_righe_interventi.idiva), '.$decimals.'
        ) AS altro_iva

    FROM in_righe_interventi WHERE idintervento='.prepare($id_intervento));

    $result = array_merge($tecnici[0], $articoli[0], $altro[0]);

    $result['totale_costo'] = sum([
        $result['manodopera_costo'],
        $result['dirittochiamata_costo'],
        $result['viaggio_costo'],
        $result['ricambi_costo'],
        $result['altro_costo'],
    ]);

    $result['totale_addebito'] = sum([
        $result['manodopera_addebito'],
        $result['dirittochiamata_addebito'],
        $result['viaggio_addebito'],
        $result['ricambi_addebito'],
        $result['altro_addebito'],
    ]);

    $result['totale_scontato'] = sum([
        $result['manodopera_scontato'],
        $result['dirittochiamata_scontato'],
        $result['viaggio_scontato'],
        $result['ricambi_scontato'],
        $result['altro_scontato'],
    ]);
    
    $result['iva_costo'] = sum([
        $result['manodopera_costo']*$rs_iva[0]['percentuale']/100,
        $result['dirittochiamata_costo']*$rs_iva[0]['percentuale']/100,
        $result['viaggio_costo']*$rs_iva[0]['percentuale']/100,
        $result['ricambi_costo']*$result['ricambi_iva']/100,
        $result['altro_costo']*$result['altro_iva']/100,
    ]);

    $result['iva_addebito'] = sum([
        $result['manodopera_addebito']*$rs_iva[0]['percentuale']/100,
        $result['dirittochiamata_addebito']*$rs_iva[0]['percentuale']/100,
        $result['viaggio_addebito']*$rs_iva[0]['percentuale']/100,
        $result['ricambi_addebito']*$result['ricambi_iva']/100,
        $result['altro_addebito']*$result['altro_iva']/100,
    ]);

    $result['iva_totale'] = sum([
        $result['manodopera_scontato']*$rs_iva[0]['percentuale']/100,
        $result['dirittochiamata_scontato']*$rs_iva[0]['percentuale']/100,
        $result['viaggio_scontato']*$rs_iva[0]['percentuale']/100,
        $result['ricambi_scontato']*$result['ricambi_iva']/100,
        $result['altro_scontato']*$result['altro_iva']/100,
    ]);
    
    $result['totaleivato_costo'] = sum([
        $result['manodopera_costo']+($result['manodopera_costo']*$rs_iva[0]['percentuale']/100),
        $result['dirittochiamata_costo']+($result['dirittochiamata_costo']*$rs_iva[0]['percentuale']/100),
        $result['viaggio_costo']+($result['viaggio_costo']*$rs_iva[0]['percentuale']/100),
        $result['ricambi_costo']+($result['ricambi_costo']*$result['ricambi_iva']/100),
        $result['altro_costo']+($result['altro_costo']*$result['altro_iva']/100),
    ]);

    $result['totaleivato_addebito'] = sum([
        $result['manodopera_addebito']+($result['manodopera_addebito']*$rs_iva[0]['percentuale']/100),
        $result['dirittochiamata_addebito']+($result['dirittochiamata_addebito']*$rs_iva[0]['percentuale']/100),
        $result['viaggio_addebito']+($result['viaggio_addebito']*$rs_iva[0]['percentuale']/100),
        $result['ricambi_addebito']+($result['ricambi_addebito']*$result['ricambi_iva']/100),
        $result['altro_addebito']+($result['altro_addebito']*$result['altro_iva']/100),
    ]);

    $result['totale'] = sum([
        $result['manodopera_scontato']+($result['manodopera_scontato']*$rs_iva[0]['percentuale']/100),
        $result['dirittochiamata_scontato']+($result['dirittochiamata_scontato']*$rs_iva[0]['percentuale']/100),
        $result['viaggio_scontato']+($result['viaggio_scontato']*$rs_iva[0]['percentuale']/100),
        $result['ricambi_scontato']+($result['ricambi_scontato']*$result['ricambi_iva']/100),
        $result['altro_scontato']+($result['altro_scontato']*$result['altro_iva']/100),
    ]);

    // Calcolo dello sconto incondizionato
    $sconto = $dbo->fetchArray('SELECT sconto_globale, tipo_sconto_globale FROM in_interventi WHERE id='.prepare($id_intervento))[0];
    $result['sconto_globale'] = ($sconto['tipo_sconto_globale'] == 'PRC') ? $result['totale'] * $sconto['sconto_globale'] / 100 : $sconto['sconto_globale'];
    $result['sconto_globale'] = round($result['sconto_globale'], $decimals);
    
    $result['totale_scontato'] = sum($result['totale_scontato'], -$result['sconto_globale']);
    $result['iva_totale'] = sum($result['iva_totale'], -($result['sconto_globale']*$rs_iva[0]['percentuale']/100));
    $result['totale'] = sum($result['totale'], -($result['sconto_globale']+($result['sconto_globale']*$rs_iva[0]['percentuale']/100)));

    return $result;
}
