<?php

include_once __DIR__.'/../../core.php';

// Lettura sconto incondizionato
$rss = $dbo->fetchArray('SELECT idtipointervento, sconto_globale, tipo_sconto_globale FROM in_interventi WHERE id='.prepare($idintervento));
$sconto = $rss[0]['sconto_globale'];
$tipo_sconto = $rss[0]['tipo_sconto_globale'];

if (Auth::admin() || $_SESSION['gruppo'] != 'Tecnici') {
    $rsr = $dbo->fetchArray('SELECT * FROM vw_activity_subtotal WHERE id='.prepare($id_record));

    $manodopera_costo = $rsr[0]['manodopera_costo'];
    $manodopera_addebito = $rsr[0]['manodopera_addebito'];
    $manodopera_scontato = $rsr[0]['manodopera_scontato'];

    $viaggio_costo = $rsr[0]['viaggio_costo'];
    $viaggio_addebito = $rsr[0]['viaggio_addebito'];
    $viaggio_scontato = $rsr[0]['viaggio_scontato'];

    $ricambi_costo = $rsr[0]['ricambi_costo'];
    $ricambi_addebito = $rsr[0]['ricambi_addebito'];
    $ricambi_scontato = $rsr[0]['ricambi_scontato'];

    $altro_costo = $rsr[0]['altro_costo'];
    $altro_addebito = $rsr[0]['altro_addebito'];
    $altro_scontato = $rsr[0]['altro_scontato'];

    $totale_costi = sum([$manodopera_costo, $viaggio_costo, $ricambi_costo, $altro_costo]);
    $totale_addebito = sum([$manodopera_addebito, $viaggio_addebito, $ricambi_addebito, $altro_addebito]);
    $totale_manodopera = sum([$manodopera_scontato, $viaggio_scontato, $ricambi_scontato, $altro_scontato]);

    $sconto_globale = $rsr[0]['sconto_globale'];

    $totale_manodopera = sum($totale_manodopera, -$sconto_globale);

    echo '
<!-- Riepilogo dei costi -->
<table class="table table condensed table-striped table-hover table-bordered">
    <tr>
        <th width="40%"></th>
        <th width="20%" class="text-center">'.strtoupper(_('Costo')).'</th>
        <th width="20%" class="text-center">'.strtoupper(_('Addebito')).'</th>
        <th width="20%" class="text-center">'.strtoupper(_('Tot. Scontato')).'</th>
    </tr>

    <tr>
        <th>'.strtoupper(_('Totale manodopera')).'</th>
        <td class="text-right">'.Translator::numberToLocale($manodopera_costo).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($manodopera_addebito).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($manodopera_scontato).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(_('Totale viaggio')).'</th>
        <td class="text-right">'.Translator::numberToLocale($viaggio_costo).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($viaggio_addebito).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($viaggio_scontato).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(_('Totale articoli')).'</th>
        <td class="text-right">'.Translator::numberToLocale($ricambi_costo).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($ricambi_addebito).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($ricambi_scontato).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(_('Totale altre spese')).'</th>
        <td class="text-right">'.Translator::numberToLocale($altro_costo).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($altro_addebito).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($altro_scontato).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(_('Sconto totale')).'</th>
        <td class="text-right">-</td>
        <td class="text-right">-</td>
        <td class="text-right">'.Translator::numberToLocale(-$sconto_globale).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(_('Totale')).'</th>
        <th class="text-right">'.Translator::numberToLocale($manodopera_costo + $viaggio_costo + $ricambi_costo + $altro_costo).' &euro;</th>
        <th class="text-right">'.Translator::numberToLocale($manodopera_addebito + $viaggio_addebito + $ricambi_addebito + $altro_addebito).' &euro;</th>
        <th class="text-right">'.Translator::numberToLocale($manodopera_scontato + $viaggio_scontato + $ricambi_scontato + $altro_scontato - $sconto_globale).' &euro;</th>
    </tr>
</table>';
}

echo '

<!-- AGGIUNTA TECNICO -->
<div class="row">
    <div class="col-md-4 pull-right">
        {[ "type": "number", "label": "'._('Sconto globale').'", "name": "sconto_globale", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
    </div>
</div>';
