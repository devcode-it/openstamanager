<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

/*
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
