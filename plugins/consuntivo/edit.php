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

use Models\Module;
use Modules\Contratti\Contratto;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;

$tipologie = [];
$tecnici = [];
$stati_intervento = [];
$materiali_art = [];
$materiali_righe = [];

// Tabella con riepilogo interventi
if ($id_module == Module::where('name', 'Preventivi')->first()->id) {
    $documento = Preventivo::find($id_record);
    $id_documento = 'id_preventivo';
    $text = tr('Preventivo');
} elseif ($id_module == Module::where('name', 'Contratti')->first()->id) {
    $documento = Contratto::find($id_record);
    $id_documento = 'id_contratto';
    $text = tr('Contratto');
} elseif ($id_module == Module::where('name', 'Ordini cliente')->first()->id) {
    $documento = Ordine::find($id_record);
    $id_documento = 'id_ordine';
    $text = tr('Ordine');
}

$interventi = Intervento::where($id_documento, $id_record)->get();
$totale_ore_completate = 0;

if (!empty($interventi)) {
    echo '
<table class="table table-bordered table-sm">
    <tr>
        <th>'.tr('Attività').'</th>
        <th width="125">'.tr('Ore').'</th>
        <th width="125">'.tr('Km').'</th>
        <th width="145">'.tr('Costo').'</th>
        <th width="145">'.tr('Tot. scontato').'</th>
    </tr>';

    // Tabella con i dati
    foreach ($interventi as $intervento) {
        $totale_ore_completate += !empty($intervento->stato->is_bloccato) ? $intervento->ore_totali : 0;
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
        $sessioni = $intervento->sessioni()->leftJoin('in_tipiintervento', 'in_interventi_tecnici.idtipointervento', 'in_tipiintervento.id')->where('non_conteggiare', 0)->get();
        if (!empty($sessioni)) {
            echo '
            <table class="table table-striped table-sm table-bordered">
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
                $sconto_ore = !empty($sessione->sconto_totale_manodopera) ? '<br><span class="badge badge-danger">'.moneyFormat(-$sessione->sconto_totale_manodopera).'</span>' : '';
                $sconto_km = !empty($sessione->sconto_totale_viaggio) ? '<br><span class="badge badge-danger">'.moneyFormat(-$sessione->sconto_totale_viaggio).'</span>' : '';
                echo '
                <tr>
                    <td>'.$sessione->anagrafica->ragione_sociale.'</td>
                    <td>'.$sessione->tipo->getTranslation('title').'</td>
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
                $tipologie[$sessione->tipo->getTranslation('title')]['ore'] += $sessione->ore;
                $tipologie[$sessione->tipo->getTranslation('title')]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
                $tipologie[$sessione->tipo->getTranslation('title')]['ricavo'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera + $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio + $sessione->prezzo_diritto_chiamata;
                // Raggruppamento per tecnico
                $tecnici[$sessione->anagrafica->ragione_sociale]['ore'] += $sessione->ore;
                $tecnici[$sessione->anagrafica->ragione_sociale]['km'] += $sessione->km;
                $tecnici[$sessione->anagrafica->ragione_sociale]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
                $tecnici[$sessione->anagrafica->ragione_sociale]['ricavo'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera + $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio + $sessione->prezzo_diritto_chiamata;
                // Raggruppamento per stato intervento
                $stati_intervento[$intervento->stato->getTranslation('title')]['colore'] = $intervento->stato->colore;
                $stati_intervento[$intervento->stato->getTranslation('title')]['ore'] += $sessione->ore;
                $stati_intervento[$intervento->stato->getTranslation('title')]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
                $stati_intervento[$intervento->stato->getTranslation('title')]['ricavo'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera + $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio + $sessione->prezzo_diritto_chiamata;
            }
            echo '
            </table>';
        }
        // Lettura articoli utilizzati
        $articoli = $intervento->articoli;
        if (!$articoli->isEmpty()) {
            echo '
            <table class="table table-striped table-sm table-bordered">
                <tr>
                    <th>'.tr('Materiale').'</th>
                    <th width="120">'.tr('Q.tà').'</th>
                    <th width="150">'.tr('Prezzo di acquisto').'</th>
                    <th width="150">'.tr('Prezzo di vendita').'</th>
                </tr>';
            foreach ($articoli as $articolo) {
                $sconto = !empty($articolo->sconto) ? '<br><span class="badge badge-danger">'.moneyFormat(-$articolo->sconto).'</span>' : '';
                echo '
                <tr>
                    <td>
                        '.Modules::link('Articoli', $articolo->idarticolo, $articolo->descrizione).'
                    </td>
                    <td class="text-right">'.numberFormat($articolo->qta, 'qta').'</td>
                    <td class="text-right danger">'.moneyFormat($articolo->spesa).'</td>
                    <td class="text-right success">'.moneyFormat($articolo->imponibile).$sconto.'</td>
                </tr>';
                // Raggruppamento per articolo con lo stesso prezzo
                $ricavo = (string) (($articolo->imponibile - $articolo->sconto) / ($articolo->qta > 0 ? $articolo->qta : 1));
                $costo = (string) ($articolo->spesa / ($articolo->qta > 0 ? $articolo->qta : 1));
                $descrizione = $articolo->articolo->codice.' - '.$articolo->descrizione;
                $materiali_art[$descrizione][$ricavo][$costo]['id'] = $articolo->id;
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
            <table class="table table-striped table-sm table-bordered">
                <tr>
                    <th>'.tr('Altre spese').'</th>
                    <th width="120">'.tr('Q.tà').'</th>
                    <th width="150">'.tr('Prezzo di acquisto').'</th>
                    <th width="150">'.tr('Prezzo di vendita').'</th>
                </tr>';
            foreach ($righe as $riga) {
                $sconto = !empty($riga->sconto) ? '<br><span class="badge badge-danger">'.moneyFormat(-$riga->sconto).'</span>' : '';
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

// Bilancio del documento
$budget = $documento->totale_imponibile;
$righe = $documento->righe;
foreach ($righe as $riga) {
    if ($riga->um == 'ore') {
        $totale_ore_contratto = $riga->qta;
    }
}

$diff = sum($budget, -$totale) - $documento->provvigione;

if ($diff > 0) {
    $bilancio = '<span class="text-success"><big>+ '.moneyFormat($diff).'</big></span>';
} elseif ($diff < 0) {
    $bilancio = '<span class="text-danger"><big>'.moneyFormat($diff).'</big></span>';
} else {
    $bilancio = '<span><big>'.moneyFormat($diff).'</big></span>';
}
echo '
<div class="well text-center">
    <h4>
        <b>'.tr('Rapporto budget/spesa').'</b>:<br>
        '.$bilancio.'
    </h4>
    <br><br>
</div>';
if (!empty($totale_ore_contratto)) {
    echo '
<div>
    <div class="row">
        <div class="col-md-4 offset-md-4 text-center">
            <table class="table text-left table-striped table-bordered">
                <tr>
                    <td>'.tr('Ore a contratto').':</td>
                    <td class="text-right">'.Translator::numberToLocale($totale_ore_contratto).'</td>
                </tr>
                <tr>
                    <td>'.tr('Ore erogate totali').':</td>
                    <td class="text-right">'.Translator::numberToLocale($totale_ore).'</td>
                </tr>
                <tr>
                    <td>'.tr('Ore residue totali').':</td>
                    <td class="text-right">'.Translator::numberToLocale(floatval($totale_ore_contratto) - floatval($totale_ore)).'</td>
                </tr>
                <tr>
                    <td>'.tr('Ore erogate concluse').':</td>
                    <td class="text-right">'.Translator::numberToLocale($totale_ore_completate).'</td>
                </tr>
                <tr>
                    <td>'.tr('Ore residue concluse').':</td>
                    <td class="text-right">'.Translator::numberToLocale(floatval($totale_ore_contratto) - floatval($totale_ore_completate)).'</td>
                </tr>
            </table>
        </div>
    </div>
</div>';
}
echo '
<div class="alert alert-info">
    <p>'.tr('Per monitorare il consumo ore, inserisci almeno una riga con unità di misura "ore"').'.</p>
</div>
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
    if ($tipologia['ricavo']) {
        $margine_prc = (int) (1 - ($tipologia['costo'] / ($tipologia['ricavo'] > 0 ? $tipologia['ricavo'] : 1))) * 100;
        $ricarico_prc = ($tipologia['ricavo'] && $tipologia['costo']) ? (int) ((($tipologia['ricavo'] / ($tipologia['costo'] > 0 ? $tipologia['costo'] : 1)) - 1) * 100) : 100;
    }
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
    if ($tecnico['ricavo']) {
        $margine_prc = (int) (1 - ($tecnico['costo'] / ($tecnico['ricavo'] > 0 ? $tecnico['ricavo'] : 1))) * 100;
        $ricarico_prc = ($tecnico['ricavo'] && $tecnico['costo']) ? (int) ((($tecnico['ricavo'] / ($tecnico['costo'] > 0 ? $tecnico['costo'] : 1)) - 1) * 100) : 100;
    }
    echo '
            <tr>
                <td>'.$key.'</td>
                <td class="text-right">'.(int) $tecnico['km'].'</td>
                <td class="text-right">'.Translator::numberToLocale($tecnico['ore']).'</td>
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
    if ($stato['ricavo']) {
        $margine_prc = (int) (1 - ($stato['costo'] / ($stato['ricavo'] > 0 ? $stato['ricavo'] : 1))) * 100;
        $ricarico_prc = ($stato['ricavo'] && $stato['costo']) ? (int) ((($stato['ricavo'] / ($stato['costo'] > 0 ? $stato['costo'] : 1)) - 1) * 100) : 100;
    }
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
            $margine_prc = (int) (1 - ($materiale['costo'] / ($materiale['ricavo'] > 0 ? $materiale['ricavo'] : 1))) * 100;
            $ricarico_prc = ($materiale['ricavo'] && $materiale['costo']) ? (int) ((($materiale['ricavo'] / ($materiale['costo'] > 0 ? $materiale['costo'] : 1)) - 1) * 100) : 100;
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
    $margine_prc = (int) (1 - ($materiale['costo'] / ($materiale['ricavo'] > 0 ? $materiale['ricavo'] : 1))) * 100;
    $ricarico_prc = ($materiale['ricavo'] && $materiale['costo']) ? (int) ((($materiale['ricavo'] / ($materiale['costo'] > 0 ? $materiale['costo'] : 1)) - 1) * 100) : 100;
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
</div>';

// Tabella totale delle ore,km,costi e totale scontato suddivisi per i mesi in cui sono stati effettuati gli interventi
echo '
<div class="row">
    <div class="col-md-12">
        <table class="table text-left table-striped table-bordered">
            <tr>
                <th>'.tr('Mese').'</th>
                <th width="10%">'.tr('Ore').'</th>
                <th width="10%">'.tr('Km').'</th>
                <th width="16%">'.tr('Costo').'</th>
                <th width="16%">'.tr('Totale scontato').'</th>
            </tr>';
$interventi_per_mese = [];
$totals = ['ore' => 0, 'km' => 0, 'costo' => 0, 'totale' => 0];

foreach ($interventi as $intervento) {
    // Ottieni le sessioni di lavoro per questo intervento
    $sessioni = $intervento->sessioni()
        ->leftJoin('in_tipiintervento', 'in_interventi_tecnici.idtipointervento', 'in_tipiintervento.id')
        ->where('non_conteggiare', 0)
        ->get();

    foreach ($sessioni as $sessione) {
        $mese = date('Y-m', strtotime((string) $sessione->orario_inizio));
        if (!isset($interventi_per_mese[$mese])) {
            $interventi_per_mese[$mese] = [
                'ore' => 0,
                'km' => 0,
                'costo' => 0,
                'totale' => 0,
            ];
        }
        $interventi_per_mese[$mese]['ore'] += $sessione->ore;
        $interventi_per_mese[$mese]['km'] += $sessione->km;
        $interventi_per_mese[$mese]['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
        $interventi_per_mese[$mese]['totale'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera +
                                                $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio +
                                                $sessione->prezzo_diritto_chiamata;

        $totals['ore'] += $sessione->ore;
        $totals['km'] += $sessione->km;
        $totals['costo'] += $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata;
        $totals['totale'] += $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera +
                            $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio +
                            $sessione->prezzo_diritto_chiamata;
    }
}

ksort($interventi_per_mese);
foreach ($interventi_per_mese as $mese => $dati) {
    echo '
            <tr>
                <td>'.ucfirst(Carbon\Carbon::createFromFormat('Y-m', $mese)->translatedFormat('F Y')).'</td>
                <td class="text-right">'.Translator::numberToLocale($dati['ore']).'</td>
                <td class="text-right">'.Translator::numberToLocale($dati['km']).'</td>
                <td class="text-right">'.Translator::numberToLocale($dati['costo']).' €</td>
                <td class="text-right">'.Translator::numberToLocale($dati['totale']).' €</td>
            </tr>';
}

echo '
            <tr class="table-info font-weight-bold">
                <td>'.tr('Totali').'</td>
                <td class="text-right">'.Translator::numberToLocale($totals['ore']).'</td>
                <td class="text-right">'.Translator::numberToLocale($totals['km']).'</td>
                <td class="text-right">'.Translator::numberToLocale($totals['costo']).' €</td>
                <td class="text-right">'.Translator::numberToLocale($totals['totale']).' €</td>
            </tr>';
echo '
        </table>
    </div>
</div>';

/*
    Stampa consuntivo
*/
echo '
<div class="text-center">
    '.Prints::getLink('Consuntivo '.$text, $id_record, 'btn-primary', tr('Stampa consuntivo')).'
</div>';

// Aggiunta interventi se il documento é aperto o in attesa o pagato (non si possono inserire interventi collegati ad altri preventivi)
$query = 'SELECT id, CONCAT(\'Intervento \', codice, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta), \'%d/%m/%Y\')) AS descrizione FROM in_interventi WHERE id_preventivo IS NULL AND id_contratto IS NULL AND id_ordine IS NULL AND id NOT IN( SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND id NOT IN( SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL) AND idanagrafica='.prepare($record['idanagrafica']);

$count = $dbo->fetchNum($query);

echo '<hr>
<form action="" method="post" id="aggiungi-intervento">
    <input type="hidden" name="op" value="addintervento">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-8">
            {[ "type": "select", "label": "'.tr('Aggiungi un intervento a questo documento').' ('.$count.')", "name": "idintervento", "values": "query='.$query.'", "required":"1" ]}
        </div>

    <!-- PULSANTI -->
		<div class="col-md-4">
            <p style="margin-top:-5px;" >&nbsp;</p>
            <button type="button" class="btn btn-primary" onclick="if($(\'#aggiungi-intervento\').parsley().validate() && confirm(\''.tr('Aggiungere questo intervento al documento?').'\') ){ $(\'#aggiungi-intervento\').submit(); }" '.(($record['is_pianificabile'] && !$block_edit) ? '' : 'disabled').'>
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
    </div>
</form>';
