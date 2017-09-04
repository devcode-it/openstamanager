<?php

include_once __DIR__.'/../../../core.php';

/*
    CONSUNTIVO
*/

// Salvo i colori e gli stati degli stati intervento su un array
$colori = [];
$stati = [];
$queryc = 'SELECT * FROM in_statiintervento';
$rsc = $dbo->fetchArray($queryc);
for ($i = 0; $i < sizeof($rsc); ++$i) {
    $colori[$rsc[$i]['idstatointervento']] = $rsc[$i]['colore'];
    $stati[$rsc[$i]['idstatointervento']] = $rsc[$i]['descrizione'];
}

// Contenitore per i totali interventi per stato
$totale_x_stato = [];

// Interventi collegati
$q = 'SELECT *, (SELECT orario_inizio FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento = in_interventi.id) AS data, (SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS costo_ore_unitario, (SELECT costo_km FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS costo_km_unitario, (SELECT SUM(costo_diritto_chiamata) FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS dirittochiamata, (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS km, (SELECT SUM(prezzo_ore_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`, (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo` FROM co_righe_contratti INNER JOIN in_interventi ON co_righe_contratti.idintervento=in_interventi.id WHERE co_righe_contratti.idcontratto='.prepare($id_record).' ORDER BY data DESC';
$rscontratti = $dbo->fetchArray($q);

$totale_ore = 0.0;
$totale_km = 0.0;
$totale = 0;
$interventi = [];
$ore = [];
$km = [];
$ntecnici = [];
$tecnici = [];
$costi_orari = [];
$costi_km = [];
$idinterventi = [];

$tot_ore_consuntivo = [];
$tot_km_consuntivo = [];
$tot_diritto_chiamata = [];

if (!empty($rscontratti)) {
    foreach ($rscontratti as $r) {
        $totale_ore = 0;
        $totale_km = 0;
        $totale_diritto_chiamata = 0;

        // Lettura numero tecnici collegati all'intervento
        $query = 'SELECT an_anagrafiche.idanagrafica, prezzo_ore_consuntivo, prezzo_km_consuntivo, prezzo_ore_unitario, prezzo_km_unitario, prezzo_dirittochiamata, ragione_sociale, orario_inizio, orario_fine, in_interventi_tecnici.km FROM in_interventi_tecnici LEFT OUTER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE idintervento='.prepare($r['id']);
        $rst = $dbo->fetchArray($query);
        $n_tecnici = sizeof($rst);
        $tecnici_full = '';

        $t = 0;

        for ($j = 0; $j < $n_tecnici; ++$j) {
            $t1 = datediff('n', $rst[$j]['orario_inizio'], $rst[$j]['orario_fine']);

            $orario = '';

            if (floatval($t1) > 0) {
                $orario .= date('d/m/Y H:i', strtotime($rst[$j]['orario_inizio'])).' - '.date('d/m/Y H:i', strtotime($rst[$j]['orario_fine']));
            }

            $tecnici_full .= $rst[$j]['ragione_sociale'].' ('.$orario.')<br><small>'.Translator::numberToLocale($t1 / 60).'h x '.Translator::numberToLocale($rst[$j]['prezzo_ore_unitario']).' &euro;/h<br>'.Translator::numberToLocale($rst[$j]['km']).'km x '.Translator::numberToLocale($rst[$j]['prezzo_km_unitario']).' km/h<br>'.Translator::numberToLocale($rst[$j]['prezzo_dirittochiamata']).'&euro; d.c.</small><br><br>';

            // Conteggio ore totali
            $t += $t1 / 60;

            $totale_ore += $rst[$j]['prezzo_ore_consuntivo'];
            $totale_km += $rst[$j]['prezzo_km_consuntivo'];
            $totale_diritto_chiamata += $rst[$j]['prezzo_dirittochiamata'];
        }

        $totale_ore_impiegate += $t;

        $desc = nl2br($r['descrizione']);
        $line = Modules::link('Interventi', $r['id'], str_replace(['_NUM_', '_DATE_'], [$r['codice'], Translator::dateToLocale($r['data'])], tr('Intervento <b>_NUM_</b> del <b>_DATE_</b>'))).'<br>'.$desc;

        // Inutilizzati
        $contratti[] = $line;
        $tot_ore_consuntivo[] = $totale_ore;
        $tot_dirittochiamata[] = $totale_diritto_chiamata;
        $idinterventi[] = "'".$rscontrattii[0]['idintervento']."'";
        $ntecnici[] = $n_tecnici;

        // Utilizzati
        $tot_km_consuntivo[] = $totale_km;
        $tecnici[] = $tecnici_full;
        $interventi[] = $line;
    }
}

// Tabella con riepilogo interventi e ore
if (!empty($rscontratti)) {
    echo '
<table class="table table-bordered table-condensed">
    <tr>
        <th width="20%">'.tr('Interventi').'</th>
        <th>'.tr('Tecnici').'</th>
        <th width="160">'.tr('Subtotale contratto').'</th>';

    if ($stato == 'aperto' || $stato == 'in attesa') {
        echo '
        <th></th>';
    }
    echo '
    </tr>';

    //  Tabella con i dati
    for ($i = 0; $i < sizeof($interventi); ++$i) {
        echo '
    <tr style="background:'.$colori[$r['idstatointervento']].';">
        <td width="250">
            '.$interventi[$i].'
        </td>';

        echo '
        <td>
            '.$tecnici[$i].'
        </td>';

        $subtotale = $tot_ore_consuntivo[$i] + $km[$i] * $costo_km[$i] + $diritto_chiamata[$i];
        echo '
        <td align="right">
            '.Translator::numberToLocale($subtotale).'
        </td>';

        if ($stato == 'Accettato' || $stato == 'In lavorazione') {
            echo "
        <td>
            <a href=\"javascript:;\" onclick=\"if( confirm('Rimuovere questo intervento dal contratto?') ){ location.href='".$rootdir.'/editor.php?id_module='.Modules::getModule('Contratti')['id'].'&id_record='.$id_record.'&op=unlink&idintervento='.$r['id']."'; }\"><i class='fa fa-unlink'></i></a>
        </td>";
        }
        echo '
    </tr>';

        $totale += $subtotale;
        $totale_x_stato[$r['idstatointervento']] += $subtotale;

        // Mostro gli articoli collegati a questo intervento
        $query = 'SELECT * FROM mg_articoli_interventi WHERE idintervento='.prepare($r['id']);
        $rs2 = $dbo->fetchArray($query);
        if (sizeof($rs2) > 0) {
            echo '
    <tr>
        <td colspan="9">
            <table width="100%" cellspacing="0" align="center">
                <tr>
                    <th width="20"></th>
                    <th colspan="2">'.tr('Articoli utilizzati').':</th>
                </tr>

                <tr>
                    <td></td>
                    <th>'.tr('Articolo').'</th>
                    <th>'.tr('Q.tà').'</th>
                    <th>'.tr('Prezzo unitario').'</th>
                    <th>'.tr('Subtot').'</th>
                </tr>';

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                echo '
                <tr>
                    <td></td>';

                // Articolo
                echo '
                    <td>
                        '.Modules::link('Articoli', $rs2[$j]['idarticolo'], $rs2[$j]['descrizione']);

                if ($rs2[$i]['lotto'] != '') {
                    echo '<br>'.tr('Lotto').': '.$rs2[$i]['lotto'];
                }
                if ($rs2[$i]['serial'] != '') {
                    echo '<br>'.tr('SN').': '.$rs2[$i]['serial'];
                }
                if ($rs2[$i]['altro'] != '') {
                    echo '<br>'.$rs2[$i]['altro'];
                }

                echo '
                    </td>';

                // Q.tà
                echo '
                    <td>'.Translator::numberToLocale($rs2[$j]['qta']).'</td>';

                // Prezzo di vendita
                echo '
                    <td>'.Translator::numberToLocale($rs2[$j]['prezzo_vendita']).'</td>';

                // Subtotale consuntivo
                $netto = $rs2[$j]['prezzo_vendita'] * $rs2[$j]['qta'];
                echo '
                    <td>'.Translator::numberToLocale($netto).'</td>
                </tr>';

                $totale += $netto;
                $totale += $netto;
                $totale_x_stato[$r['idstatointervento']] += $netto;
            }
            echo '
            </table>
        </td>
    </tr>';
        }

        /*
            Elenco righe di spese aggiuntive
        */
        $query = 'SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($r['id']).' ORDER BY id ASC';
        $rs2 = $dbo->fetchArray($query);
        if (sizeof($rs2) > 0) {
            echo '
    <tr>
        <td colspan="9">
            <table class="table table-striped table-hover table-bordered">
                <tr>
                    <th></th>
                    <th colspan="4">'.tr('Spese aggiuntive').':</th>
                </tr>

                <tr>
                    <td></td>
                    <th>'.tr('Descrizione').'</th>
                    <th>'.tr('Q.tà').'</th>
                    <th>'.tr('Prezzo unitario').'</th>
                    <th>'.tr('Subtot').'</th>
                </tr>';

            // Righe
            for ($j = 0; $j < sizeof($rs2); ++$j) {
                echo '
                <tr>
                    <td></td>';

                // Descrizione
                echo '
                    <td>'.$rs2[$j]['descrizione'].'</td>';

                // Quantità
                $qta = $rs2[$j]['qta'];
                echo '
                    <td>'.Translator::numberToLocale($rs2[$j]['qta']).'</td>';

                // Prezzo unitario
                $netto = $rs2[$j]['prezzo'];
                echo '
                    <td>'.Translator::numberToLocale($netto).'</td>';

                // Prezzo totale
                $subtotale = $rs2[$j]['prezzo'] * $rs2[$j]['qta'];
                echo '
                    <td>'.Translator::numberToLocale($subtotale).'</td>
                </tr>';

                $totale += $subtotale;
                $totale += $subtotale;
                $totale_x_stato[$r['idstatointervento']] += $subtotale;
            }
            echo '
            </table>
        </td>
    </tr>';
        }
    }

    // Totali
    echo '
    <tr>
        <td colspan="2" align="right"><b>'.tr('Totale').'</b></td>
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale).'</b></big>
        </td>
    </tr>';

    // Totali per stato
    echo '
    <tr>
        <td colspan="3"><br><b>'.strtoupper(tr('Totale interventi per stato')).'</b></td>
    </tr>';

    foreach ($totale_x_stato as $stato => $tot) {
        echo '
    <tr>
        <td align="right" colspan="2">
            <big><b style="background:'.$colori[$stato].';">'.$stati[$stato].':</b></big>
        </td>

        <td align="right">
            <big><b>'.Translator::numberToLocale($tot).'</b></big>
        </td>
    </tr>';
    }
    echo '
</table>';
}

/*
    Bilancio del contratto
*/
$rs = $dbo->fetchArray('SELECT SUM(subtotale) AS budget FROM co_righe2_contratti WHERE idcontratto='.prepare($id_record));
$budget = $rs[0]['budget'];

$rs = $dbo->fetchArray("SELECT SUM(qta) AS totale_ore FROM `co_righe2_contratti` WHERE um='ore' AND idcontratto=".prepare($id_record));
$contratto_tot_ore = $rs[0]['totale_ore'];

$diff = Translator::numberToLocale(floatval($budget) - floatval($totale));
if ($diff > 0) {
    $bilancio = '<span class="text-success"><big>'.$diff.' &euro;</big></span>';
} elseif ($diff < 0) {
    $bilancio = '<span class="text-danger"><big>'.$diff.' &euro;</big></span>';
} else {
    $bilancio = '<span><big>'.$diff.' &euro;</big></span>';
}

echo '
<div class="well text-center">
    <big>
        <b>Rapporto budget/spesa</b>:<br>
        '.$bilancio.'
    </big>
    <br><br>';

$diff2 = Translator::numberToLocale(floatval($contratto_tot_ore) - floatval($totale_ore_impiegate));
echo '
    <big>
        Ore residue: '.$diff2.'<br>
        Ore erogate: '.Translator::numberToLocale($totale_ore_impiegate).'<br>
        Ore in contratto: '.Translator::numberToLocale($contratto_tot_ore).'
    </big>
</div>';

/*
    Stampa consuntivo
*/
echo '
<div class="text-center">
    <a class="btn btn-primary" href="'.$rootdir.'/pdfgen.php?ptype=contratti_cons&amp;idcontratto='.$id_record.'" target="_blank">
        <i class="fa fa-print"></i><br>'.tr('Stampa consuntivo').'
    </a>
</div>';
