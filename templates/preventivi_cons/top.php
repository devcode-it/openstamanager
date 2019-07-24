<?php

include_once __DIR__.'/../../core.php';

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center">
            <h4 class="text-bold">'.tr('Consuntivo', [], ['upper' => true]).'</h4>
            <b>'.tr('Preventivo num. _NUM_ del _DATE_', [
        '_NUM_' => $documento['numero'],
        '_DATE_' => Translator::dateToLocale($documento['data_bozza']),
    ], ['upper' => true]).'</b>
        </div>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$ $c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>
        </table>
    </div>
</div>';

// Descrizione
if (!empty($documento['descrizione'])) {
    echo '
<p>'.nl2br($documento['descrizione']).'</p>
<br>';
}

echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="2">'.tr('Documento', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Imponibile', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Sconto', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Totale imponibile', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';
