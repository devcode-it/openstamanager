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

// Cache per le sessioni per evitare query duplicate
$sessioni_cache = [];

/**
 * Funzione helper per calcolare margine, margine percentuale e ricarico percentuale
 */
function calcolaMargini($costo, $ricavo)
{
    $margine = $ricavo - $costo;
    
    if ($ricavo > 0) {
        $margine_prc = (int) ((1 - ($costo / $ricavo)) * 100);
        $ricarico_prc = $costo > 0 ? (int) ((($ricavo / $costo) - 1) * 100) : 100;
    } else {
        $margine_prc = 0;
        $ricarico_prc = 0;
    }
    
    return [
        'margine' => $margine,
        'margine_prc' => $margine_prc,
        'ricarico_prc' => $ricarico_prc,
    ];
}

/**
 * Funzione helper per ottenere le sessioni di un intervento con cache
 */
function getSessioniCache($intervento, &$cache)
{
    $id = $intervento->id;
    
    if (!isset($cache[$id])) {
        $cache[$id] = $intervento->sessioni()
            ->leftJoin('in_tipiintervento', 'in_interventi_tecnici.idtipointervento', 'in_tipiintervento.id')
            ->where('non_conteggiare', 0)
            ->get();
    }
    
    return $cache[$id];
}

/**
 * Funzione helper per calcolare i totali di una sessione
 */
function calcolaTotaliSessione($sessione)
{
    return [
        'costo' => $sessione->costo_manodopera + $sessione->costo_viaggio + $sessione->costo_diritto_chiamata,
        'ricavo' => $sessione->prezzo_manodopera - $sessione->sconto_totale_manodopera +
                    $sessione->prezzo_viaggio - $sessione->sconto_totale_viaggio +
                    $sessione->prezzo_diritto_chiamata,
    ];
}

/**
 * Funzione helper per generare HTML sconto
 */
function getHtmlSconto($sconto)
{
    return !empty($sconto) ? '<br><span class="badge badge-danger">'.moneyFormat(-$sconto).'</span>' : '';
}

/**
 * Funzione helper per ottenere la query degli interventi disponibili
 */
function getQueryInterventiDisponibili($idanagrafica)
{
    global $dbo;
    
    return 'SELECT id, CONCAT(\'Intervento \', codice, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta), \'%d/%m/%Y\')) AS descrizione 
            FROM in_interventi 
            WHERE id_preventivo IS NULL 
                AND id_contratto IS NULL 
                AND id_ordine IS NULL 
                AND id NOT IN( SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) 
                AND id NOT IN( SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL) 
                AND idanagrafica='.prepare($idanagrafica);
}

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

if (empty($documento)) {
    return;
}

$interventi = Intervento::where($id_documento, $id_record)
    ->with(['stato', 'sessioni.tipo', 'sessioni.anagrafica', 'articoli.articolo', 'righe'])
    ->get();
$totale_ore_completate = 0;

if (!empty($interventi)) {
    echo '
<div class="card">
    <div class="card-header bg-info text-white">
        <i class="fa fa-list-alt"></i> '.tr('Riepilogo Interventi').'
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>'.tr('Attività').'</th>
                    <th width="125">'.tr('Ore').'</th>
                    <th width="125">'.tr('Km').'</th>
                    <th width="145">'.tr('Costo').'</th>
                    <th width="145">'.tr('Tot. scontato').'</th>
                </tr>
            </thead>
            <tbody>';

    // Tabella con i dati
    foreach ($interventi as $intervento) {
        $totale_ore_completate += !empty($intervento->stato->is_bloccato) ? $intervento->ore_totali : 0;
        // Riga per il singolo intervento
        echo '
                <tr style="background:'.$intervento->stato->colore.';">
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" onclick="$(\'#dettagli_'.$intervento->id.'\').toggleClass(\'hide\'); $(this).find(\'i\').toggleClass(\'fa-plus\').toggleClass(\'fa-minus\');">
                            <i class="fa fa-plus"></i>
                        </button>
                        '.Modules::link('Interventi', $intervento->id, tr('Intervento num. _NUM_ del _DATE_', [
            '_NUM_' => $intervento->codice,
            '_DATE_' => Translator::dateToLocale($intervento->inizio),
        ])).'
                    </td>

                    <td class="text-right">
                        '.($intervento->ore_totali <= 0 ? '<i class="fa fa-exclamation-triangle text-warning" title="'.tr('Questa sessione è vuota').'"></i> ' : '').numberFormat($intervento->ore_totali).'
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
                    <td colspan="5" class="p-3">';
        // Lettura sessioni di lavoro con cache
        $sessioni = getSessioniCache($intervento, $sessioni_cache);
        if (!$sessioni->isEmpty()) {
            echo '
                <div class="mb-3">
                    <h6 class="font-weight-bold text-primary"><i class="fa fa-users"></i> '.tr('Sessioni di lavoro').'</h6>
                    <table class="table table-sm table-bordered table-striped">
                        <thead>
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
                            </tr>
                        </thead>
                        <tbody>';
            foreach ($sessioni as $sessione) {
                // Visualizzo lo sconto su ore o km se c'è
                $sconto_ore = getHtmlSconto($sessione->sconto_totale_manodopera);
                $sconto_km = getHtmlSconto($sessione->sconto_totale_viaggio);
                echo '
                            <tr>
                                <td>'.$sessione->anagrafica->ragione_sociale.'</td>
                                <td>'.$sessione->tipo->getTranslation('title').'</td>
                                <td class="text-right">'.numberFormat($sessione->ore).'</td>
                                <td class="text-right">'.numberFormat($sessione->km).'</td>
                                <td class="text-right text-danger">'.moneyFormat($sessione->costo_manodopera).'</td>
                                <td class="text-right text-danger">'.moneyFormat($sessione->costo_viaggio).'</td>
                                <td class="text-right text-danger">'.moneyFormat($sessione->costo_diritto_chiamata).'</td>
                                <td class="text-right text-success">'.moneyFormat($sessione->prezzo_manodopera).$sconto_ore.'</td>
                                <td class="text-right text-success">'.moneyFormat($sessione->prezzo_viaggio).$sconto_km.'</td>
                                <td class="text-right text-success">'.moneyFormat($sessione->prezzo_diritto_chiamata).'</td>
                            </tr>';
                
                // Calcola totali sessione
                $totali_sessione = calcolaTotaliSessione($sessione);
                
                // Raggruppamento per tipologia descrizione
                $tipo_title = $sessione->tipo->getTranslation('title');
                $tipologie[$tipo_title]['ore'] = ($tipologie[$tipo_title]['ore'] ?? 0) + $sessione->ore;
                $tipologie[$tipo_title]['costo'] = ($tipologie[$tipo_title]['costo'] ?? 0) + $totali_sessione['costo'];
                $tipologie[$tipo_title]['ricavo'] = ($tipologie[$tipo_title]['ricavo'] ?? 0) + $totali_sessione['ricavo'];
                
                // Raggruppamento per tecnico
                $tecnico_nome = $sessione->anagrafica->ragione_sociale;
                $tecnici[$tecnico_nome]['ore'] = ($tecnici[$tecnico_nome]['ore'] ?? 0) + $sessione->ore;
                $tecnici[$tecnico_nome]['km'] = ($tecnici[$tecnico_nome]['km'] ?? 0) + $sessione->km;
                $tecnici[$tecnico_nome]['costo'] = ($tecnici[$tecnico_nome]['costo'] ?? 0) + $totali_sessione['costo'];
                $tecnici[$tecnico_nome]['ricavo'] = ($tecnici[$tecnico_nome]['ricavo'] ?? 0) + $totali_sessione['ricavo'];
                
                // Raggruppamento per stato intervento
                $stato_title = $intervento->stato->getTranslation('title');
                $stati_intervento[$stato_title]['colore'] = $intervento->stato->colore;
                $stati_intervento[$stato_title]['ore'] = ($stati_intervento[$stato_title]['ore'] ?? 0) + $sessione->ore;
                $stati_intervento[$stato_title]['costo'] = ($stati_intervento[$stato_title]['costo'] ?? 0) + $totali_sessione['costo'];
                $stati_intervento[$stato_title]['ricavo'] = ($stati_intervento[$stato_title]['ricavo'] ?? 0) + $totali_sessione['ricavo'];
            }
            echo '
                        </tbody>
                    </table>
                </div>';
        }
        // Lettura articoli utilizzati
        $articoli = $intervento->articoli;
        if (!$articoli->isEmpty()) {
            echo '
                <div class="mb-3">
                    <h6 class="font-weight-bold text-primary"><i class="fa fa-box"></i> '.tr('Materiale utilizzato').'</h6>
                    <table class="table table-sm table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>'.tr('Materiale').'</th>
                                <th width="120">'.tr('Q.tà').'</th>
                                <th width="150">'.tr('Prezzo di acquisto').'</th>
                                <th width="150">'.tr('Prezzo di vendita').'</th>
                            </tr>
                        </thead>
                        <tbody>';
            foreach ($articoli as $articolo) {
                $sconto = getHtmlSconto($articolo->sconto);
                echo '
                            <tr>
                                <td>
                                    '.Modules::link('Articoli', $articolo->idarticolo, $articolo->descrizione).'
                                </td>
                                <td class="text-right">'.numberFormat($articolo->qta, 'qta').'</td>
                                <td class="text-right text-danger">'.moneyFormat($articolo->spesa).'</td>
                                <td class="text-right text-success">'.moneyFormat($articolo->imponibile).$sconto.'</td>
                            </tr>';
                // Raggruppamento per articolo con lo stesso prezzo
                $qta = $articolo->qta > 0 ? $articolo->qta : 1;
                $ricavo = (string) (($articolo->imponibile - $articolo->sconto) / $qta);
                $costo = (string) ($articolo->spesa / $qta);
                $descrizione = $articolo->articolo->codice.' - '.$articolo->descrizione;
                
                if (!isset($materiali_art[$descrizione][$ricavo][$costo])) {
                    $materiali_art[$descrizione][$ricavo][$costo] = ['id' => $articolo->id, 'qta' => 0, 'costo' => 0, 'ricavo' => 0];
                }
                $materiali_art[$descrizione][$ricavo][$costo]['qta'] += $articolo->qta;
                $materiali_art[$descrizione][$ricavo][$costo]['costo'] += $articolo->spesa;
                $materiali_art[$descrizione][$ricavo][$costo]['ricavo'] += $articolo->imponibile - $articolo->sconto;
            }
            echo '
                        </tbody>
                    </table>
                </div>';
        }
        // Lettura spese aggiuntive
        $righe = $intervento->righe;
        if (!$righe->isEmpty()) {
            echo '
                <div class="mb-3">
                    <h6 class="font-weight-bold text-primary"><i class="fa fa-receipt"></i> '.tr('Altre spese').'</h6>
                    <table class="table table-sm table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>'.tr('Altre spese').'</th>
                                <th width="120">'.tr('Q.tà').'</th>
                                <th width="150">'.tr('Prezzo di acquisto').'</th>
                                <th width="150">'.tr('Prezzo di vendita').'</th>
                            </tr>
                        </thead>
                        <tbody>';
            foreach ($righe as $riga) {
                $sconto = getHtmlSconto($riga->sconto);
                echo '
                            <tr>
                                <td>
                                    '.$riga->descrizione.'
                                </td>
                                <td class="text-right">'.numberFormat($riga->qta, 'qta').'</td>
                                <td class="text-right text-danger">'.moneyFormat($riga->spesa).'</td>
                                <td class="text-right text-success">'.moneyFormat($riga->imponibile).$sconto.'</td>
                            </tr>';
                // Raggruppamento per riga
                $descrizione_riga = $riga->descrizione;
                $materiali_righe[$descrizione_riga]['qta'] = ($materiali_righe[$descrizione_riga]['qta'] ?? 0) + $riga->qta;
                $materiali_righe[$descrizione_riga]['costo'] = ($materiali_righe[$descrizione_riga]['costo'] ?? 0) + $riga->spesa;
                $materiali_righe[$descrizione_riga]['ricavo'] = ($materiali_righe[$descrizione_riga]['ricavo'] ?? 0) + $riga->imponibile - $riga->sconto;
            }
            echo '
                        </tbody>
                    </table>
                </div>';
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
                <tr class="font-weight-bold">
                    <td class="text-right">
                        '.tr('Totale').'
                    </td>';
    echo '
                    <td class="text-right">
                        '.numberFormat($totale_ore).'
                    </td>';
    echo '
                    <td class="text-right">
                        '.numberFormat($totale_km).'
                    </td>';
    echo '
                    <td class="text-right">
                        '.moneyFormat($totale_costo).'
                    </td>';
    echo '
                    <td class="text-right">
                        '.moneyFormat($totale).'
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>';
}

$budget = $documento->totale_imponibile;
$righe = $documento->getRighe();
$totale_ore_contratto = 0;
foreach ($righe as $riga) {
    if ($riga->um == 'ore') {
        $totale_ore_contratto += $riga->qta;
    }
}

$diff = sum($budget, -$totale) - $documento->provvigione;

if ($diff > 0) {
    $bilancio_class = 'bg-success';
    $bilancio_icon = 'fa-arrow-up';
} elseif ($diff < 0) {
    $bilancio_class = 'bg-danger';
    $bilancio_icon = 'fa-arrow-down';
} else {
    $bilancio_class = 'bg-secondary';
    $bilancio_icon = 'fa-minus';
}
echo '
<div class="card mb-4">
    <div class="card-body text-center '.$bilancio_class.' text-white">
        <h4 class="mb-0">
            <i class="fa '.$bilancio_icon.'"></i> '.tr('Rapporto budget/spesa').':<br>
            <strong>'.moneyFormat($diff).'</strong>
        </h4>
    </div>
</div>';
if (!empty($totale_ore_contratto)) {
    echo '
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <i class="fa fa-clock"></i> '.tr('Riepilogo ore').'
    </div>
    <div class="card-body">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td>'.tr('Ore a contratto').':</td>
                            <td class="text-right font-weight-bold">'.Translator::numberToLocale($totale_ore_contratto).'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Ore erogate totali').':</td>
                            <td class="text-right font-weight-bold">'.Translator::numberToLocale($totale_ore).'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Ore residue totali').':</td>
                            <td class="text-right font-weight-bold text-primary">'.Translator::numberToLocale(floatval($totale_ore_contratto) - floatval($totale_ore)).'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Ore erogate concluse').':</td>
                            <td class="text-right font-weight-bold">'.Translator::numberToLocale($totale_ore_completate).'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Ore residue concluse').':</td>
                            <td class="text-right font-weight-bold text-primary">'.Translator::numberToLocale(floatval($totale_ore_contratto) - floatval($totale_ore_completate)).'</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';
} else {
    echo'
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Per monitorare il consumo ore, inserisci almeno una riga con unità di misura "ore"').'.
</div>';
}
echo '
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fa fa-list"></i> '.tr('Tipologia').'
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>'.tr('Tipologia').'</th>
                            <th width="10%">'.tr('Ore').'</th>
                            <th width="16%">'.tr('Costo').'</th>
                            <th width="16%">'.tr('Ricavo').'</th>
                            <th width="10%">'.tr('Margine').'</th>
                            <th width="10%">'.tr('Ricarico').'</th>
                        </tr>
                    </thead>
                    <tbody>';
ksort($tipologie);
foreach ($tipologie as $key => $tipologia) {
    $margini = calcolaMargini($tipologia['costo'], $tipologia['ricavo']);
    $bg_class = $margini['margine'] > 0 ? 'bg-success text-white' : 'bg-danger text-white';
    echo '
                        <tr>
                            <td>'.$key.'</td>
                            <td class="text-right">'.Translator::numberToLocale($tipologia['ore']).'</td>
                            <td class="text-right">'.Translator::numberToLocale($tipologia['costo']).' €</td>
                            <td class="text-right">'.Translator::numberToLocale($tipologia['ricavo']).' €</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['margine_prc'].'%)</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['ricarico_prc'].'%)</td>
                        </tr>';
}
echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fa fa-users"></i> '.tr('Tecnici').'
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>'.tr('Tecnici').'</th>
                            <th width="7%">'.tr('km').'</th>
                            <th width="10%">'.tr('Ore').'</th>
                            <th width="16%">'.tr('Costo').'</th>
                            <th width="16%">'.tr('Ricavo').'</th>
                            <th width="10%">'.tr('Margine').'</th>
                            <th width="10%">'.tr('Ricarico').'</th>
                        </tr>
                    </thead>
                    <tbody>';
ksort($tecnici);
foreach ($tecnici as $key => $tecnico) {
    $margini = calcolaMargini($tecnico['costo'], $tecnico['ricavo']);
    $bg_class = $margini['margine'] > 0 ? 'bg-success text-white' : 'bg-danger text-white';
    echo '
                        <tr>
                            <td>'.$key.'</td>
                            <td class="text-right">'.(int) $tecnico['km'].'</td>
                            <td class="text-right">'.Translator::numberToLocale($tecnico['ore']).'</td>
                            <td class="text-right">'.Translator::numberToLocale($tecnico['costo']).' €</td>
                            <td class="text-right">'.Translator::numberToLocale($tecnico['ricavo']).' €</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['margine_prc'].'%)</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['ricarico_prc'].'%)</td>
                        </tr>';
}
echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fa fa-flag"></i> '.tr('Stato').'
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>'.tr('Stato').'</th>
                            <th width="10%">'.tr('Ore').'</th>
                            <th width="16%">'.tr('Costo').'</th>
                            <th width="16%">'.tr('Ricavo').'</th>
                            <th width="10%">'.tr('Margine').'</th>
                            <th width="10%">'.tr('Ricarico').'</th>
                        </tr>
                    </thead>
                    <tbody>';
ksort($stati_intervento);
foreach ($stati_intervento as $key => $stato) {
    $margini = calcolaMargini($stato['costo'], $stato['ricavo']);
    $bg_class = $margini['margine'] > 0 ? 'bg-success text-white' : 'bg-danger text-white';
    echo '
                        <tr>
                            <td><div class="img-circle" style="width:18px; height:18px; position:relative; bottom:-2px; background:'.$stato['colore'].'; float:left;"></div> '.$key.'</td>
                            <td class="text-right">'.Translator::numberToLocale($stato['ore']).'</td>
                            <td class="text-right">'.Translator::numberToLocale($stato['costo']).' €</td>
                            <td class="text-right">'.Translator::numberToLocale($stato['ricavo']).' €</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['margine_prc'].'%)</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['ricarico_prc'].'%)</td>
                        </tr>';
}
echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fa fa-box"></i> '.tr('Materiale').'
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>'.tr('Materiale').'</th>
                            <th width="8%">'.tr('Qtà').'</th>
                            <th width="16%">'.tr('Costo').'</th>
                            <th width="16%">'.tr('Ricavo').'</th>
                            <th width="10%">'.tr('Margine').'</th>
                            <th width="10%">'.tr('Ricarico').'</th>
                        </tr>
                    </thead>
                    <tbody>';
ksort($materiali_art);
foreach ($materiali_art as $key => $materiali_array1) {
    foreach ($materiali_array1 as $materiali_array2) {
        foreach ($materiali_array2 as $materiale) {
            $margini = calcolaMargini($materiale['costo'], $materiale['ricavo']);
            $bg_class = $margini['margine'] > 0 ? 'bg-success text-white' : 'bg-danger text-white';
            echo '
                        <tr>
                            <td>'.Modules::link('Articoli', $materiale['id'], $key).'</td>
                            <td class="text-center">'.$materiale['qta'].'</td>
                            <td class="text-right">'.Translator::numberToLocale($materiale['costo']).' €</td>
                            <td class="text-right">'.Translator::numberToLocale($materiale['ricavo']).' €</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['margine_prc'].'%)</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['ricarico_prc'].'%)</td>
                        </tr>';
        }
    }
}
ksort($materiali_righe);
foreach ($materiali_righe as $key => $materiale) {
    $margini = calcolaMargini($materiale['costo'], $materiale['ricavo']);
    $bg_class = $margini['margine'] > 0 ? 'bg-success text-white' : 'bg-danger text-white';
    echo '
                        <tr>
                            <td>'.$key.'</td>
                            <td class="text-center">'.$materiale['qta'].'</td>
                            <td class="text-right">'.Translator::numberToLocale($materiale['costo']).' €</td>
                            <td class="text-right">'.Translator::numberToLocale($materiale['ricavo']).' €</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['margine_prc'].'%)</td>
                            <td class="text-right '.$bg_class.'">'.Translator::numberToLocale($margini['margine']).' € ('.$margini['ricarico_prc'].'%)</td>
                        </tr>';
}
echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

// Tabella totale delle ore,km,costi e totale scontato suddivisi per i mesi in cui sono stati effettuati gli interventi
echo '
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <i class="fa fa-calendar"></i> '.tr('Riepilogo mensile').'
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-striped mb-0">
            <thead>
                <tr>
                    <th>'.tr('Mese').'</th>
                    <th width="10%">'.tr('Ore').'</th>
                    <th width="10%">'.tr('Km').'</th>
                    <th width="16%">'.tr('Costo').'</th>
                    <th width="16%">'.tr('Totale scontato').'</th>
                </tr>
            </thead>
            <tbody>';
$interventi_per_mese = [];
$totals = ['ore' => 0, 'km' => 0, 'costo' => 0, 'totale' => 0];

foreach ($interventi as $intervento) {
    // Riutilizza le sessioni già caricate in cache
    $sessioni = getSessioniCache($intervento, $sessioni_cache);

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
        
        // Usa la funzione helper per calcolare i totali
        $totali_sessione = calcolaTotaliSessione($sessione);
        
        $interventi_per_mese[$mese]['ore'] += $sessione->ore;
        $interventi_per_mese[$mese]['km'] += $sessione->km;
        $interventi_per_mese[$mese]['costo'] += $totali_sessione['costo'];
        $interventi_per_mese[$mese]['totale'] += $totali_sessione['ricavo'];

        $totals['ore'] += $sessione->ore;
        $totals['km'] += $sessione->km;
        $totals['costo'] += $totali_sessione['costo'];
        $totals['totale'] += $totali_sessione['ricavo'];
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
                </tr>
            </tbody>
        </table>
    </div>
</div>';

/*
    Stampa consuntivo
*/
echo '
<div class="text-center mb-4">
    '.Prints::getLink('Consuntivo '.$text, $id_record, 'btn-primary btn-lg', tr('Stampa consuntivo')).'
</div>';

// Aggiunta interventi se il documento é aperto o in attesa o pagato (non si possono inserire interventi collegati ad altri preventivi)
$query = getQueryInterventiDisponibili($record['idanagrafica']);

$count = $dbo->fetchNum($query);

echo '<hr>
<div class="card">
    <div class="card-header bg-info text-white">
        <i class="fa fa-plus"></i> '.tr('Aggiungi intervento').'
    </div>
    <div class="card-body">
        <form action="" method="post" id="aggiungi-intervento">
            <input type="hidden" name="op" value="addintervento">
            <input type="hidden" name="backto" value="record-edit">

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "select", "label": "'.tr('Aggiungi un intervento a questo documento').' ('.$count.')", "name": "idintervento", "values": "query='.$query.'", "required":"1" ]}
                </div>

                <!-- PULSANTI -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" onclick="if($(\'#aggiungi-intervento\').parsley().validate() && confirm(\''.tr('Aggiungere questo intervento al documento?').'\') ){ $(\'#aggiungi-intervento\').submit(); }" '.(($record['is_pianificabile'] && !$block_edit) ? '' : 'disabled').'>
                            <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>';
