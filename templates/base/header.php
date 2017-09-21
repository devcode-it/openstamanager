<?php

/**
 * Header di default.
 * I contenuti di questo file vengono utilizzati per generare l'header delle stampe nel caso non esista un file header.php all'interno della stampa.
 *
 * Per modificare l'header della stampa basta aggiungere un file header.php all'interno della cartella della stampa con i contenuti da mostrare (vedasi templates/fatture/header.php).
 *
 * La personalizzazione specifica dell'header deve comunque seguire lo standard della cartella custom: anche se il file header.php non esiste nella stampa originaria, se si vuole personalizzare l'header bisogna crearlo all'interno della cartella custom.
 */

return '
<div class="row">
    <div class="col-xs-6">
        <img src="$directory$/logo_azienda.jpg" alt="Logo" border="0"/>
    </div>
    <div class="col-xs-6 text-right">
        <p><b>'.$f_ragionesociale.'</b></p>
        <p>'.$f_indirizzo.' '.$f_citta_full.'</p>
        <p>'.(!empty($f_piva) ? tr('P.Iva').': '.$f_piva : '').'</p>
        <p>'.(!empty($f_codicefiscale) ? tr('C.F.').': '.$f_codicefiscale : '').'</p>
        <p>'.(!empty($f_capsoc) ? tr('Cap.Soc.').': '.$f_capsoc : '').'</p>
        <p>'.(!empty($f_telefono) ? tr('Tel').': '.$f_telefono : '').'</p>
    </div>
</div>';
