<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;

/**
 * Recupera il totale delle ore spese per un intervento.
 *
 * @param int $idintervento
 */
function get_ore_intervento($idintervento)
{
    $dbo = database();
    $totale_ore = 0;

    $sessioni = $dbo->fetchArray('SELECT idintervento, TIMESTAMPDIFF(MINUTE, orario_inizio, orario_fine) / 60 AS tot_ore FROM in_interventi_tecnici WHERE idintervento = '.prepare($idintervento));

    foreach ($sessioni as $sessione) {
        $totale_ore = $totale_ore + $sessione['tot_ore'];
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

    $intervento = Intervento::find($idintervento);
    $anagrafica = Anagrafica::find($idtecnico);

    $sessione = Sessione::build($intervento, $anagrafica, $inizio, $fine);

    // Notifica nuovo intervento al tecnico
    if (!empty($tecnico['email'])) {
        $n = new Notifications\EmailNotification();

        $n->setTemplate('Notifica intervento', $idintervento);
        $n->setReceivers($anagrafica['email']);

        $n->send();
    }

    return true;
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

function aggiungi_intervento_in_fattura($id_intervento, $id_fattura, $descrizione, $id_iva, $id_conto, $id_rivalsa_inps = false, $id_ritenuta_acconto = false, $calcolo_ritenuta_acconto = false)
{
    $dbo = database();

    $id_rivalsa_inps = $id_rivalsa_inps !== false ? $id_rivalsa_inps : setting('Percentuale rivalsa');
    $id_ritenuta_acconto = $id_ritenuta_acconto !== false ? $id_ritenuta_acconto : setting("Percentuale ritenuta d'acconto");
    $calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto !== false ? $calcolo_ritenuta_acconto : setting("Metodologia calcolo ritenuta d'acconto predefinito");

    // Leggo l'anagrafica del cliente
    $rs = $dbo->fetchArray('SELECT idanagrafica, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento).') AS data FROM `in_interventi` WHERE id='.prepare($id_intervento));
    $idanagrafica = $rs[0]['idanagrafica'];
    $data = $rs[0]['data'];
    $codice = $rs[0]['codice'];

    // Fatturo le ore di lavoro raggruppate per costo orario
    $rst = $dbo->fetchArray('SELECT SUM( ROUND( ore, '.setting('Cifre decimali per quantità').' ) ) AS tot_ore, SUM(prezzo_ore_unitario*ore) AS tot_prezzo_ore_consuntivo, SUM(sconto) AS tot_sconto, sconto_unitario, scontokm_unitario, prezzo_ore_unitario FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento).' GROUP BY prezzo_ore_unitario, sconto_unitario, tipo_sconto');

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
            $sconto_unitario = $sconto / $ore;
            $subtot = $rst[$i]['tot_prezzo_ore_consuntivo'];
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalse WHERE id='.prepare($id_rivalsa_inps);
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
            $rs = $dbo->fetchArray($query);
            if ($calcolo_ritenuta_acconto == 'Imponibile') {
                $ritenutaacconto = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            } else {
                $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
            }

            $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenuta_acconto, `order`) VALUES('.prepare($id_fattura).', '.prepare($id_intervento).', '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).", 'UNT', 'ore', ".prepare($ore).', '.prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare($calcolo_ritenuta_acconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
            $dbo->query($query);
        }
    }

    $intervento = \Modules\Interventi\Intervento::find($id_intervento);

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
        $query = 'SELECT * FROM co_rivalse WHERE id='.prepare($id_rivalsa_inps);
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
            'idrivalsainps' => $id_rivalsa_inps ?: 0,
            'rivalsainps' => $rivalsainps,
            'idritenutaacconto' => $id_ritenuta_acconto ?: 0,
            'ritenutaacconto' => $ritenutaacconto,
            'order' => orderValue('co_righe_documenti', 'iddocumento', $id_fattura),
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
            $query = 'SELECT * FROM co_rivalse WHERE id='.prepare($id_rivalsa_inps);
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
            $rs = $dbo->fetchArray($query);
            if ($calcolo_ritenuta_acconto == 'Imponibile') {
                $ritenutaacconto = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            } else {
                $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
            }

            $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenuta_acconto, `order`) VALUES('.prepare($id_fattura).', '.prepare($id_intervento).', '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($rsr[$i]['descrizione']).', '.prepare($subtot).', '.prepare($rsr[$i]['sconto']).', '.prepare($rsr[$i]['sconto_unitario']).', '.prepare($rsr[$i]['tipo_sconto']).', '.prepare($rsr[$i]['um']).', '.prepare($rsr[$i]['qta']).', '.prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare($calcolo_ritenuta_acconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
            $dbo->query($query);
        }
    }

    // Aggiunta km come "Trasferta" (se c'è)
    if ($intervento->prezzo_viaggio > 0) {
        // Calcolo iva
        $query = 'SELECT * FROM co_iva WHERE id='.prepare($id_iva);
        $dati = $dbo->fetchArray($query);
        $desc_iva = $dati[0]['descrizione'];

        $subtot = $intervento->prezzo_viaggio;
        $sconto = $intervento->sconto_totale_viaggio;
        $iva = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $dati[0]['indetraibile'];

        // Calcolo rivalsa inps
        $query = 'SELECT * FROM co_rivalse WHERE id='.prepare($id_rivalsa_inps);
        $dati = $dbo->fetchArray($query);
        $rivalsainps = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];

        // Calcolo ritenuta d'acconto
        $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare($id_ritenuta_acconto);
        $dati = $dbo->fetchArray($query);
        if ($calcolo_ritenuta_acconto == 'Imponibile') {
            $ritenutaacconto = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];
        } else {
            $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $dati[0]['percentuale'];
        }

        $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenuta_acconto, `order`) VALUES('.prepare($id_fattura).', '.prepare($id_intervento).', '.prepare($id_conto).', '.prepare($id_iva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare('Trasferta intervento '.$codice.' del '.Translator::dateToLocale($data)).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto).", 'UNT', '', 1, ".prepare($id_rivalsa_inps).', '.prepare($rivalsainps).', '.prepare($id_ritenuta_acconto).', '.prepare($ritenutaacconto).', '.prepare($calcolo_ritenuta_acconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_fattura).'))';
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
