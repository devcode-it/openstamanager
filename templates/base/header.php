<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Header di default.
 * I contenuti di questo file vengono utilizzati per generare l'header delle stampe nel caso non esista un file header.php all'interno della stampa.
 *
 * Per modificare l'header della stampa basta aggiungere un file header.php all'interno della cartella della stampa con i contenuti da mostrare (vedasi templates/fatture/header.php).
 *
 * La personalizzazione specifica dell'header deve comunque seguire lo standard della cartella custom: anche se il file header.php non esiste nella stampa originaria, se si vuole personalizzare l'header bisogna crearlo all'interno della cartella custom.
 */

return '
<div class="row" style="'.((!empty($settings['header-font-size'])) ? 'font-size:'.($settings['header-font-size']).'px;' : '').'"  >
    <div class="col-xs-6">
        <img src="$logo$" alt="Logo" border="0"/>
    </div>
    <div class="col-xs-6 text-right" >
        <p><b>'.$f_ragionesociale.'</b></p>
        <p>'.$f_indirizzo.'</p>
        <p>'.$f_citta_full.'</p>
        <p>'.(!empty($f_piva) ? tr('P.Iva').': '.$f_piva : '').'</p>
        <p>'.(!empty($f_codicefiscale) ? tr('C.F.').': '.$f_codicefiscale : '').'</p>
        <p>'.(!empty($f_capsoc) ? tr('Cap.Soc.').': '.$f_capsoc : '').'</p>
        <p>'.(!empty($f_telefono) ? tr('Tel').': '.$f_telefono : '').'</p>
		<p>'.(!empty($f_email) ? tr('Email').': '.$f_email : '').'</p>
		<p>'.(!empty($f_pec) ? tr('PEC').': '.$f_pec : '').'</p>
		<p>'.(!empty($f_sitoweb) ? tr('Web').': '.$f_sitoweb : '').'</p>
    </div>
</div>';
