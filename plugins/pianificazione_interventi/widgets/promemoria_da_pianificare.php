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

use Carbon\Carbon;
use Models\Module;
use Plugins\PianificazioneInterventi\Promemoria;

include_once __DIR__.'/../../../core.php';

$elenco_promemoria = Promemoria::doesntHave('intervento')->orderByraw('data_richiesta ASC')->get();

$array_promemoria = [];
foreach ($elenco_promemoria as $promemoria) {
    $data_pro = new Carbon($promemoria->data_richiesta);
    $array_promemoria[$data_pro->format('Y-m')][] = $promemoria;
}

if ($elenco_promemoria->isEmpty()) {
    echo '
<p>'.tr('Non ci sono promemoria da pianificare').'.</p>';

    return;
}

$raggruppamenti = $elenco_promemoria->groupBy(fn ($item) => $item->data_richiesta->format('Y-m'));

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

    $nome_mese = new Carbon($mese.'-01');

    echo "
<h4>
    <a class='clickable' onclick=\"if( $('#promemoria_pianificare_".$counter."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#promemoria_pianificare_".$counter."').slideToggle();\">
    <i class='fa ".$class."'></i> ".ucfirst($nome_mese->isoFormat('MMMM YYYY')).'
    </a>
</h4>';

    echo '
<div id="promemoria_pianificare_'.$counter.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="200">'.tr('Cliente').'</th>
                <th width="300">'.tr('Contratto').'</th>
                <th width="70">'.tr('Entro').'</th>
                <th width="200">'.tr('Tipo attività').'</th>
                <th>'.tr('Descrizione').'</th>
                <th width="20"></th>
            </tr>
        </thead>

        <tbody>';

    // Elenco promemoria da pianificare
    foreach ($array_promemoria[$mese] as $promemoria) {
        $contratto = $promemoria->contratto;
        $anagrafica = $contratto->anagrafica;

        echo '
            <tr>
                <td>
                    '.Modules::link('Anagrafiche', $anagrafica->id, nl2br((string) $anagrafica->ragione_sociale)).'<br><small>Presso: ';

        // Sede promemoria
        if ($promemoria->idsede == '-1') {
            echo '- Nessuna -';
        } elseif (empty($promemoria->idsede)) {
            echo tr('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($promemoria->idsede));

            echo $rsp2[0]['descrizione'];
        }

        echo '  </small>
                </td>

                <td>
                    '.reference($contratto).'
                </td>

                <td>'.dateFormat($promemoria->data_richiesta).'</td>
                <td>'.$promemoria->tipo->getTranslation('title').'</td>
                <td>'.nl2br((string) $promemoria->richiesta).'</td>

                <td>

                </td>';

        // Pulsanti
        echo '
                <td>
                    <button type="button" class="btn btn-primary btn-sm" title="Pianifica intervento ora..." data-widget="tooltip" onclick="launch_modal(\'Pianifica intervento\', \''.base_path_osm().'/add.php?id_module='.Module::where('name', 'Interventi')->first()->id.'&ref=interventi_contratti&idcontratto='.$contratto->id.'&idcontratto_riga='.$promemoria->id.'\');">
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
