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

include_once __DIR__.'/../../core.php';

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;
use Modules\Impianti\Impianto;

$calendar_id = filter('calendar_id');
$start = filter('start');
$end = filter('end');

$anagrafica = Anagrafica::withTrashed()->find($id_record);
if (empty($anagrafica)) {
    return;
}

// Preventivi
$preventivi = Preventivo::whereBetween('data_bozza', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->where('default_revision', 1)
    ->get();
$totale_preventivi = $preventivi->sum('totale_imponibile');

// Contratti
$contratti = Contratto::whereBetween('data_bozza', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->get();
$totale_contratti = $contratti->sum('totale_imponibile');

// Ordini cliente
$ordini_cliente = Ordine::whereBetween('data', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->get();
$totale_ordini_cliente = $ordini_cliente->sum('totale_imponibile');

// Interventi e Ore lavorate
$interventi = [];
// Clienti
if ($anagrafica->isTipo('Cliente')) {
    $interventi = $dbo->fetchArray('SELECT in_interventi.id FROM in_interventi WHERE in_interventi.id_anagrafica='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end));
    $sessioni = $dbo->fetchArray('SELECT in_interventi_tecnici.id FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi.id = in_interventi_tecnici.id_intervento WHERE in_interventi.id_anagrafica='.prepare($id_record).' AND in_interventi_tecnici.orario_inizio BETWEEN '.prepare($start).' AND '.prepare($end));
}

// Tecnici
elseif ($anagrafica->isTipo('Tecnico')) {
    $interventi = $dbo->fetchArray('SELECT in_interventi.id FROM in_interventi INNER JOIN in_interventi_tecnici ON in_interventi.id = in_interventi_tecnici.id_intervento WHERE in_interventi_tecnici.id_tecnico='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end));

    $sessioni = $dbo->fetchArray('SELECT in_interventi_tecnici.id FROM in_interventi_tecnici WHERE in_interventi_tecnici.id_tecnico='.prepare($id_record).' AND in_interventi_tecnici.orario_inizio BETWEEN '.prepare($start).' AND '.prepare($end));
}

$interventi = Intervento::whereIn('id', array_column($interventi, 'id'))->get();
$totale_interventi = $interventi->sum('totale_imponibile');

if ($sessioni) {
    $sessioni = Sessione::whereIn('id', array_column($sessioni, 'id'))->get();
    $totale_ore_lavorate = $sessioni->sum('ore');
}

// Ddt in uscita
$ddt_uscita = DDT::whereBetween('data', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->whereHas('tipo', function ($query) {
        $query->where('dt_tipi_ddt.dir', '=', 'entrata');
    })
    ->get();
$totale_ddt_uscita = $ddt_uscita->sum('totale_imponibile');

// Fatture di vendita
$segmenti = $dbo->select('zz_segments', 'id', [], ['autofatture' => 0]);
$fatture_vendita = Fattura::whereBetween('data', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->whereHas('tipo', fn ($query) => $query->where('co_tipi_documento.dir', '=', 'entrata')
        ->where('co_tipi_documento.reversed', '=', 0))
    ->whereIn('id_segment', array_column($segmenti, 'id'))
    ->get();
$note_credito = Fattura::whereBetween('data', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->whereHas('tipo', fn ($query) => $query->where('co_tipi_documento.dir', '=', 'entrata')
        ->where('co_tipi_documento.reversed', '=', 1))
    ->get();
$totale_fatture_vendita = $fatture_vendita->sum('totale_imponibile') - $note_credito->sum('totale_imponibile');

// Impianti
$impianti = Impianto::whereBetween('data', [$start, $end])
    ->where('id_anagrafica', $id_record)
    ->get();

// Articoli venduti (solo per fornitori)
$articoli_venduti = [];
if ($anagrafica->isTipo('Fornitore')) {
    $articoli_venduti = $dbo->fetchArray('
        SELECT DISTINCT rd.id_articolo
        FROM co_righe_documenti rd
        INNER JOIN co_documenti d ON d.id = rd.id_documento
        INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
        WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = \'uscita\' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).'
        UNION
        SELECT DISTINCT rdd.id_articolo
        FROM dt_righe_ddt rdd
        INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
        INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
        WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = \'uscita\' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end));
}

// Articoli acquistati (solo per clienti)
$articoli_acquistati = [];
if ($anagrafica->isTipo('Cliente')) {
    $articoli_acquistati = $dbo->fetchArray('
        SELECT DISTINCT rd.id_articolo
        FROM co_righe_documenti rd
        INNER JOIN co_documenti d ON d.id = rd.id_documento
        INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
        WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = \'entrata\' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).'
        UNION ALL
        SELECT DISTINCT rdd.id_articolo
        FROM dt_righe_ddt rdd
        INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
        INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
        WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = \'entrata\' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end));
}


echo '
<div class="card card-info" id="row-'.$calendar_id.'" data-start="'.$start.'" data-end="'.$end.'">
    <div class="card-header">
        <h3 class="card-title">'.tr('Dal _START_ al _END_', [
    '_START_' => dateFormat($start),
    '_END_' => dateFormat($end),
]).' - '.tr('Periodo _NUM_', [
    '_NUM_' => $calendar_id,
]).'</h3>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'preventivi\')">
                    <span class="info-box-icon bg-'.($preventivi->count() == 0 ? 'gray' : 'info').'"><i class="fa fa-question"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Preventivi').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$preventivi->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_preventivi).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'contratti\')">
                    <span class="info-box-icon bg-'.($contratti->count() == 0 ? 'gray' : 'purple').'"><i class="fa fa-refresh"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Contratti').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$contratti->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_contratti).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'ordini_cliente\')">
                    <span class="info-box-icon bg-'.($ordini_cliente->count() == 0 ? 'gray' : 'blue').'"><i class="fa fa-file-text"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Ordini cliente').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$ordini_cliente->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_ordini_cliente).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'interventi\')">
                    <span class="info-box-icon bg-'.($interventi->count() == 0 ? 'gray' : 'red').'"><i class="fa fa-cog"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Attività');
echo '
                            <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$interventi->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_interventi).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'ddt\')">
                    <span class="info-box-icon bg-'.($ddt_uscita->count() == 0 ? 'gray' : 'maroon').'"><i class="fa fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Ddt in uscita').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$ddt_uscita->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_ddt_uscita).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'fatture\')">
                    <span class="info-box-icon bg-'.($fatture_vendita->count() + $note_credito->count() == 0 ? 'gray' : 'green').'"><i class="fa fa-money"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Fatture').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.($fatture_vendita->count() + $note_credito->count()).'</big><br>
                            <small class="help-block">'.moneyFormat($totale_fatture_vendita).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'impianti\')">
                    <span class="info-box-icon bg-'.($impianti->count() == 0 ? 'gray' : 'orange').'"><i class="fa fa-puzzle-piece"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Impianti').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$impianti->count().'</big>
                            <br><br>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'sessioni\')">
                    <span class="info-box-icon bg-'.(!empty($sessioni) ? 'warning' : 'gray').'"><i class="fa fa-wrench"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Ore lavorate').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.numberFormat($totale_ore_lavorate, 0).'</big>
                            <br><br>
                        </span>
                    </div>
                </div>
            </div>
';
if ($anagrafica->isTipo('Fornitore')) {
    echo '
            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'articoli_venduti\')">
                    <span class="info-box-icon bg-'.($articoli_venduti ? 'teal' : 'gray').'"><i class="fa fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Articoli venduti').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($articoli_venduti).'</big>
                            <br><br>
                        </span>
                    </div>
                </div>
            </div>';
}
if ($anagrafica->isTipo('Cliente')) {
    echo '
            <div class="col-md-3">
                <div class="info-box" style="cursor: pointer;" onclick="apriPopup(this, \'articoli_acquistati\')">
                    <span class="info-box-icon bg-'.($articoli_acquistati ? 'teal' : 'gray').'"><i class="fa fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Articoli acquistati').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($articoli_acquistati).'</big>
                            <br><br>
                        </span>
                    </div>
                </div>
            </div>';
}
?>
        </div>
    </div>
</div>
