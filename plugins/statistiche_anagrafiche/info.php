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

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;

$calendar_id = filter('calendar_id');
$start = filter('start');
$end = filter('end');

$anagrafica = Anagrafica::withTrashed()->find($id_record);
if (empty($anagrafica)) {
    return;
}

// Preventivi
$preventivi = Preventivo::whereBetween('data_bozza', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->where('default_revision', 1)
    ->get();
$totale_preventivi = $preventivi->sum('totale_imponibile');

// Contratti
$contratti = Contratto::whereBetween('data_bozza', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->get();
$totale_contratti = $contratti->sum('totale_imponibile');

// Ordini cliente
$ordini_cliente = Ordine::whereBetween('data', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->get();
$totale_ordini_cliente = $ordini_cliente->sum('totale_imponibile');

// Interventi
$interventi = [];
// Clienti
if ($anagrafica->isTipo('Cliente')) {
    $interventi = $dbo->fetchArray('SELECT in_interventi.id FROM in_interventi WHERE in_interventi.idanagrafica='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end));
}

// Tecnici
elseif ($anagrafica->isTipo('Tecnico')) {
    $interventi = $dbo->fetchArray('SELECT in_interventi.id FROM in_interventi INNER JOIN in_interventi_tecnici ON in_interventi.id = in_interventi_tecnici.idintervento WHERE in_interventi_tecnici.idtecnico='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end));
}

$interventi = Intervento::whereIn('id', array_column($interventi, 'id'))->get();
$totale_interventi = $interventi->sum('totale_imponibile');

// Ddt in uscita
$ddt_uscita = DDT::whereBetween('data', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->whereHas('tipo', function ($query) {
        $query->where('dt_tipiddt.dir', '=', 'entrata');
    })
    ->get();
$totale_ddt_uscita = $ddt_uscita->sum('totale_imponibile');

// Fatture di vendita
$fatture_vendita = Fattura::whereBetween('data', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->whereHas('tipo', function ($query) {
        $query->where('co_tipidocumento.dir', '=', 'entrata');
    })
    ->get();
$totale_fatture_vendita = $fatture_vendita->sum('totale_imponibile');

echo '
<div class="box box-info" id="row-'.$calendar_id.'">
    <div class="box-header">
        <h3 class="box-title">'.tr('Dal _START_ al _END_', [
            '_START_' => dateFormat($start),
            '_END_' => dateFormat($end),
        ]).' - '.tr('Periodo _NUM_', [
            '_NUM_' => $calendar_id,
    ]).'</h3>
    </div>

    <div class="box-body">

        <div class="row">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.($preventivi->count() == 0 ? 'gray' : 'aqua').'"><i class="fa fa-question"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Preventivi').'</span>
                        '.($preventivi->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Preventivi')['id'].'&search_Cliente='.rawurlencode($anagrafica['ragione_sociale']).'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$preventivi->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_preventivi).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.($contratti->count() == 0 ? 'gray' : 'purple').'"><i class="fa fa-refresh"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Contratti').'</span>
                        '.($contratti->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Contratti')['id'].'&search_Cliente='.rawurlencode($anagrafica['ragione_sociale']).'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$contratti->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_contratti).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.($ordini_cliente->count() == 0 ? 'gray' : 'blue').'"><i class="fa fa-file-text"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Ordini cliente').'</span>
                        '.($ordini_cliente->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Ordini cliente')['id'].'&search_Ragione-sociale='.rawurlencode($anagrafica['ragione_sociale']).'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$ordini_cliente->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_ordini_cliente).'</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.($interventi->count() == 0 ? 'gray' : 'red').'"><i class="fa fa-cog"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Attività').'</span>
                        '.($interventi->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Interventi')['id'].'&search_Ragione-sociale='.rawurlencode($anagrafica['ragione_sociale']).'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$interventi->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_interventi).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.($ddt_uscita->count() == 0 ? 'gray' : 'maroon').'"><i class="fa fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Ddt in uscita').'</span>
                        '.($ddt_uscita->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Ddt di vendita')['id'].'&search_Ragione-sociale='.rawurlencode($anagrafica['ragione_sociale']).'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$ddt_uscita->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_ddt_uscita).'</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.($fatture_vendita->count() == 0 ? 'gray' : 'green').'"><i class="fa fa-money"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Fatture').'</span>
                        '.($fatture_vendita->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Fatture di vendita')['id'].'&search_Ragione-sociale='.rawurlencode($anagrafica['ragione_sociale']).'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.$fatture_vendita->count().'</big><br>
                            <small class="help-block">'.moneyFormat($totale_fatture_vendita).'</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>';
