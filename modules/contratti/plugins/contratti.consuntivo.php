<?php

include_once __DIR__.'/../../../core.php';

/* CONSUNTIVO */

// Salvo i colori e gli stati degli stati intervento su un array
$colori = [];
$stati = [];
$queryc = 'SELECT * FROM in_statiintervento';
$rsc = $dbo->fetchArray($queryc);
for ($i = 0; $i < sizeof($rsc); ++$i) {
    $colori[$rsc[$i]['idstatointervento']] = $rsc[$i]['colore'];
    $stati[$rsc[$i]['idstatointervento']] = $rsc[$i]['descrizione'];
}

$totale_costo = 0;
$totale_addebito = 0;
$totale = 0;

$totale_stato = [];

// Tabella con riepilogo interventi
$rsi = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.idstatointervento, 
       (SELECT completato FROM in_statiintervento WHERE in_statiintervento.idstatointervento = in_interventi.idstatointervento) AS completato,
       (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS inizio,
       (SELECT SUM(ore) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS ore,
       (SELECT MIN(km) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS km
    FROM co_promemoria
    INNER JOIN in_interventi ON co_promemoria.idintervento=in_interventi.id
    WHERE co_promemoria.idcontratto='.prepare($id_record).'
UNION
    SELECT in_interventi.id, in_interventi.idstatointervento,  
        (SELECT completato FROM in_statiintervento WHERE in_statiintervento.idstatointervento = in_interventi.idstatointervento) AS completato,
        (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS inizio,
        (SELECT SUM(ore) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS ore,
        (SELECT MIN(km) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS km
    FROM in_interventi
    WHERE id_contratto = '.prepare($id_record).'
ORDER BY id DESC');

if (!empty($rsi)) {
    echo '
<table class="table table-bordered table-condensed">
    <tr>
        <th>'.tr('Attività').'</th>
        <th width="100">'.tr('Ore').'</th>
        <th width="100">'.tr('Km').'</th>
        <th width="120">'.tr('Costo').'</th>
        <th width="120">'.tr('Addebito').'</th>
        <th width="120">'.tr('Tot. scontato').'</th>
    </tr>';

    // Tabella con i dati
    foreach ($rsi as $int) {
        $intervento = \Modules\Interventi\Intervento::find($int['id']);
        $totale_stato[$int['idstatointervento']] = sum($totale_stato[$int['idstatointervento']], $intervento->totale_imponibile);

        // Riga intervento singolo
        echo '
    <tr style="background:'.$colori[$int['idstatointervento']].';">
        <td>
            <a href="javascript:;" class="btn btn-primary btn-xs" onclick="$(\'#dettagli_'.$int['id'].'\').toggleClass(\'hide\'); $(this).find(\'i\').toggleClass(\'fa-plus\').toggleClass(\'fa-minus\');"><i class="fa fa-plus"></i></a>
            '.Modules::link('Interventi', $int['id'], tr('Intervento _NUM_ del _DATE_', [
                '_NUM_' => $int['codice'],
                '_DATE_' => Translator::dateToLocale($int['inizio']),
            ])).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['ore']).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['km']).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($intervento->spesa).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($intervento->imponibile).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($intervento->totale_imponibile).'
        </td>
    </tr>';

        // Riga con dettagli
        echo '
    <tr class="hide" id="dettagli_'.$int['id'].'">
        <td colspan="6">';

        /**
         * Lettura sessioni di lavoro.
         */
        $rst = $dbo->fetchArray('SELECT in_interventi_tecnici.*, ragione_sociale FROM in_interventi_tecnici LEFT OUTER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica WHERE idintervento='.prepare($int['id']));
        if (!empty($rst)) {
            echo '
            <table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Tecnico').'</th>
                    <th width="230">'.tr('Tipo attività').'</th>
                    <th width="120">'.tr('Ore').'</th>
                    <th width="120">'.tr('Km').'</th>
                    <th width="120">'.tr('Costo ore').'</th>
                    <th width="120">'.tr('Costo km').'</th>
                    <th width="120">'.tr('Diritto ch.').'</th>
                    <th width="120">'.tr('Prezzo ore').'</th>
                    <th width="120">'.tr('Prezzo km').'</th>
                    <th width="120">'.tr('Diritto ch.').'</th>
                </tr>';

            foreach ($rst as $r) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto_ore = ($r['sconto'] != 0) ? '<br><span class="label label-danger">'.moneyFormat(-$r['sconto']).'</span>' : '';
                $sconto_km = ($r['scontokm'] != 0) ? '<br><span class="label label-danger">'.moneyFormat(-$r['scontokm']).'</span>' : '';

                // Aggiungo lo sconto globale nel totale ore
                if ($int['sconto_globale'] > 0) {
                    $sconto_ore .= ' <span class="label label-danger">'.moneyFormat(-$int['sconto_globale']).'</span>';
                }

                echo '
                <tr>
                    <td>'.$r['ragione_sociale'].'</td>
                    <td>'.$r['idtipointervento'].'</td>
                    <td class="text-right">'.Translator::numberToLocale($r['ore']).'</td>
                    <td class="text-right">'.Translator::numberToLocale($r['km']).'</td>
                    <td class="text-right danger">'.Translator::numberToLocale($r['prezzo_ore_consuntivo_tecnico']).'</td>
                    <td class="text-right danger">'.Translator::numberToLocale($r['prezzo_km_consuntivo_tecnico']).'</td>
                    <td class="text-right danger">'.Translator::numberToLocale($r['prezzo_dirittochiamata_tecnico']).'</td>
                    <td class="text-right success">'.Translator::numberToLocale($r['prezzo_ore_consuntivo']).$sconto_ore.'</td>
                    <td class="text-right success">'.Translator::numberToLocale($r['prezzo_km_consuntivo']).$sconto_km.'</td>
                    <td class="text-right success">'.Translator::numberToLocale($r['prezzo_dirittochiamata']).'</td>
                </tr>';
            }

            echo '
            </table>';
        }

        /**
         * Lettura articoli utilizzati.
         */
        $rst = $dbo->fetchArray('SELECT * FROM mg_articoli_interventi WHERE idintervento='.prepare($int['id']));
        if (!empty($rst)) {
            echo '
            <table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Materiale').'</th>
                    <th width="120">'.tr('Q.tà').'</th>
                    <th width="150">'.tr('Prezzo di acquisto').'</th>
                    <th width="150">'.tr('Prezzo di vendita').'</th>
                </tr>';

            foreach ($rst as $r) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto = ($r['sconto'] != 0) ? '<br><span class="label label-danger">'.moneyFormat(-$r['sconto']).'</span>' : '';

                echo '
                <tr>
                    <td>
                        '.Modules::link('Articoli', $r['idarticolo'], $r['descrizione']).(!empty($extra) ? '<small class="help-block">'.implode(', ', $extra).'</small>' : '').'
                    </td>
                    <td class="text-right">'.Translator::numberToLocale($r['qta'], 'qta').'</td>
                    <td class="text-right danger">'.Translator::numberToLocale($r['prezzo_acquisto'] * $r['qta']).'</td>
                    <td class="text-right success">'.Translator::numberToLocale($r['prezzo_vendita'] * $r['qta']).$sconto.'</td>
                </tr>';
            }

            echo '
            </table>';
        }

        /**
         * Lettura spese aggiuntive.
         */
        $rst = $dbo->fetchArray('SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($int['id']));
        if (!empty($rst)) {
            echo '
            <table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Altre spese').'</th>
                    <th width="120">'.tr('Q.tà').'</th>
                    <th width="150">'.tr('Prezzo di acquisto').'</th>
                    <th width="150">'.tr('Prezzo di vendita').'</th>
                </tr>';

            foreach ($rst as $r) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto = ($r['sconto'] != 0) ? '<br><span class="label label-danger">'.moneyFormat(-$r['sconto']).'</span>' : '';

                echo '
                <tr>
                    <td>
                        '.$r['descrizione'].'
                    </td>
                    <td class="text-right">'.Translator::numberToLocale($r['qta'], 'qta').'</td>
                    <td class="text-right danger">'.Translator::numberToLocale($r['prezzo_acquisto'] * $r['qta']).'</td>
                    <td class="text-right success">'.Translator::numberToLocale($r['prezzo_vendita'] * $r['qta']).$sconto.'</td>
                </tr>';
            }

            echo '
            </table>';
        }

        echo '
        </td>
    </tr>';

        $totale_ore_interventi += $int['ore'];
        $totale_ore_completate += !empty($int['completato']) ? $int['ore'] : 0;
        $totale_km += $int['km'];
        $totale_costo += $intervento->spesa;
        $totale_addebito += $intervento->imponibile;
        $totale += $intervento->totale_imponibile;
    }

    // Totali
    echo '
    <tr>
        <td align="right">
            <b><big>'.tr('Totale').'</big></b>
        </td>';

    echo '
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale_ore_interventi).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale_km).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale_costo).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale_addebito).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale).'</b></big>
        </td>
    </tr>';

    // Totali per stato
    echo '
    <tr>
        <td colspan="6">
            <br><b>'.tr('Totale interventi per stato', [], ['upper' => true]).'</b>
        </td>
    </tr>';

    foreach ($totale_stato as $stato => $tot) {
        echo '
    <tr>
        <td colspan="3"></td>

        <td align="right" colspan="2" style="background:'.$colori[$stato].';">
            <big><b>'.$stati[$stato].':</b></big>
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
$rs = $dbo->fetchArray('SELECT SUM(subtotale - sconto) AS budget FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));
$budget = $rs[0]['budget'];

$rs = $dbo->fetchArray("SELECT SUM(qta) AS totale_ore FROM `co_righe_contratti` WHERE um='ore' AND idcontratto=".prepare($id_record));
$totale_ore_contratto = $rs[0]['totale_ore'];

$diff = sum($budget, -$totale);

if ($diff > 0) {
    $bilancio = '<span class="text-success"><big>'.moneyFormat($diff).'</big></span>';
} elseif ($diff < 0) {
    $bilancio = '<span class="text-danger"><big>'.moneyFormat($diff).'</big></span>';
} else {
    $bilancio = '<span><big>'.moneyFormat($diff).'</big></span>';
}

echo '
<div class="well text-center">
    <big>
        <b>'.tr('Rapporto budget/spesa').'</b>:<br>
        '.$bilancio.'
    </big>
    <br><br>';

if (!empty($totale_ore_contratto)) {
    echo '
    <div class="row">
        <big class="col-md-4 col-md-offset-4 text-center">
            <table class="table text-left">
                <tr>
                    <td colspan="2">'.tr('Ore in contratto').':</td>
                    <td  colspan="2" class="text-right">'.Translator::numberToLocale($totale_ore_contratto).'</td>
                </tr>
                
                <tr>
                    <td>'.tr('Ore erogate totali').':</td>
                    <td class="text-right">'.Translator::numberToLocale($totale_ore_interventi).'</td>
                    
                    <td>'.tr('Ore residue totali').':</td>
                    <td class="text-right">'.Translator::numberToLocale(floatval($totale_ore_contratto) - floatval($totale_ore_interventi)).'</td>
                </tr>

                <tr>
                    <td>'.tr('Ore erogate concluse').':</td>
                    <td class="text-right">'.Translator::numberToLocale($totale_ore_completate).'</td>
                    
                    <td>'.tr('Ore residue').':</td>
                    <td class="text-right">'.Translator::numberToLocale(floatval($totale_ore_contratto) - floatval($totale_ore_completate)).'</td>
                </tr>
            </table>
        </big>
    </div>';
} else {
    echo '
    <div class="alert alert-info">
        <p>'.tr('Per monitorare il consumo ore, inserisci almeno una riga con unità di misura "ore"').'.</p>
    </div>';
}

    echo '
</div>';

/*
    Stampa consuntivo
*/
echo '
<div class="text-center">
    '.Prints::getLink('Consuntivo contratto', $id_record, 'btn-primary', tr('Stampa consuntivo')).'
</div>';
