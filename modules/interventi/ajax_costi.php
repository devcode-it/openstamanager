<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/modutil.php';

if (Auth::admin() || $_SESSION['gruppo'] != 'Tecnici') {
    $costi = get_costi_intervento($id_record);

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
        <td class="text-right">'.Translator::numberToLocale($costi['manodopera_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['manodopera_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['manodopera_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.tr('Totale viaggio', [], ['upper' => true]).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['viaggio_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['viaggio_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['viaggio_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.tr('Totale articoli', [], ['upper' => true]).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['ricambi_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['ricambi_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['ricambi_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.tr('Totale altre spese', [], ['upper' => true]).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['altro_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['altro_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['altro_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.tr('Sconto totale', [], ['upper' => true]).'</th>
        <td class="text-right">-</td>
        <td class="text-right">-</td>
        <td class="text-right">'.Translator::numberToLocale(-$costi['sconto_globale']).' &euro;</td>
    </tr>

    <tr>
        <th>'.tr('Totale', [], ['upper' => true]).'</th>
        <th class="text-right">'.Translator::numberToLocale($costi['totale_costo']).' &euro;</th>
        <th class="text-right">'.Translator::numberToLocale($costi['totale_addebito']).' &euro;</th>
        <th class="text-right">'.Translator::numberToLocale($costi['totale']).' &euro;</th>
    </tr>
</table>';
}

// Lettura dello sconto globale
$rss = $dbo->fetchArray('SELECT sconto_globale, tipo_sconto_globale FROM in_interventi WHERE id='.prepare($id_record));
$sconto = $rss[0]['sconto_globale'];
$tipo_sconto = $rss[0]['tipo_sconto_globale'];

echo '

<!-- SCONTO -->
<div class="row">
    <div class="col-md-4 pull-right">
        {[ "type": "number", "label": "'.tr('Sconto globale').'", "name": "sconto_globale", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
    </div>
</div>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';
