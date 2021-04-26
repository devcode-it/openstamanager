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

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

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
        <td class="text-left" colspan="2" >'.tr('Pec').': <b>'.$anagrafica['pec'].'</b></td>
        <td class="text-left" colspan="2">'.tr('Email').': <b>'.$anagrafica['email'].'</b></td>
    </tr>
    <tr>
        <td class="text-left" colspan="2" >'.tr('Telefono').': <b>'.$anagrafica['telefono'].'</b></td>
        <td class="text-left">'.tr('cellulare').': <b>'.$anagrafica['cellulare'].'</b></td>
        <td class="text-left">'.tr('fax').': <b>'.$anagrafica['fax'].'</b></td>
    </tr>
    <tr>
        <td colspan="4" class="text-left" >'.tr('Agente').': <b>'.$agente['ragione_sociale'].'</b></td>
    </tr>
</table>';

/*
    Sedi
*/

$sedi = $dbo->fetchArray('SELECT * FROM an_sedi WHERE idanagrafica='.prepare($anagrafica->idanagrafica));
if (!empty($sedi)) {
    echo '
    <table class="table table-bordered">
        <tr>
            <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Sedi', [], ['upper' => true]).'</th>
        </tr>';

    for ($i = 0; count($sedi) > $i; ++$i) {
        echo '
            <tr>
                <td colspan="4" class="text-left" >'.tr('Nome sede').': <b>'.$sedi[$i]['nomesede'].'</b></td>
            </tr>
            <tr>
                <td colspan="4" class="text-left" >'.tr('Indirizzo').': <b>'.$sedi[$i]['indirizzo'].'</b></td>
            </tr>
            <tr>
                <td class="text-left" colspan="2">'.tr('Città').': <b>'.$sedi[$i]['citta'].'</b></td>
                <td class="text-left" >'.tr('Provincia').': <b>'.$sedi[$i]['prov'].'</b></td>
                <td class="text-left" >'.tr('Cap').': <b>'.$sedi[$i]['cap'].'</b></td>
            </tr>
            <tr>
                <td class="text-left" colspan="2" >'.tr('telefono').': <b>'.$sedi[$i]['telefono'].'</b></td>
                <td class="text-left" colspan="2">'.tr('cellulare').': <b>'.$sedi[$i]['cellulare'].'</b></td>
            </tr>';

        if (!empty($sedi[$i + 1])) {
            echo '
                <tr><td colspan="4"></td></tr>';
        }
    }

    echo '
    </table>';
}

/*
    Impianti
*/

$impianti = $dbo->fetchArray('SELECT * FROM my_impianti WHERE idanagrafica='.prepare($anagrafica->idanagrafica));

if (!empty($impianti)) {
    echo '
    <table class="table table-bordered">
        <tr>
            <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Impianti', [], ['upper' => true]).'</th>
        </tr>';

    for ($i = 0; count($impianti) > $i; ++$i) {
        echo '
            <tr>
                <td colspan="2" class="text-left" >'.tr('Matricola').': <b>'.$impianti[$i]['matricola'].'</b></td>
                <td colspan="2" class="text-left" >'.tr('Data').': <b>'.Translator::dataToLocale($impianti[$i]['data']).'</b></td>
            </tr>
            <tr>
                <td colspan="4" class="text-left" >'.tr('Nome').': <b>'.$impianti[$i]['nome'].'</b></td>
            </tr>
            <tr>
                <td colspan="4" class="text-left" >'.tr('descrizione').': <b>'.$impianti[$i]['descrizione'].'</b></td>
            </tr>';

        if (!empty($impianti[$i + 1])) {
            echo '
                <tr><td colspan="4"></td></tr>';
        }
    }

    echo '
    </table>';
}

/*
    Attività
*/

$interventi = $dbo->fetchArray('SELECT id, sessione.inizio FROM in_interventi LEFT JOIN (SELECT MIN(orario_inizio) AS inizio, in_interventi_tecnici.idintervento FROM in_interventi_tecnici GROUP BY in_interventi_tecnici.idintervento) AS sessione ON sessione.idintervento=in_interventi.id  WHERE idanagrafica='.prepare($anagrafica->idanagrafica));

if (!empty($interventi)) {
    echo '
    <table class="table table-bordered">
        <tr>
            <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Attività', [], ['upper' => true]).'</th>
        </tr>';

    for ($i = 0; count($interventi) > $i; ++$i) {
        $intervento = Intervento::find($interventi[$i]['id']);
        echo '
            <tr>
                <td class="text-left">'.tr('Data richiesta').': <b>'.Translator::dateToLocale($intervento->data_richiesta).'</b></td>
                <td class="text-left" colspan="2" >'.tr('Data scadenza').': <b>'.Translator::dateToLocale($intervento->data_scadenza).'</b></td>
                <td class="text-left" >'.tr('Data inizio').': <b>'.Translator::dateToLocale($interventi[$i]['inizio']).'</b></td>
            </tr>
            <tr>
                <td colspan="2" class="text-left" >'.tr('Tipo').': <b>'.$intervento->tipo->descrizione.'</b></td>
                <td colspan="2" class="text-left" >'.tr('stato').': <b>'.$intervento->stato->descrizione.'</b></td>
            </tr>
            <tr>
                <td colspan="4" class="text-left" >'.tr('richiesta').': <b>'.$intervento->richiesta.'</b></td>
            </tr>';

        if (!empty($interventi[$i + 1])) {
            echo '
                <tr><td colspan="4"></td></tr>';
        }
    }

    echo '
    </table>';
}
