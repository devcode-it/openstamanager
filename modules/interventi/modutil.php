<?php

include_once __DIR__.'/../../core.php';

/**
 * Recupera il totale delle ore spese per un intervento.
 *
 * @param int $idintervento
 */
function get_ore_intervento($idintervento)
{
    $dbo = database();
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
 * @param int $idintervento
 * @param int $idimpianto
 * @param int $idarticolo
 * @param int $qta
 */
function link_componente_to_articolo($idintervento, $idimpianto, $idarticolo, $qta)
{
    $dbo = database();

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

function add_tecnico($idintervento, $idtecnico, $inizio, $fine, $idcontratto = null)
{
    $dbo = database();

    // Controllo sull'identità del tecnico
    $tecnico = $dbo->fetchOne('SELECT an_anagrafiche.idanagrafica, an_anagrafiche.email FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE an_anagrafiche.idanagrafica = '.prepare($idtecnico)." AND an_tipianagrafiche.descrizione = 'Tecnico'");
    if (empty($tecnico)) {
        return false;
    }

    $rs = $dbo->fetchArray('SELECT idanagrafica, idsede, idtipointervento FROM in_interventi WHERE id='.prepare($idintervento));
    $idanagrafica = $rs[0]['idanagrafica'];
    $idsede = $rs[0]['idsede'];
    $idtipointervento = $rs[0]['idtipointervento'];

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
    $ore = ($diff->h + ($diff->i / 60));

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

    // Notifica nuovo intervento al tecnico
    if (!empty($tecnico['email'])) {
        $n = new Notifications\EmailNotification();

        $n->setTemplate('Notifica intervento', $id_record);
        $n->setReceivers($tecnico['email']);

        $n->send();
    }

    return true;
}

function get_costi_intervento($id_intervento)
{
    $dbo = database();

    $decimals = setting('Cifre decimali per importi');

    $idiva = setting('Iva predefinita');
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
        $result['manodopera_costo'] * $rs_iva[0]['percentuale'] / 100,
        $result['dirittochiamata_costo'] * $rs_iva[0]['percentuale'] / 100,
        $result['viaggio_costo'] * $rs_iva[0]['percentuale'] / 100,
        $result['ricambi_costo'] * $result['ricambi_iva'] / 100,
        $result['altro_costo'] * $result['altro_iva'] / 100,
    ]);

    $result['iva_addebito'] = sum([
        $result['manodopera_addebito'] * $rs_iva[0]['percentuale'] / 100,
        $result['dirittochiamata_addebito'] * $rs_iva[0]['percentuale'] / 100,
        $result['viaggio_addebito'] * $rs_iva[0]['percentuale'] / 100,
        $result['ricambi_addebito'] * $result['ricambi_iva'] / 100,
        $result['altro_addebito'] * $result['altro_iva'] / 100,
    ]);

    $result['iva_totale'] = sum([
        $result['manodopera_scontato'] * $rs_iva[0]['percentuale'] / 100,
        $result['dirittochiamata_scontato'] * $rs_iva[0]['percentuale'] / 100,
        $result['viaggio_scontato'] * $rs_iva[0]['percentuale'] / 100,
        $result['ricambi_scontato'] * $result['ricambi_iva'] / 100,
        $result['altro_scontato'] * $result['altro_iva'] / 100,
    ]);

    $result['totaleivato_costo'] = sum([
        $result['manodopera_costo'] + ($result['manodopera_costo'] * $rs_iva[0]['percentuale'] / 100),
        $result['dirittochiamata_costo'] + ($result['dirittochiamata_costo'] * $rs_iva[0]['percentuale'] / 100),
        $result['viaggio_costo'] + ($result['viaggio_costo'] * $rs_iva[0]['percentuale'] / 100),
        $result['ricambi_costo'] + ($result['ricambi_costo'] * $result['ricambi_iva'] / 100),
        $result['altro_costo'] + ($result['altro_costo'] * $result['altro_iva'] / 100),
    ]);

    $result['totaleivato_addebito'] = sum([
        $result['manodopera_addebito'] + ($result['manodopera_addebito'] * $rs_iva[0]['percentuale'] / 100),
        $result['dirittochiamata_addebito'] + ($result['dirittochiamata_addebito'] * $rs_iva[0]['percentuale'] / 100),
        $result['viaggio_addebito'] + ($result['viaggio_addebito'] * $rs_iva[0]['percentuale'] / 100),
        $result['ricambi_addebito'] + ($result['ricambi_addebito'] * $result['ricambi_iva'] / 100),
        $result['altro_addebito'] + ($result['altro_addebito'] * $result['altro_iva'] / 100),
    ]);

    $result['totale'] = sum([
        $result['manodopera_scontato'] + ($result['manodopera_scontato'] * $rs_iva[0]['percentuale'] / 100),
        $result['dirittochiamata_scontato'] + ($result['dirittochiamata_scontato'] * $rs_iva[0]['percentuale'] / 100),
        $result['viaggio_scontato'] + ($result['viaggio_scontato'] * $rs_iva[0]['percentuale'] / 100),
        $result['ricambi_scontato'] + ($result['ricambi_scontato'] * $result['ricambi_iva'] / 100),
        $result['altro_scontato'] + ($result['altro_scontato'] * $result['altro_iva'] / 100),
    ]);

    // Calcolo dello sconto incondizionato
    $sconto = $dbo->fetchArray('SELECT sconto_globale, tipo_sconto_globale FROM in_interventi WHERE id='.prepare($id_intervento))[0];
    $result['sconto_globale'] = ($sconto['tipo_sconto_globale'] == 'PRC') ? $result['totale_scontato'] * $sconto['sconto_globale'] / 100 : $sconto['sconto_globale'];
    $result['sconto_globale'] = round($result['sconto_globale'], $decimals);

    $result['totale_scontato'] = sum($result['totale_scontato'], -$result['sconto_globale']);
    $result['iva_totale'] = sum($result['iva_totale'], -($result['sconto_globale'] * $rs_iva[0]['percentuale'] / 100));
    $result['totale'] = sum($result['totale'], -($result['sconto_globale'] + ($result['sconto_globale'] * $rs_iva[0]['percentuale'] / 100)));

    return $result;
}

/**
 * Calcola le ore presenti tra due date.
 *
 * @param string $orario_inizio
 * @param string $orario_fine
 *
 * @return float
 */
function calcola_ore_intervento($orario_inizio, $orario_fine)
{
    $inizio = new DateTime($orario_inizio);
    $diff = $inizio->diff(new DateTime($orario_fine));

    $ore = $diff->i / 60 + $diff->h + ($diff->days * 24);

    return $ore;
}

function aggiungi_intervento_in_fattura($id_intervento, $id_fattura, $descrizione, $id_iva, $id_conto)
{
    $dbo = database();

    $id_ritenuta_acconto = setting("Percentuale ritenuta d'acconto");
    $id_rivalsa_inps = setting('Percentuale rivalsa INPS');

    // Leggo l'anagrafica del cliente
    $rs = $dbo->fetchArray('SELECT idanagrafica, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento).') AS data FROM `in_interventi` WHERE id='.prepare($id_intervento));
    $idanagrafica = $rs[0]['idanagrafica'];
    $data = $rs[0]['data'];
    $codice = $rs[0]['codice'];

    // Fatturo le ore di lavoro raggruppate per costo orario
    $rst = $dbo->fetchArray('SELECT SUM( ROUND( TIMESTAMPDIFF( MINUTE, orario_inizio, orario_fine ) / 60, '.setting('Cifre decimali per quantità').' ) ) AS tot_ore, SUM(prezzo_ore_consuntivo) AS tot_prezzo_ore_consuntivo, SUM(sconto) AS tot_sconto, prezzo_ore_unitario FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento).' GROUP BY prezzo_ore_unitario');

    // Aggiunta riga intervento sul documento
    if (sizeof($rst) == 0) {
        $_SESSION['warnings'][] = tr('L\'intervento _NUM_ non ha sessioni di lavoro!', [
            '_NUM_' => $id_intervento,
        ]);
    } else {
        for ($i = 0; $i < sizeof($rst); ++$i) {
            $ore = $rst[$i]['tot_ore'];

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($id_iva);
            $rs = $dbo->fetchArray($query);

            $sconto = $rst[$i]['tot_sconto'];
            $subtot = $rst[$i]['tot_prezzo_ore_consuntivo'];
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare($id_rivalsa_inps);
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
            $rs = $dbo->fetchArray($query);
            if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
                $ritenutaacconto = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            } else {
                $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
            }

            $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenutaacconto, `order`) VALUES('.prepare($id_fattura).', '.prepare($id_intervento).', '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto).", 'UNT', 'ore', ".prepare($ore).', '.prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare(setting("Metodologia calcolo ritenuta d'acconto predefinito")).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
            $dbo->query($query);
        }
    }

    $costi_intervento = get_costi_intervento($id_intervento);

    // Fatturo i diritti di chiamata raggruppati per costo
    $rst = $dbo->fetchArray('SELECT COUNT(id) AS qta, SUM(prezzo_dirittochiamata) AS tot_prezzo_dirittochiamata FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento).' AND prezzo_dirittochiamata > 0 GROUP BY prezzo_dirittochiamata');

    // Aggiunta diritto di chiamata se esiste
    for ($i = 0; $i < sizeof($rst); ++$i) {
        // Calcolo iva
        $query = 'SELECT * FROM co_iva WHERE id='.prepare($id_iva);
        $rs = $dbo->fetchArray($query);

        $iva = $rst[$i]['tot_prezzo_dirittochiamata'] / 100 * $rs[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
        $desc_iva = $rs[0]['descrizione'];

        // Calcolo rivalsa inps
        $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare($id_rivalsa_inps);
        $rs = $dbo->fetchArray($query);
        $rivalsainps = $rst[$i]['tot_prezzo_dirittochiamata'] / 100 * $rs[0]['percentuale'];

        // Calcolo ritenuta d'acconto
        $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
        $rs = $dbo->fetchArray($query);
        $ritenutaacconto = $rst[$i]['tot_prezzo_dirittochiamata'] / 100 * $rs[0]['percentuale'];

        $dbo->insert('co_righe_documenti', [
            'iddocumento' => $id_fattura,
            'idintervento' => $id_intervento,
            'idconto' => $id_conto,
            'idiva' => $id_iva,
            'desc_iva' => $desc_iva,
            'iva' => $iva,
            'iva_indetraibile' => $iva_indetraibile,
            'descrizione' => 'Diritto di chiamata',
            'subtotale' => $rst[$i]['tot_prezzo_dirittochiamata'],
            'sconto' => 0,
            'sconto_unitario' => 0,
            'tipo_sconto' => 'UNT',
            'um' => '-',
            'qta' => $rst[$i]['qta'],
            'idrivalsainps' => $id_rivalsa_inps,
            'rivalsainps' => $rivalsainps,
            'idritenutaacconto' => $id_ritenuta_acconto,
            'ritenutaacconto' => $ritenutaacconto,
            '#order' => '(SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).')',
        ]);
    }

    // Collego in fattura eventuali articoli collegati all'intervento
    $rs2 = $dbo->fetchArray('SELECT mg_articoli_interventi.*, idarticolo FROM mg_articoli_interventi INNER JOIN mg_articoli ON mg_articoli_interventi.idarticolo=mg_articoli.id WHERE idintervento='.prepare($id_intervento).' AND (idintervento NOT IN(SELECT idintervento FROM co_righe_preventivi WHERE idpreventivo IN(SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_fattura).')) AND idintervento NOT IN(SELECT idintervento FROM co_promemoria WHERE idcontratto IN(SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($id_fattura).')) )');
    for ($i = 0; $i < sizeof($rs2); ++$i) {
        $riga = add_articolo_infattura($id_fattura, $rs2[$i]['idarticolo'], $rs2[$i]['descrizione'], $rs2[$i]['idiva'], $rs2[$i]['qta'], $rs2[$i]['prezzo_vendita'] * $rs2[$i]['qta'], $rs2[$i]['sconto'], $rs2[$i]['sconto_unitario'], $rs2[$i]['tipo_sconto'], $id_intervento, 0, $rs2[$i]['um']);

        // Lettura lotto, serial, altro dalla riga dell'ordine
        $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($riga).', '.prepare($rs2[$i]['idarticolo']).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_intervento='.prepare($rs2[$i]['id']));
    }

    // Aggiunta spese aggiuntive come righe generiche
    $query = 'SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($id_intervento).' AND (idintervento NOT IN(SELECT idintervento FROM co_righe_preventivi WHERE idpreventivo IN(SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_fattura).')) AND idintervento NOT IN(SELECT idintervento FROM co_promemoria WHERE idcontratto IN(SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($id_fattura).')) )';
    $rsr = $dbo->fetchArray($query);
    if (sizeof($rsr) > 0) {
        for ($i = 0; $i < sizeof($rsr); ++$i) {
            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($rsr[$i]['idiva']);
            $rs = $dbo->fetchArray($query);
            $desc_iva = $rs[0]['descrizione'];

            $subtot = $rsr[$i]['prezzo_vendita'] * $rsr[$i]['qta'];
            $sconto = $rsr[$i]['sconto'];
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare($id_rivalsa_inps);
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
            $rs = $dbo->fetchArray($query);
            if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
                $ritenutaacconto = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            } else {
                $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
            }

            $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenutaacconto, `order`) VALUES('.prepare($id_fattura).', '.prepare($id_intervento).', '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($rsr[$i]['descrizione']).', '.prepare($subtot).', '.prepare($rsr[$i]['sconto']).', '.prepare($rsr[$i]['sconto_unitario']).', '.prepare($rsr[$i]['tipo_sconto']).', '.prepare($rsr[$i]['um']).', '.prepare($rsr[$i]['qta']).', '.prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare(setting("Metodologia calcolo ritenuta d'acconto predefinito")).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
            $dbo->query($query);
        }
    }

    // Aggiunta km come "Trasferta" (se c'è)
    if ($costi_intervento['viaggio_addebito'] > 0) {
        // Calcolo iva
        $query = 'SELECT * FROM co_iva WHERE id='.prepare($id_iva);
        $dati = $dbo->fetchArray($query);
        $desc_iva = $dati[0]['descrizione'];

        $subtot = $costi_intervento['viaggio_addebito'];
        $sconto = $costi_intervento['viaggio_addebito'] - $costi_intervento['viaggio_scontato'];
        $iva = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $dati[0]['indetraibile'];

        // Calcolo rivalsa inps
        $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare($id_rivalsa_inps);
        $dati = $dbo->fetchArray($query);
        $rivalsainps = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];

        // Calcolo ritenuta d'acconto
        $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
        $dati = $dbo->fetchArray($query);
        if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
            $ritenutaacconto = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];
        } else {
            $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $dati[0]['percentuale'];
        }

        $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenutaacconto, `order`) VALUES('.prepare($id_fattura).', '.prepare($id_intervento).', '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare('Trasferta intervento '.$codice.' del '.Translator::dateToLocale($data)).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto).", 'UNT', '', 1, ".prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare(setting("Metodologia calcolo ritenuta d'acconto predefinito")).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
        $dbo->query($query);
    }

    // Aggiunta sconto
    if (!empty($costi_intervento['sconto_globale'])) {
        $subtot = -$costi_intervento['sconto_globale'];

        // Calcolo iva
        $query = 'SELECT * FROM co_iva WHERE id='.prepare($id_iva);
        $rs = $dbo->fetchArray($query);
        $desc_iva = $rs[0]['descrizione'];

        $iva = ($subtot) / 100 * $rs[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

        // Calcolo rivalsa inps
        $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare($id_rivalsa_inps);
        $rs = $dbo->fetchArray($query);
        $rivalsainps = ($subtot) / 100 * $rs[0]['percentuale'];

        // Calcolo ritenuta d'acconto
        $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
        $rs = $dbo->fetchArray($query);
        if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
            $ritenutaacconto = $subtot / 100 * $rs[0]['percentuale'];
        } else {
            $ritenutaacconto = ($subtot + $rivalsainps) / 100 * $rs[0]['percentuale'];
        }

        $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenutaacconto, `order`) VALUES('.prepare($id_fattura).', NULL, '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare('Sconto '.$descrizione).', '.prepare($subtot).', 1, '.prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare(setting("Metodologia calcolo ritenuta d'acconto predefinito")).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
        $dbo->query($query);
    }

    // Ricalcolo inps, ritenuta e bollo
    if ($dir == 'entrata') {
        ricalcola_costiagg_fattura($id_fattura);
    } else {
        ricalcola_costiagg_fattura($id_fattura);
    }

    // Metto l'intervento in stato "Fatturato"
    $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id=".prepare($id_intervento));
}
