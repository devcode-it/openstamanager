<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

$tipologie = [];
$tecnici = [];
$stati_intervento = [];
$materiali_art = [];
$materiali_righe = [];

// Tabella con riepilogo interventi
$interventi = Intervento::where('id_ordine', $id_record)->get();
if (!empty($interventi)) {
    echo '
<table class="table table-bordered table-condensed">
    <tr>
        <th>'.tr('Attività').'</th>
        <th width="125">'.tr('Ore').'</th>
        <th width="125">'.tr('Km').'</th>
        <th width="145">'.tr('Costo').'</th>
        <th width="145">'.tr('Tot. scontato').'</th>
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
            '.($intervento->ore_totali <= 0 ? '<i class="fa fa-warning tip" style="position:relative;margin-left:-16px;" title="'.tr('Questa sessione è vuota').'" ></i> ' : '').numberFormat($intervento->ore_totali).'
        </td>


        <td class="text-right">
            '.numberFormat($intervento->km_totali).'
        </td>

        <td class="text-right">
            '.moneyFormat($intervento->spesa).'
        </td>

        <td class="text-right">
            '.moneyFormat($intervento->totale_imponibile).'
        </td>
    </tr>';

        // Riga con dettagli
        echo '
    <tr class="hide" id="dettagli_'.$intervento->id.'">
        <td colspan="5">';

        // Lettura sessioni di lavoro
        $sessioni = $intervento->sessioni;
        if (!empty($sessioni)) {
            echo '
            <table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Tecnico').'</th>
                    <th width="210">'.tr('Tipo attività').'</th>
                    <th width="110">'.tr('Ore').'</th>
                    <th width="110">'.tr('Km').'</th>
                    <th width="110">'.tr('Costo ore').'</th>
                    <th width="110">'.tr('Costo km').'</th>
                    <th width="110">'.tr('Diritto ch.').'</th>
                    <th width="110">'.tr('Prezzo ore').'</th>
                    <th width="110">'.tr('Prezzo km').'</th>
                    <th width="110">'.tr('Diritto ch.').'</th>
                </tr>';

            foreach ($sessioni as $sessione) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto_ore = !empty($sessione->sconto_totale_manodopera) ? '<br><span class="label label-danger">'.moneyFormat(-$sessione->sconto_totale_manodopera).'</span>' : '';
                $sconto_km = !empty($sessione->sconto_totale_viaggio) ? '<br><span class="label label-danger">'.moneyFormat(-$sessione->sconto_totale_viaggio).'</span>' : '';

                echo '
                <tr>
                    <td>'.$sessione->anagrafica->ragione_sociale.'</td>
                    <td>'.$sessione->tipo->getTranslation('name').'</td>
                    <td class="text-right">'.numberFormat($sessione->ore).'</td>
                    <td class="text-right">'.numberFormat($sessione->km).'</td>
                    <td class="text-right danger">'.moneyFormat($sessione->costo_manodopera).'</td>
                    <td class="text-right danger">'.moneyFormat($sessione->costo_viaggio).'</td>
                    <td class="text-right danger">'.moneyFormat($sessione->costo_diritto_chiamata).'</td>
                    <td class="text-right success">'.moneyFormat($sessione->prezzo_manodopera).$sconto_ore.'</td>
                    <td class="text-right success">'.moneyFormat($sessione->prezzo_viaggio).$sconto_km.'</td>
                    <td class="text-right success">'.moneyFormat($sessione->prezzo_diritto_chiamata).'</td>
                </tr>';

                // Raggruppamento per tipologia descrizione
                $tipologie[$sessione->tipo->getTranslation('name')]['ore'] += $sessione->ore;
                $tipologie[$sessione->tipo->getTranslation('name')]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
                $tipologie[$sessione->tipo->getTranslation('name')]['ricavo'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera + $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio + $sessione->prezzo_diritto_chiamata;

                // Raggruppamento per tecnico
                $tecnici[$sessione->anagrafica->ragione_sociale]['ore'] += $sessione->ore;
                $tecnici[$sessione->anagrafica->ragione_sociale]['km'] += $sessione->km;
                $tecnici[$sessione->anagrafica->ragione_sociale]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
                $tecnici[$sessione->anagrafica->ragione_sociale]['ricavo'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera + $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio + $sessione->prezzo_diritto_chiamata;

                // Raggruppamento per stato intervento
                $stati_intervento[$intervento->stato->getTranslation('name')]['colore'] = $intervento->stato->colore;
                $stati_intervento[$intervento->stato->getTranslation('name')]['ore'] += $sessione->ore;
                $stati_intervento[$intervento->stato->getTranslation('name')]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
                $stati_intervento[$intervento->stato->getTranslation('name')]['ricavo'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera + $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio + $sessione->prezzo_diritto_chiamata;
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
                        '.Modules::link('Articoli', $articolo->idarticolo, $articolo->getTranslation('name')).'
                    </td>
                    <td class="text-right">'.numberFormat($articolo->qta, 'qta').'</td>
                    <td class="text-right danger">'.moneyFormat($articolo->spesa).'</td>
                    <td class="text-right success">'.moneyFormat($articolo->imponibile).$sconto.'</td>
                </tr>';

                // Raggruppamento per articolo con lo stesso prezzo
                $ricavo = ($articolo->imponibile - $articolo->sconto) / $articolo->qta;
                $costo = $articolo->spesa / $articolo->qta;
                $descrizione = $articolo->articolo->codice.' - '.$articolo->articolo->getTranslation('name');

                $materiali_art[$descrizione][$ricavo][$costo]['id'] = $articolo->articolo->id;
                $materiali_art[$descrizione][$ricavo][$costo]['qta'] += $articolo->qta;
                $materiali_art[$descrizione][$ricavo][$costo]['costo'] += $articolo->spesa;
                $materiali_art[$descrizione][$ricavo][$costo]['ricavo'] += $articolo->imponibile - $articolo->sconto;
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

                // Raggruppamento per riga
                $materiali_righe[$riga->descrizione]['qta'] += $riga->qta;
                $materiali_righe[$riga->descrizione]['costo'] += $riga->spesa;
                $materiali_righe[$riga->descrizione]['ricavo'] += $riga->imponibile - $riga->sconto;
            }

            echo '
            </table>';
        }

        echo '
        </td>
    </tr>';
    }

    $array_interventi = $interventi->toArray();
    $totale_km = sum(array_column($array_interventi, 'km_totali'));
    $totale_costo = sum(array_column($array_interventi, 'spesa'));
    $totale_addebito = sum(array_column($array_interventi, 'imponibile'));
    $totale = sum(array_column($array_interventi, 'totale_imponibile'));
    $totale_ore = sum(array_column($array_interventi, 'ore_totali'));

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
            <big><b>'.moneyFormat($totale).'</b></big>
        </td>
    </tr>
</table>';
}

// Bilancio del preventivo
$budget = $ordine->totale_imponibile;
$diff = sum($budget, -$totale) - $ordine->provvigione;

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

    <div class="row">
        <div class="col-md-6">
            <table class="table text-left table-striped table-bordered">
                <tr>
                    <th>'.tr('Tipologia').'</th>
                    <th width="10%">'.tr('Ore').'</th>
                    <th width="16%">'.tr('Costo').'</th>
                    <th width="16%">'.tr('Ricavo').'</th>
                    <th width="10%">'.tr('Margine').'</th>
                    <th width="10%">'.tr('Ricarico').'</th>
                </tr>';
ksort($tipologie);
foreach ($tipologie as $key => $tipologia) {
    $margine = $tipologia['ricavo'] - $tipologia['costo'];
    $margine_prc = (int) (1 - ($tipologia['costo'] / $tipologia['ricavo'])) * 100;
    $ricarico_prc = ($tipologia['ricavo'] && $tipologia['costo']) ? (int) ((($tipologia['ricavo'] / $tipologia['costo']) - 1) * 100) : 100;
    echo '
                <tr>
                    <td>'.$key.'</td>
                    <td class="text-right">'.Translator::numberToLocale($tipologia['ore']).'</td>
                    <td class="text-right">'.Translator::numberToLocale($tipologia['costo']).' €</td>
                    <td class="text-right">'.Translator::numberToLocale($tipologia['ricavo']).' €</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$margine_prc.'%)</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$ricarico_prc.'%)</td>
                </tr>';
}
echo '
            </table>
        </div>

        <div class="col-md-6">
            <table class="table text-left table-striped table-bordered">
                <tr>
                    <th>'.tr('Tecnici').'</th>
                    <th width="7%">'.tr('km').'</th>
                    <th width="10%">'.tr('Ore').'</th>
                    <th width="16%">'.tr('Costo').'</th>
                    <th width="16%">'.tr('Ricavo').'</th>
                    <th width="10%">'.tr('Margine').'</th>
                    <th width="10%">'.tr('Ricarico').'</th>
                </tr>';
ksort($tecnici);
foreach ($tecnici as $key => $tecnico) {
    $margine = $tecnico['ricavo'] - $tecnico['costo'];
    $margine_prc = (int) (1 - ($tecnico['costo'] / $tecnico['ricavo'])) * 100;
    $ricarico_prc = ($tecnico['ricavo'] && $tecnico['costo']) ? (int) ((($tecnico['ricavo'] / $tecnico['costo']) - 1) * 100) : 100;
    echo '
                <tr>
                    <td>'.$key.'</td>
                    <td class="text-right">'.Translator::numberToLocale($tecnico['ore']).'</td>
                    <td class="text-right">'.(int) $tecnico['km'].'</td>
                    <td class="text-right">'.Translator::numberToLocale($tecnico['costo']).' €</td>
                    <td class="text-right">'.Translator::numberToLocale($tecnico['ricavo']).' €</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$margine_prc.'%)</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$ricarico_prc.'%)</td>
                </tr>';
}
echo '
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <table class="table text-left table-striped table-bordered">
                <tr>
                    <th>'.tr('Stato').'</th>
                    <th width="10%">'.tr('Ore').'</th>
                    <th width="16%">'.tr('Costo').'</th>
                    <th width="16%">'.tr('Ricavo').'</th>
                    <th width="10%">'.tr('Margine').'</th>
                    <th width="10%">'.tr('Ricarico').'</th>
                </tr>';
ksort($stati_intervento);
foreach ($stati_intervento as $key => $stato) {
    $margine = $stato['ricavo'] - $stato['costo'];
    $margine_prc = (int) (1 - ($stato['costo'] / $stato['ricavo'])) * 100;
    $ricarico_prc = ($stato['ricavo'] && $stato['costo']) ? (int) ((($stato['ricavo'] / $stato['costo']) - 1) * 100) : 100;
    echo '
                <tr>
                    <td><div class="img-circle" style="width:18px; height:18px; position:relative; bottom:-2px; background:'.$stato['colore'].'; float:left;"></div> '.$key.'</td>
                    <td class="text-right">'.Translator::numberToLocale($stato['ore']).'</td>
                    <td class="text-right">'.Translator::numberToLocale($stato['costo']).' €</td>
                    <td class="text-right">'.Translator::numberToLocale($stato['ricavo']).' €</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$margine_prc.'%)</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$ricarico_prc.'%)</td>
                </tr>';
}
echo '
            </table>
        </div>

        <div class="col-md-6">
                <table class="table text-left table-striped table-bordered">
                <tr>
                    <th>'.tr('Materiale').'</th>
                    <th width="8%">'.tr('Qtà').'</th>
                    <th width="16%">'.tr('Costo').'</th>
                    <th width="16%">'.tr('Ricavo').'</th>
                    <th width="10%">'.tr('Margine').'</th>
                    <th width="10%">'.tr('Ricarico').'</th>
                </tr>';
ksort($materiali_art);
foreach ($materiali_art as $key => $materiali_array1) {
    foreach ($materiali_array1 as $materiali_array2) {
        foreach ($materiali_array2 as $materiale) {
            $margine = $materiale['ricavo'] - $materiale['costo'];
            $margine_prc = (int) (1 - ($materiale['costo'] / ($materiale['ricavo'] ? $materiale['ricavo'] : 1))) * 100;
            $ricarico_prc = ($materiale['ricavo'] && $materiale['costo']) ? (int) ((($materiale['ricavo'] / ($materiale['costo'] ? $materiale['costo'] : 1)) - 1) * 100) : 100;
            echo '
                <tr>
                    <td>'.Modules::link('Articoli', $materiale['id'], $key).'</td>
                    <td class="text-center">'.$materiale['qta'].'</td>
                    <td class="text-right">'.Translator::numberToLocale($materiale['costo']).' €</td>
                    <td class="text-right">'.Translator::numberToLocale($materiale['ricavo']).' €</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$margine_prc.'%)</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$ricarico_prc.'%)</td>
                </tr>';
        }
    }
}

ksort($materiali_righe);
foreach ($materiali_righe as $key => $materiale) {
    $margine = $materiale['ricavo'] - $materiale['costo'];
    $margine_prc = (int) (1 - ($materiale['costo'] / ($materiale['ricavo'] ? $materiale['ricavo'] : 1))) * 100;
    $ricarico_prc = ($materiale['ricavo'] && $materiale['costo']) ? (int) ((($materiale['ricavo'] / ($materiale['costo'] ? $materiale['costo'] : 1)) - 1) * 100) : 100;
    echo '
                <tr>
                    <td>'.$key.'</td>
                    <td class="text-center">'.$materiale['qta'].'</td>
                    <td class="text-right">'.Translator::numberToLocale($materiale['costo']).' €</td>
                    <td class="text-right">'.Translator::numberToLocale($materiale['ricavo']).' €</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$margine_prc.'%)</td>
                    <td class="text-right '.($margine > 0 ? 'bg-success' : 'bg-danger').'">'.Translator::numberToLocale($margine).' € ('.$ricarico_prc.'%)</td>
                </tr>';
}
echo '
            </table>
        </div>
    </div>
</div>';

/*
    Stampa consuntivo
*/
echo '
<div class="text-center">
    '.Prints::getLink('Consuntivo ordine', $id_record, 'btn-primary', tr('Stampa consuntivo')).'
</div>';
