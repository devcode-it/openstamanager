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

use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../../core.php';

$pianificazioni = Pianificazione::doesntHave('fattura')
    ->orderBy('data_scadenza', 'asc')
    ->whereHas('contratto', function ($q) {
        $q->whereHas('stato', function ($q) {
            $q->where('is_fatturabile', 1);
        });
    })
    ->get();
if ($pianificazioni->isEmpty()) {
    echo '
<p>'.tr('Non ci sono fatture da emettere').'.</p>';

    return;
}

$raggruppamenti = $pianificazioni->groupBy(function ($item) {
    return ucfirst($item->data_scadenza->formatLocalized('%B %Y'));
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
    <a class='clickable' onclick=\"if( $('#rate_pianificare_".$counter."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#rate_pianificare_".$counter."').slideToggle();\">
        <i class='fa ".$class."'></i> ".$mese.'
    </a>
</h4>';

    echo '
<div id="rate_pianificare_'.$counter.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="25%">'.tr('Entro il').'</th>
                <th width="35%">'.tr('Ragione sociale').'</th>
                <th width="20%">'.tr('Importo').'</th>
                <th width="10%"></th>
            </tr>
        </thead>

        <tbody>';

    // Elenco fatture da emettere
    foreach ($pianificazioni as $pianificazione) {
        $contratto = $pianificazione->contratto;
        $anagrafica = $contratto->anagrafica;
        $numero_pianificazioni = $contratto->pianificazioni()->count();

        if (strtolower($pianificazione->data_scadenza->formatLocalized('%B %Y')) == strtolower($mese)) {
            echo '
            <tr>
                <td>
                    '.dateFormat($pianificazione->data_scadenza).'
                    <br><small>'.reference($contratto).'</small>
                </td>

                <td>
                    '.Modules::link('Anagrafiche', $anagrafica->id, nl2br($anagrafica->ragione_sociale)).'
                </td>

                <td>
                    '.moneyFormat($pianificazione->totale).'<br>
                    <small>'.tr('Rata _IND_/_NUM_ (totale: _TOT_)', [
                        '_IND_' => numberFormat($pianificazione->getNumeroPianificazione(), 0),
                        '_NUM_' => numberFormat($numero_pianificazioni, 0),
                    '_TOT_' => moneyFormat($contratto->totale),
                ]).'</small>
                </td>';

            // Pulsanti
            echo '
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm" onclick="crea_fattura('.$contratto->id.', '.$pianificazione->id.')">
                        <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                    </button>
                </td>
            </tr>';
        }
    }

    echo '
        </tbody>
    </table>
</div>';
}

$modulo_pianificazione = Modules::get('Contratti');
$plugin_pianificazione = Plugins::get('Pianificazione fatturazione');
echo '
<script>
function crea_fattura(contratto, rata){
    openModal("Crea fattura", "'.$plugin_pianificazione->fileurl('crea_fattura.php').'?id_module='.$modulo_pianificazione->id.'&id_plugin='.$plugin_pianificazione->id.'&id_record=" + contratto + "&rata=" + rata);
}
</script>';
