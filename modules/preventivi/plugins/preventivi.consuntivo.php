<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

use Modules\Interventi\Intervento;

// CONSUNTIVO

// Tabella con riepilogo interventi
$interventi = Intervento::where('id_preventivo', $id_record)->get();
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
        <td class="text-right">
            <b><big>'.tr('Totale').'</big></b>
        </td>';

    echo '
        <td class="text-right">
            <big><b>'.numberFormat($totale_ore).'</b></big>
        </td>';

    echo '
        <td class="text-right">
            <big><b>'.numberFormat($totale_km).'</b></big>
        </td>';

    echo '
        <td class="text-right">
            <big><b>'.moneyFormat($totale_costo).'</b></big>
        </td>';

    echo '
        <td class="text-right">
            <big><b>'.moneyFormat($totale_addebito).'</b></big>
        </td>';

    echo '
        <td class="text-right">
            <big><b>'.moneyFormat($totale).'</b></big>
        </td>
    </tr>';

    $stati = $interventi->groupBy('idstatointervento');
    if (count($stati) > 0) {
        // Totali per stato
        echo '
        <tr>
            <td colspan="6">
                <br><b>'.tr('Totale interventi per stato', [], ['upper' => true]).'</b>
            </td>
        </tr>';

        foreach ($stati as $interventi_collegati) {
            $stato = $interventi_collegati->first()->stato;
            $totale_stato = sum(array_column($interventi_collegati->toArray(), 'totale_imponibile'));

            echo '
        <tr>
            <td colspan="3"></td>

            <td class="text-right" colspan="2" style="background:'.$stato->colore.';">
                <big><b>'.$stato->descrizione.':</b></big>
            </td>

            <td class="text-right">
                <big><b>'.moneyFormat($totale_stato).'</b></big>
            </td>
        </tr>';
        }
    }

    echo '
</table>';
}

// Bilancio del preventivo
$budget = $preventivo->totale_imponibile;
$diff = sum($budget, -$totale);

echo '
<div class="well text-center">
    <br><span><big>
        <b>'.tr('Rapporto budget/spesa').':<br>';
if ($diff > 0) {
    echo '
        <span class="text-success"><big>+'.moneyFormat($diff).'</big></span>';
} elseif ($diff < 0) {
    echo '
        <span class="text-danger"><big>'.moneyFormat($diff).'</big></span>';
} else {
    echo '
        <span><big>'.moneyFormat($diff).'</big></span>';
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
    '.Prints::getLink('Consuntivo preventivo', $id_record, 'btn-primary', tr('Stampa consuntivo')).'
</div>';

// Aggiunta interventi se il preventivo é aperto o in attesa o pagato (non si possono inserire interventi collegati ad altri preventivi)
if (in_array($record['stato'], ['Accettato', 'In lavorazione', 'Pagato'])) {
    echo '
<form action="" method="post">
    <input type="hidden" name="op" value="addintervento">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Aggiungi un altro intervento a questo preventivo').'", "name": "idintervento", "values": "query=SELECT id, CONCAT(\'Intervento \', codice, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta), \'%d/%m/%Y\')) AS descrizione FROM in_interventi WHERE id_preventivo IS NULL AND id NOT IN( SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND id NOT IN( SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL) AND idanagrafica='.prepare($record['idanagrafica']).'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right" onclick="if($(\'#idintervento\').val() && confirm(\'Aggiungere questo intervento al preventivo?\'){ $(this).parent().submit(); }">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
    </div>
</form>';
}
