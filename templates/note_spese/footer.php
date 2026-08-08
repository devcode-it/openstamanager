<?php

// Come per l'header, Prints::loader() cattura l'output del file.
echo '
<table width="100%" cellspacing="0" cellpadding="0" style="border-top:0.5px solid #e0e0e0; color:#999; font-size:6.5px; padding-top:2px;">
    <tr>
        <td style="width:60%;">'.tr('Nota spese').'</td>
        <td style="width:40%; text-align:right;">'.tr('Pagina _PAGE_ di _TOTAL_', [
    '_PAGE_' => '{PAGENO}',
    '_TOTAL_' => '{nb}',
]).'</td>
    </tr>
</table>';
