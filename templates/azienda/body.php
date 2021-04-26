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

use Modules\Banche\Banca;

include_once __DIR__.'/../../core.php';

$banca = Banca::where('id_anagrafica', $anagrafica->idanagrafica)
    ->where('predefined', 1)
    ->first();

/*
    Dati Anagrafici
*/
echo '
<table class="table table-bordered">
    <tr>
        <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Dettaglio anagrafica', [], ['upper' => true]).'</th>
    </tr>
    <tr>
        <td colspan="4" class="text-left" >'.tr('Denominazione').': <b>'.$anagrafica['ragione_sociale'].'</b></td>
    </tr>
    <tr>
        <td colspan="4" class="text-left" >'.tr('Indirizzo').': <b>'.$anagrafica['indirizzo'].'</b></td>
    </tr>
    <tr>
        <td class="text-left" colspan="2">'.tr('Città').': <b>'.$anagrafica['citta'].'</b></td>
        <td class="text-left" >'.tr('Provincia').': <b>'.$anagrafica['provincia'].'</b></td>
        <td class="text-left" >'.tr('Cap').': <b>'.$anagrafica['cap'].'</b></td>
    </tr>
    <tr>
        <td class="text-left" colspan="2" >'.tr('Partita IVA').': <b>'.$anagrafica['piva'].'</b></td>
        <td class="text-left" colspan="2">'.tr('Codice fiscale').': <b>'.$anagrafica['codice_fiscale'].'</b></td>
    </tr>
    <tr>
        <td class="text-left" >'.tr('Banca').': <b>'.$banca->nome.'</b></td>
        <td class="text-left" colspan="2">'.tr('IBAN').': <b>'.$banca->iban.'</b></td>
        <td class="text-left">'.tr('Codice destinatario').': <b>'.$anagrafica['codice_destinatario'].'</b></td>
    </tr>
    <tr>
        <td class="text-left" colspan="2" >'.tr('Pec').': <b>'.$anagrafica['pec'].'</b></td>
        <td class="text-left" colspan="2">'.tr('Email').': <b>'.$anagrafica['email'].'</b></td>
    </tr>
    <tr>
        <td class="text-left" colspan="2" >'.tr('Telefono').': <b>'.$anagrafica['telefono'].'</b></td>
        <td class="text-left">'.tr('cellulare').': <b>'.$anagrafica['cellulare'].'</b></td>
        <td class="text-left">'.tr('fax').': <b>'.$anagrafica['fax'].'</b></td>
    </tr>
</table>';
