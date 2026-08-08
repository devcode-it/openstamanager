<?php

include_once __DIR__.'/../../core.php';

$period = tr('Dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($date_start),
    '_END_' => Translator::dateToLocale($date_end),
]);

$total = 0;
$total_attachments = 0;

// Titolo essenziale: la stampa è una Nota spese, il periodo è quello globale selezionato.
echo '<table width="100%" cellspacing="0" cellpadding="0" style="margin:3px 0 8px 0;">'
    .'<tr>'
    .'<td style="vertical-align:bottom;"><div style="font-size:15px;font-weight:bold;line-height:1.05;">'.tr('Nota spese').'</div>'
    .'<div style="font-size:8px;color:#666;margin-top:3px;">'.$period.'</div></td>'
    .'<td style="text-align:right;vertical-align:bottom;font-size:7.5px;color:#777;">'.tr('Solo confermate').'</td>'
    .'</tr></table>';

echo '<table class="table table-bordered table-sm" style="font-size:8px;">'
    .'<thead><tr style="background:#ededed;">'
    .'<th style="width:9%">'.tr('Data').'</th>'
    .'<th style="width:14%">'.tr('Tipologia').'</th>'
    .'<th>'.tr('Descrizione').'</th>'
    .'<th style="width:14%">'.tr('Controparte').'</th>'
    .'<th style="width:14%">'.tr('Operatore').'</th>'
    .'<th style="width:6%;text-align:center">'.tr('Allegati').'</th>'
    .'<th style="width:12%;text-align:right">'.tr('Importo').'</th>'
    .'</tr></thead><tbody>';

if (empty($rows)) {
    echo '<tr><td colspan="7" class="text-center" style="padding:12px;">'.tr('Nessuna nota spesa confermata nel periodo selezionato.').'</td></tr>';
} else {
    foreach ($rows as $row) {
        $total += (float) $row['importo'];
        $total_attachments += (int) $row['allegati'];
        $description = htmlentities((string) $row['descrizione']);
        if (!empty($row['note'])) {
            $description .= '<br><span style="font-size:7px;color:#777;">'.nl2br(htmlentities((string) $row['note'])).'</span>';
        }

        echo '<tr>'
            .'<td>'.Translator::dateToLocale($row['data']).'</td>'
            .'<td>'.htmlentities((string) $row['tipologia']).'</td>'
            .'<td>'.$description.'</td>'
            .'<td>'.htmlentities((string) $row['controparte_display']).'</td>'
            .'<td>'.htmlentities((string) ($row['operatore'] ?: '-')).'</td>'
            .'<td style="text-align:center">'.(int) $row['allegati'].'</td>'
            .'<td style="text-align:right;white-space:nowrap;">'.moneyFormat($row['importo'], 2).'</td>'
            .'</tr>';
    }
}

echo '<tr style="background:#f7f7f7;">'
    .'<td colspan="6" style="text-align:right;font-weight:bold;font-size:9px;">'.tr('Totale', [], ['upper' => true]).':</td>'
    .'<td style="text-align:right;font-weight:bold;font-size:9px;white-space:nowrap;">'.moneyFormat($total, 2).'</td>'
    .'</tr>'
    .'</tbody></table>';

if (!empty($rows)) {
    echo '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top:5px;font-size:7.5px;color:#555;">'
        .'<tr><td>'
        .'<strong>'.tr('Registrazioni').':</strong> '.count($rows)
        .' &nbsp;&nbsp;&nbsp; <strong>'.tr('Allegati').':</strong> '.$total_attachments
        .' &nbsp;&nbsp;&nbsp; <strong>'.tr('Senza allegati').':</strong> '.(int) $without_attachments
        .'</td></tr></table>';
}

if (!empty($groups)) {
    echo '<div style="font-size:11px;font-weight:bold;margin-top:12px;margin-bottom:4px;">'.tr('Totali per tipologia').'</div>'
        .'<table class="table table-bordered table-sm" style="width:62%;font-size:8px;">'
        .'<thead><tr style="background:#f2f2f2;"><th>'.tr('Tipologia').'</th><th style="width:16%;text-align:center">'.tr('Righe').'</th><th style="width:27%;text-align:right">'.tr('Totale').'</th></tr></thead><tbody>';

    foreach ($groups as $group) {
        echo '<tr><td>'.htmlentities((string) $group['tipologia']).'</td><td style="text-align:center">'.(int) $group['righe'].'</td><td style="text-align:right;white-space:nowrap;">'.moneyFormat($group['totale'], 2).'</td></tr>';
    }

    echo '</tbody></table>';
}
