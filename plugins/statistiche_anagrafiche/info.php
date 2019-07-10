<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;

$calendar_id = filter('calendar_id');
$start = filter('start');
$end = filter('end');

$anagrafica = Anagrafica::find($id_record);

// Preventivi
$preventivi = Preventivo::whereBetween('data_accettazione', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->where('default_revision', 1)
    ->get();
$totale_preventivi = $preventivi->sum('imponibile_scontato');

// Contratti
$contratti = Contratto::whereBetween('data_accettazione', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->get();
$totale_contratti = $contratti->sum('imponibile_scontato');

// Ordini cliente
$ordini_cliente = Ordine::whereBetween('data', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->get();
$totale_ordini_cliente = $ordini_cliente->sum('imponibile_scontato');

// Interventi

// Clienti
if ($anagrafica->isTipo('Cliente')) {
    $rsi = $dbo->fetchArray('SELECT ragione_sociale, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data, (SELECT SUM(prezzo_ore_consuntivo+prezzo_km_consuntivo+prezzo_dirittochiamata) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS totale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.idanagrafica='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end));
}

// Tecnici
elseif ($anagrafica->isTipo('Tecnico')) {
    $rsi = $dbo->fetchArray('SELECT ragione_sociale, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data, (SELECT SUM(prezzo_ore_consuntivo+prezzo_km_consuntivo+prezzo_dirittochiamata) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id AND in_interventi_tecnici.idtecnico = '.prepare($id_record).' ) AS totale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN in_interventi_tecnici ON in_interventi.id = in_interventi_tecnici.idintervento  WHERE in_interventi_tecnici.idtecnico='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end));
}
$totale_interventi = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_interventi += $rsi[$i]['totale'];
}

// Ddt in uscita
$ddt_uscita = DDT::whereBetween('data', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->whereHas('tipo', function ($query) {
        $query->where('dt_tipiddt.dir', '=', 'entrata');
    })
    ->get();
$totale_ddt_uscita = $ddt_uscita->sum('imponibile_scontato');

// Fatture di vendita
$fatture_vendita = Fattura::whereBetween('data', [$start, $end])
    ->where('idanagrafica', $id_record)
    ->whereHas('tipo', function ($query) {
        $query->where('co_tipidocumento.dir', '=', 'entrata');
    })
    ->get();
$totale_fatture_vendita = $fatture_vendita->sum('imponibile_scontato');

echo '
<div class="box box-info" id="row-'.$calendar_id.'">
    <div class="box-header">
        <h3 class="box-title">'.tr('Periodo dal _START_ al _END_', [
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
                        '.($preventivi->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Preventivi')['id'].'&search_Cliente='.$rsi[0]['ragione_sociale'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($preventivi).'</big><br>
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
                        '.($contratti->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Contratti')['id'].'&search_Cliente='.$rsi[0]['ragione_sociale'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($contratti).'</big><br>
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
                        '.($ordini_cliente->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Ordini cliente')['id'].'&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($ordini_cliente).'</big><br>
                            <small class="help-block">'.moneyFormat($totale_ordini_cliente).'</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'red').'"><i class="fa fa-cog"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text pull-left">'.tr('Attività').'</span>
                        '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Interventi')['id'].'&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($rsi).'</big><br>
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
                        '.($ddt_uscita->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Ddt di vendita')['id'].'&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($ddt_uscita).'</big><br>
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
                        '.($fatture_vendita->count() > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Fatture di vendita')['id'].'&search_Ragione-sociale='.$rsi[0]['ragione_sociale'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                        <br class="clearfix">
                        <span class="info-box-number">
                            <big>'.count($fatture_vendita).'</big><br>
                            <small class="help-block">'.moneyFormat($totale_fatture_vendita).'</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>';
