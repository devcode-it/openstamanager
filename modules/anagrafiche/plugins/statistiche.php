<?php

include_once __DIR__.'/../../../core.php';

// Preventivi
$rsi = $dbo->fetchArray('SELECT co_preventivi.id, data_accettazione AS data, ragione_sociale FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica WHERE co_preventivi.idanagrafica='.prepare($id_record).' AND default_revision = 1 AND data_accettazione BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));
$totale_preventivi = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_preventivi = sum($totale_preventivi, Modules\Preventivi\Preventivo::find($rsi[$i]['id'])->imponibile_scontato);
}

echo '
    <div class="row">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'aqua').'"><i class="fa fa-question"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text pull-left">'.tr('Preventivi').'</span>
                    '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Preventivi')['id'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                    <br class="clearfix">
                    <span class="info-box-number">
                        <big>'.count($rsi).'</big><br>
                        <small class="help-block">'.moneyFormat($totale_preventivi).'</small>
                    </span>
                </div>
            </div>
        </div>';

// Contratti
$rsi = $dbo->fetchArray('SELECT co_contratti.id, data_accettazione AS data, ragione_sociale FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_contratti.idanagrafica='.prepare($id_record).' AND data_accettazione BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$totale_contratti = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_contratti = sum($totale_contratti, Modules\Contratti\Contratto::find($rsi[$i]['id'])->imponibile_scontato);
}

echo '
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'purple').'"><i class="fa fa-refresh"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text pull-left">'.tr('Contratti').'</span>
                    '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Contratti')['id'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                    <br class="clearfix">
                    <span class="info-box-number">
                        <big>'.count($rsi).'</big><br>
                        <small class="help-block">'.moneyFormat($totale_contratti).'</small>
                    </span>
                </div>
            </div>
        </div>';

// Ordini cliente
$rsi = $dbo->fetchArray('SELECT or_ordini.id, data, ragione_sociale FROM or_ordini INNER JOIN an_anagrafiche ON or_ordini.idanagrafica=an_anagrafiche.idanagrafica WHERE or_ordini.idanagrafica='.prepare($id_record).' AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$totale_ordini_cliente = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_ordini_cliente = sum($totale_ordini_cliente, Modules\Ordini\Ordine::find($rsi[$i]['id'])->imponibile_scontato);
}

echo '
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'blue').'"><i class="fa fa-file-text"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text pull-left">'.tr('Ordini cliente').'</span>
                    '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Ordini cliente')['id'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                    <br class="clearfix">
                    <span class="info-box-number">
                        <big>'.count($rsi).'</big><br>
                        <small class="help-block">'.moneyFormat($totale_ordini_cliente).'</small>
                    </span>
                </div>
            </div>
        </div>
    </div>';

// Interventi
$rsi = [];
if (in_array('Cliente', explode(',', $record['tipianagrafica']))) {
    //Clienti
    $rsi = $dbo->fetchArray('SELECT ragione_sociale, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data, (SELECT SUM(prezzo_ore_consuntivo+prezzo_km_consuntivo+prezzo_dirittochiamata) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS totale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.idanagrafica='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));
} elseif (in_array('Tecnico', explode(',', $record['tipianagrafica']))) {
    //Tecnici
    $rsi = $dbo->fetchArray('SELECT ragione_sociale, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data, (SELECT SUM(prezzo_ore_consuntivo+prezzo_km_consuntivo+prezzo_dirittochiamata) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id AND in_interventi_tecnici.idtecnico = '.prepare($id_record).' ) AS totale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN in_interventi_tecnici ON in_interventi.id = in_interventi_tecnici.idintervento  WHERE in_interventi_tecnici.idtecnico='.prepare($id_record).' AND data_richiesta BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));
}
$totale_interventi = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_interventi += $rsi[$i]['totale'];
}

echo '
    <div class="row">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'red').'"><i class="fa fa-cog"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text pull-left">'.tr('Attività').'</span>
                    '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Interventi')['id'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                    <br class="clearfix">
                    <span class="info-box-number">
                        <big>'.count($rsi).'</big><br>
                        <small class="help-block">'.moneyFormat($totale_interventi).'</small>
                    </span>
                </div>
            </div>
        </div>';

// Ddt in uscita
$rsi = $dbo->fetchArray("SELECT id, data, ragione_sociale FROM dt_ddt INNER JOIN an_anagrafiche ON dt_ddt.idanagrafica=an_anagrafiche.idanagrafica WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir='entrata') AND dt_ddt.idanagrafica=".prepare($id_record).' AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$totale_ddt_uscita = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_ddt_uscita = sum($totale_ddt_uscita, Modules\DDT\DDT::find($rsi[$i]['id'])->imponibile_scontato);
}

echo '
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'maroon').'"><i class="fa fa-truck"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text pull-left">'.tr('Ddt in uscita').'</span>
                    '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Ddt di vendita')['id'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                    <br class="clearfix">
                    <span class="info-box-number">
                        <big>'.count($rsi).'</big><br>
                        <small class="help-block">'.moneyFormat($totale_ddt_uscita).'</small>
                    </span>
                </div>
            </div>
        </div>';

// Fatture di vendita
$rsi = $dbo->fetchArray("SELECT id, data, ragione_sociale FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='entrata') AND co_documenti.idanagrafica=".prepare($id_record).' AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$totale_fatture_vendita = 0;

for ($i = 0; $i < count($rsi); ++$i) {
    $totale_fatture_vendita = sum($totale_fatture_vendita, Modules\Fatture\Fattura::find($rsi[$i]['id'])->imponibile_scontato);
}

echo '
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-'.(count($rsi) == 0 ? 'gray' : 'green').'"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text pull-left">'.tr('Fatture').'</span>
                    '.(count($rsi) > 0 ? '<span class="info-box-text pull-right"><a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Fatture di vendita')['id'].'">'.tr('Visualizza').' <i class="fa fa-chevron-circle-right"></i></a></span>' : '').'
                    <br class="clearfix">
                    <span class="info-box-number">
                        <big>'.count($rsi).'</big><br>
                        <small class="help-block">'.moneyFormat($totale_fatture_vendita).'</small>
                    </span>
                </div>
            </div>
        </div>
    </div>';
