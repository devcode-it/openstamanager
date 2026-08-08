<?php

// Prints::loader() acquisisce l'header tramite output buffering: il file deve
// quindi produrre output con echo, non restituire una stringa con return.
$identity = trim((string) ($f_ragionesociale ?? ''));
$fiscal = [];
if (!empty($f_piva)) {
    $fiscal[] = tr('P.Iva').': '.$f_piva;
}
if (!empty($f_codicefiscale)) {
    $fiscal[] = tr('C.F.').': '.$f_codicefiscale;
}

echo '
<table width="100%" cellspacing="0" cellpadding="0" style="border-bottom:0.5px solid #d8d8d8; padding-bottom:2px; margin-bottom:4px; color:#666;">
    <tr>
        <td style="width:70%; vertical-align:bottom; font-size:7px; line-height:1.15;">
            <strong style="font-size:7.5px; color:#444;">'.htmlentities($identity).'</strong>'
            .(!empty($fiscal) ? ' &nbsp;|&nbsp; '.htmlentities(implode(' · ', $fiscal)) : '').'
        </td>
        <td style="width:30%; text-align:right; vertical-align:bottom; font-size:7px; color:#888;">'.tr('Documento interno').'</td>
    </tr>
</table>';
