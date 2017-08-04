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

$budget = get_imponibile_preventivo($id_record);

$totale_costo = 0;
$totale_addebito = 0;
$totale_scontato = 0;

$totale_stato = [];

// Tabella con riepilogo interventi
$rsi = $dbo->fetchArray('SELECT *, in_interventi.id, vw_activity_subtotal.*, (manodopera_scontato + viaggio_scontato + ricambi_scontato + altro_scontato - vw_activity_subtotal.sconto_globale) AS subtotale, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS inizio, (SELECT SUM(ore) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS ore, (SELECT MIN(km) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS km FROM co_preventivi_interventi INNER JOIN in_interventi ON co_preventivi_interventi.idintervento=in_interventi.id JOIN vw_activity_subtotal ON vw_activity_subtotal.id = in_interventi.id WHERE co_preventivi_interventi.idpreventivo='.prepare($id_record).' ORDER BY co_preventivi_interventi.idintervento DESC');
if (!empty($rsi)) {
    echo '
<table class="table table-bordered table-condensed">
    <tr>
        <th>'._('Attività').'</th>
        <th width="100">'._('Ore').'</th>
        <th width="100">'._('Km').'</th>
        <th width="120">'._('Costo').'</th>
        <th width="120">'._('Addebito').'</th>
        <th width="120">'._('Tot. scontato').'</th>
    </tr>';

    // Tabella con i dati
    foreach ($rsi as $int) {
        $totale_stato[$int['idstatointervento']] = sum($totale_stato[$int['idstatointervento']], $int['subtotale']);

        // Riga intervento singolo
        echo '
    <tr style="background:'.$colori[$int['idstatointervento']].';">
        <td>
            <a href="javascript:;" class="btn btn-primary btn-xs" onclick="$(\'#dettagli_'.$int['id'].'\').toggleClass(\'hide\'); $(this).find(\'i\').toggleClass(\'fa-plus\').toggleClass(\'fa-minus\');"><i class="fa fa-plus"></i></a>
            '.Modules::link('Interventi', $int['id'], str_replace(['_NUM_', '_DATE_'], [$int['id'], Translator::dateToLocale($int['inizio'])], 'Intervento _NUM_ del _DATE_')).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['ore']).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['km']).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['manodopera_costo']).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['manodopera_addebito']).'
        </td>

        <td class="text-right">
            '.Translator::numberToLocale($int['manodopera_scontato']).'
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
                    <th>'._('Tecnico').'</th>
                    <th width="230">'._('Tipo attività').'</th>
                    <th width="120">'._('Ore').'</th>
                    <th width="120">'._('Km').'</th>
                    <th width="120">'._('Costo orario').'</th>
                    <th width="120">'._('Costo km').'</th>
                    <th width="120">'._('Diritto ch.').'</th>
                    <th width="120">'._('Prezzo orario').'</th>
                    <th width="120">'._('Prezzo km').'</th>
                    <th width="120">'._('Diritto ch.').'</th>
                </tr>';

            foreach ($rst as $r) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto_ore = ($r['sconto'] != 0) ? '<br><span class="label label-danger">'.Translator::numberToLocale(-$r['sconto']).'</span>' : '';
                $sconto_km = ($r['scontokm'] != 0) ? '<br><span class="label label-danger">'.Translator::numberToLocale(-$r['scontokm']).'</span>' : '';

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
                    <th>'._('Materiale').'</th>
                    <th width="120">'._('Q.tà').'</th>
                    <th width="150">'._('Prezzo di acquisto').'</th>
                    <th width="150">'._('Prezzo di vendita').'</th>
                </tr>';

            foreach ($rst as $r) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto = ($r['sconto'] != 0) ? '<br><span class="label label-danger">'.Translator::numberToLocale(-$r['sconto'] * $r['qta']).'</span>' : '';

                // Info extra (lotto, serial, altro)
                $extra = [];
                if (!empty($r['lotto'])) {
                    $extra[] = '<b>'._('Lotto').'</b>: '.$r['lotto'];
                }
                if (!empty($r['serial'])) {
                    $extra[] = '<b>'._('Serial').'</b>: '.$r['serial'];
                }
                if (!empty($r['altro'])) {
                    $extra[] = '<b>'._('Altro').'</b>: '.$r['altro'];
                }

                echo '
                <tr>
                    <td>
                        '.Modules::link('Articoli', $r['idarticolo'], $r['descrizione']).(!empty($extra) ? '<small class="help-block">'.implode(', ', $extra).'</small>' : '').'
                    </td>
                    <td class="text-right">'.Translator::numberToLocale($r['qta']).'</td>
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
                    <th>'._('Altre spese').'</th>
                    <th width="120">'._('Q.tà').'</th>
                    <th width="150">'._('Prezzo di acquisto').'</th>
                    <th width="150">'._('Prezzo di vendita').'</th>
                </tr>';

            foreach ($rst as $r) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto = ($r['sconto'] != 0) ? '<br><span class="label label-danger">'.Translator::numberToLocale(-$r['sconto'] * $r['qta']).'</span>' : '';

                echo '
                <tr>
                    <td>
                        '.$r['descrizione'].'
                    </td>
                    <td class="text-right">'.Translator::numberToLocale($r['qta']).'</td>
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

        $totale_ore += $int['ore'];
        $totale_km += $int['km'];
        $totale_costo += $int['manodopera_costo'];
        $totale_addebito += $int['manodopera_addebito'];
        $totale_scontato += $int['manodopera_scontato'];
    }

    // Totali
    echo '
    <tr>
        <td align="right">
            <b><big>'._('Totale').'</big></b>
        </td>';

    echo '
        <td align="right">
            <big><b>'.Translator::numberToLocale($totale_ore).'</b></big>
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
            <big><b>'.Translator::numberToLocale($totale_scontato).'</b></big>
        </td>
    </tr>';

    // Totali per stato
    echo '
    <tr>
        <td colspan="6">
            <br><b>'.strtoupper(_('Totale interventi per stato')).'</b>
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
    Bilancio del preventivo
*/
$diff = sum($budget, -$totale_scontato);

echo '
<div class="well text-center">
    <br><span><big>
        <b>'._('Rapporto budget/spesa').':<br>';
if ($budget > $totale_scontato) {
    echo '
        <span class="text-success"><big>+'.Translator::numberToLocale($diff).' &euro;</big></span>';
} elseif ($diff < 0) {
    echo '
        <span class="text-danger"><big>'.$diff.' &euro;</big></span>';
} else {
    echo '
        <span><big>'.$diff.' &euro;</big></span>';
}
    echo '
    </b></big></span>
    <br><br>
</div>';

/*
    Stampa consuntivo
*/
echo '
<div class="text-center">
    <a class="btn btn-primary" href="'.$rootdir.'/pdfgen.php?ptype=preventivi_cons&idpreventivo='.$id_record.'" target="_blank">
        <i class="fa fa-print"></i><br>
        Stampa consuntivo
    </a>
</div>';

/*
    Aggiunta interventi se il preventivo é aperto o in attesa o pagato (non si possono inserire interventi collegati ad altri preventivi)
*/
if ($stato == 'Accettato' || $stato == 'In lavorazione' || $stato = 'Pagato') {
    echo '
<form action="" method="post">
    <input type="hidden" name="op" value="addintervento">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'._('Aggiungi un altro intervento a questo preventivo').'", "name": "idintervento", "values": "query=SELECT id, CONCAT(\'Intervento \', codice, \' del \',  DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta), \'%d/%m/%Y\')) AS descrizione FROM in_interventi WHERE id NOT IN( SELECT idintervento FROM co_preventivi_interventi WHERE idintervento IS NOT NULL) AND id NOT IN( SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND id NOT IN( SELECT idintervento FROM co_righe_contratti WHERE idintervento IS NOT NULL) AND idanagrafica='.prepare($records[0]['idanagrafica']).'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right" onclick="if($(\'#idintervento\').val() && confirm(\'Aggiungere questo intervento al preventivo?\'){ $(this).parent().submit(); }">
                <i class="fa fa-plus"></i> '._('Aggiungi').'
            </button>
		</div>
    </div>
</form>';
}
