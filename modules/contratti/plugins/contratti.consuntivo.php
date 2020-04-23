<?php

include_once __DIR__.'/../../../core.php';

use Modules\Interventi\Intervento;

// Tabella con riepilogo interventi
$interventi = Intervento::where('id_contratto', $id_record)->get();
if (!empty($interventi)) {
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
    foreach ($interventi as $intervento) {
        // Riga per il singolo intervento
        echo '
    <tr style="background:'.$intervento->stato->colore.';">
        <td>
            <a href="javascript:;" class="btn btn-primary btn-xs" onclick="$(\'#dettagli_'.$intervento->id.'\').toggleClass(\'hide\'); $(this).find(\'i\').toggleClass(\'fa-plus\').toggleClass(\'fa-minus\');"><i class="fa fa-plus"></i></a>
            '.Modules::link('Interventi', $intervento->id, tr('Intervento num. _NUM_ del _DATE_', [
                '_NUM_' => $intervento->codice,
                '_DATE_' => Translator::dateToLocale($intervento->inizio),
            ])).'
        </td>

        <td class="text-right">
            '.numberFormat($intervento->ore_totali).'
        </td>

        <td class="text-right">
            '.numberFormat($intervento->km_totali).'
        </td>

        <td class="text-right">
            '.moneyFormat($intervento->spesa).'
        </td>

        <td class="text-right">
            '.moneyFormat($intervento->imponibile).'
        </td>

        <td class="text-right">
            '.moneyFormat($intervento->totale_imponibile).'
        </td>
    </tr>';

        // Riga con dettagli
        echo '
    <tr class="hide" id="dettagli_'.$intervento->id.'">
        <td colspan="6">';

        // Lettura sessioni di lavoro
        $sessioni = $intervento->sessioni;
        if (!empty($sessioni)) {
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

            foreach ($sessioni as $sessione) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto_ore = !empty($sessione->sconto_totale_manodopera) ? '<br><span class="label label-danger">'.moneyFormat(-$sessione->sconto_totale_manodopera).'</span>' : '';
                $sconto_km = !empty($sessione->sconto_totale_viaggio) ? '<br><span class="label label-danger">'.moneyFormat(-$sessione->sconto_totale_viaggio).'</span>' : '';

                echo '
                <tr>
                    <td>'.$sessione->anagrafica->ragione_sociale.'</td>
                    <td>'.$sessione->tipo->descrizione.'</td>
                    <td class="text-right">'.numberFormat($sessione->ore).'</td>
                    <td class="text-right">'.numberFormat($sessione->km).'</td>
                    <td class="text-right danger">'.moneyFormat($sessione->costo_manodopera).'</td>
                    <td class="text-right danger">'.moneyFormat($sessione->costo_viaggio).'</td>
                    <td class="text-right danger">'.moneyFormat($sessione->costo_diritto_chiamata).'</td>
                    <td class="text-right success">'.moneyFormat($sessione->prezzo_manodopera).$sconto_ore.'</td>
                    <td class="text-right success">'.moneyFormat($sessione->prezzo_viaggio).$sconto_km.'</td>
                    <td class="text-right success">'.moneyFormat($sessione->prezzo_diritto_chiamata).'</td>
                </tr>';
            }

            echo '
            </table>';
        }

        // Lettura articoli utilizzati
        $articoli = $intervento->articoli;
        if (!$articoli->isEmpty()) {
            echo '
            <table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Materiale').'</th>
                    <th width="120">'.tr('Q.tà').'</th>
                    <th width="150">'.tr('Prezzo di acquisto').'</th>
                    <th width="150">'.tr('Prezzo di vendita').'</th>
                </tr>';

            foreach ($articoli as $articolo) {
                $sconto = !empty($articolo->sconto) ? '<br><span class="label label-danger">'.moneyFormat(-$articolo->sconto).'</span>' : '';

                echo '
                <tr>
                    <td>
                        '.Modules::link('Articoli', $articolo->idarticolo, $articolo->descrizione).'
                    </td>
                    <td class="text-right">'.numberFormat($articolo->qta, 'qta').'</td>
                    <td class="text-right danger">'.moneyFormat($articolo->spesa).'</td>
                    <td class="text-right success">'.moneyFormat($articolo->imponibile).$sconto.'</td>
                </tr>';
            }

            echo '
            </table>';
        }

        // Lettura spese aggiuntive
        $righe = $intervento->righe;
        if (!$righe->isEmpty()) {
            echo '
            <table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Altre spese').'</th>
                    <th width="120">'.tr('Q.tà').'</th>
                    <th width="150">'.tr('Prezzo di acquisto').'</th>
                    <th width="150">'.tr('Prezzo di vendita').'</th>
                </tr>';

            foreach ($righe as $riga) {
                $sconto = !empty($riga->sconto) ? '<br><span class="label label-danger">'.moneyFormat(-$riga->sconto).'</span>' : '';

                echo '
                <tr>
                    <td>
                        '.$riga->descrizione.'
                    </td>
                    <td class="text-right">'.numberFormat($riga->qta, 'qta').'</td>
                    <td class="text-right danger">'.moneyFormat($riga->spesa).'</td>
                    <td class="text-right success">'.moneyFormat($riga->imponibile).$sconto.'</td>
                </tr>';
            }

            echo '
            </table>';
        }

        echo '
        </td>
    </tr>';
    }

    $array_interventi = $interventi->toArray();
    $totale_ore = sum(array_column($array_interventi, 'ore_totali'));
    $totale_km = sum(array_column($array_interventi, 'km_totali'));
    $totale_costo = sum(array_column($array_interventi, 'spesa'));
    $totale_addebito = sum(array_column($array_interventi, 'imponibile'));
    $totale = sum(array_column($array_interventi, 'totale_imponibile'));

    // Totali
    echo '
    <tr>
        <td align="right">
            <b><big>'.tr('Totale').'</big></b>
        </td>';

    echo '
        <td align="right">
            <big><b>'.numberFormat($totale_ore).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.numberFormat($totale_km).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.moneyFormat($totale_costo).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.moneyFormat($totale_addebito).'</b></big>
        </td>';

    echo '
        <td align="right">
            <big><b>'.moneyFormat($totale).'</b></big>
        </td>
    </tr>';

    // Totali per stato
    echo '
    <tr>
        <td colspan="6">
            <br><b>'.tr('Totale interventi per stato', [], ['upper' => true]).'</b>
        </td>
    </tr>';

    $stati = $interventi->groupBy('idstatointervento');
    foreach ($stati as $interventi_collegati) {
        $stato = $interventi_collegati->first()->stato;
        $totale_stato = sum(array_column($interventi_collegati->toArray(), 'totale_imponibile'));

        echo '
    <tr>
        <td colspan="3"></td>

        <td align="right" colspan="2" style="background:'.$stato->colore.';">
            <big><b>'.$stato->descrizione.':</b></big>
        </td>

        <td align="right">
            <big><b>'.moneyFormat($totale_stato).'</b></big>
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
