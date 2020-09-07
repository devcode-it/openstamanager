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

use Plugins\PianificazioneInterventi\Promemoria;

include_once __DIR__.'/../../../core.php';

$elenco_promemoria = Promemoria::doesntHave('intervento')->get();
if ($elenco_promemoria->isEmpty()) {
    echo '
<p>'.tr('Non ci sono promemoria da pianificare').'.</p>';

    return;
}

$raggruppamenti = $elenco_promemoria->groupBy(function ($item) {
    return ucfirst($item->data_richiesta->formatLocalized('%B %Y'));
});

$counter = 0;
foreach ($raggruppamenti as $mese => $raggruppamento) {
    ++$counter;

    // Se cambia il mese ricreo l'intestazione della tabella
    if ($counter == 1) {
        $attr = '';
        $class = 'fa-minus-circle';
    } else {
        $attr = 'style="display:none;"';
        $class = 'fa-plus-circle';
    }

    echo "
<h4>
    <a class='clickable' onclick=\"if( $('#promemoria_pianificare_".$counter."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#promemoria_pianificare_".$counter."').slideToggle();\">
        <i class='fa ".$class."'></i> ".$mese.'
    </a>
</h4>';

    echo '
<div id="promemoria_pianificare_'.$counter.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="120">'.tr('Cliente').'</th>
                <th width="250">'.tr('Contratto').'</th>
                <th width="90">'.tr('Entro').'</th>
                <th width="150">'.tr('Tipo attività').'</th>
                <th width="300">'.tr('Descrizione').'</th>
                <th width="100">'.tr('Sede').'</th>
                <th width="18"></th>
            </tr>
        </thead>

        <tbody>';

    // Elenco promemoria da pianificare
    foreach ($elenco_promemoria as $promemoria) {
        $contratto = $promemoria->contratto;
        $anagrafica = $contratto->anagrafica;

        echo '
            <tr>
                <td>
                    '.Modules::link('Anagrafiche', $anagrafica->id, nl2br($anagrafica->ragione_sociale)).'
                </td>

                <td>
                    '.reference($contratto).'
                </td>

                <td>'.dateFormat($promemoria->data_richiesta).'</td>
                <td>'.$promemoria->tipo->descrizione.'</td>
                <td>'.nl2br($promemoria->richiesta).'</td>

                <td>';

        // Sede
        if ($promemoria->idsede == '-1') {
            echo '- '.('Nessuna').' -';
        } elseif (empty($promemoria->idsede)) {
            echo tr('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($promemoria->idsede));

            echo $rsp2[0]['descrizione'];
        }

        echo '
                </td>';

        // Pulsanti
        echo '
                <td>
                    <button type="button" class="btn btn-primary btn-sm" title="Pianifica intervento ora..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica intervento\', \''.$rootdir.'/add.php?id_module='.Modules::get('Interventi')['id'].'&ref=interventi_contratti&idcontratto='.$contratto->id.'&idcontratto_riga='.$promemoria->id.'\');">
                        <i class="fa fa-calendar"></i>
                    </button>
                </td>
            </tr>';
    }

    echo '
        </tbody>
    </table>
</div>';
}
