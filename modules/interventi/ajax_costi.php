<?php

if (file_exists(__DIR__.'/../../../core.php')) {
    include_once __DIR__.'/../../../core.php';
} else {
    include_once __DIR__.'/../../core.php';
}

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

$idiva = setting('Iva predefinita');
$rs_iva = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
($rs_iva[0]['percentuale'] > 0) ? $hide = '' : $hide = 'hide';

if ($show_prezzi) {
    $costi = get_costi_intervento($id_record);

    $rss = $dbo->fetchArray('SELECT in_statiintervento.completato AS flag_completato FROM in_statiintervento INNER JOIN in_interventi ON in_statiintervento.idstatointervento=in_interventi.idstatointervento WHERE in_interventi.id='.prepare($id_record));

    if ($rss[0]['flag_completato']) {
        $readonly = 'readonly';
    } else {
        $readonly = '';
    }

    echo '
<!-- Riepilogo dei costi -->
<table class="table table condensed table-striped table-hover table-bordered">
    <tr>
        <th width="40%"></th>
        <th width="20%" class="text-center">'.tr('Costo', [], ['upper' => true]).'</th>
        <th width="20%" class="text-center">'.tr('Addebito', [], ['upper' => true]).'</th>
        <th width="20%" class="text-center">'.tr('Tot. Scontato', [], ['upper' => true]).'</th>
    </tr>

    <tr>
        <th>'.tr('Totale manodopera', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['manodopera_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['manodopera_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['manodopera_scontato'], 2).'</td>
    </tr>

    <tr>
        <th>'.tr('Totale diritto di chiamata', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['dirittochiamata_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['dirittochiamata_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['dirittochiamata_scontato'], 2).'</td>
    </tr>

    <tr>
        <th>'.tr('Totale viaggio', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['viaggio_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['viaggio_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['viaggio_scontato'], 2).'</td>
    </tr>

    <tr>
        <th>'.tr('Totale articoli', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['ricambi_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['ricambi_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['ricambi_scontato'], 2).'</td>
    </tr>

    <tr>
        <th>'.tr('Totale altre spese', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['altro_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['altro_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['altro_scontato'], 2).'</td>
    </tr>

    <tr>
        <th>'.tr('Sconto incondizionato', [], ['upper' => true]).'</th>
        <td class="text-right">-</td>
        <td class="text-right">-</td>
        <td class="text-right">'.moneyFormat(-$costi['sconto_globale'], 2).'</td>
    </tr>


	<tr class='.$hide.' >
        <th>'.tr('Imponibile', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['totale_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['totale_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['totale_scontato'], 2).'</td>
    </tr>


	<tr class='.$hide.' >
        <th>'.tr('IVA', [], ['upper' => true]).'</th>
        <td class="text-right">'.moneyFormat($costi['iva_costo'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['iva_addebito'], 2).'</td>
        <td class="text-right">'.moneyFormat($costi['iva_totale'], 2).'</td>
    </tr>

    <tr>
        <th>'.tr('Totale', [], ['upper' => true]).'</th>
        <th class="text-right">'.moneyFormat($costi['totaleivato_costo'], 2).'</th>
        <th class="text-right">'.moneyFormat($costi['totaleivato_addebito'], 2).'</th>
        <th class="text-right">'.moneyFormat($costi['totale'], 2).'</th>
    </tr>
</table>';
}

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';
