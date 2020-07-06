<?php

/**
 * Footer di default.
 * I contenuti di questo file vengono utilizzati per generare il footer delle stampe nel caso non esista un file footer.php all'interno della stampa.
 *
 * Per modificare il footer della stampa basta aggiungere un file footer.php all'interno della cartella della stampa con i contenuti da mostrare (vedasi templates/fatture/footer.php).
 *
 * La personalizzazione specifica del footer deve comunque seguire lo standard della cartella custom: anche se il file footer.php non esiste nella stampa originaria, se si vuole personalizzare il footer bisogna crearlo all'interno della cartella custom.
 */

return '
<table style="color:#aaa; font-size:10px;">
<tr>
    <td align="left" style="width:97mm;">
        '.tr('Stampato con OpenSTAManager').'
    </td>

    <td class="text-right" style="width:97mm;">
        '.tr('Pagina _PAGE_ di _TOTAL_', [
            '_PAGE_' => '{PAGENO}',
            '_TOTAL_' => '{nb}',
        ]).'
    </td>
</tr>
</table>';
