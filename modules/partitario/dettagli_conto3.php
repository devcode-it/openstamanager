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

include_once __DIR__.'/../../core.php';

use Models\User;

$prima_nota = Modules::get('Prima nota');

$id_conto = get('id_conto');

// Calcolo totale conto da elenco movimenti di questo conto
$query = 'SELECT co_movimenti.*,
    SUM(totale) AS totale,
    dir FROM co_movimenti
LEFT OUTER JOIN co_documenti ON co_movimenti.iddocumento = co_documenti.id
LEFT OUTER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id
WHERE co_movimenti.idconto='.prepare($id_conto).' AND
    co_movimenti.data >= '.prepare($_SESSION['period_start']).' AND
    co_movimenti.data <= '.prepare($_SESSION['period_end']).'
GROUP BY co_movimenti.idmastrino
ORDER BY co_movimenti.data DESC, co_movimenti.id DESC';
$movimenti = $dbo->fetchArray($query);

if (!empty($movimenti)) {
    echo '
<table class="table table-bordered table-hover table-condensed table-striped">
    <tr>
        <th>'.tr('Causale').'</th>
        <th width="100">'.tr('Data').'</th>
        <th width="100">'.tr('Dare').'</th>
        <th width="100">'.tr('Avere').'</th>
        <th width="100">'.tr('Scalare').'</th>
        <th width="80" class="text-center">'.tr('Verificato').'</th>
    </tr>';

    $scalare = array_sum(array_column($movimenti, 'totale'));

    // Elenco righe del partitario
    foreach ($movimenti as $movimento) {
        echo '
    <tr>
        <td>';

        $modulo_fattura = ($movimento['dir'] == 'entrata') ? Modules::get('Fatture di vendita') : Modules::get('Fatture di acquisto');

        if (!empty($movimento['primanota'])) {
            echo Modules::link($prima_nota->id, $movimento['idmastrino'], $movimento['descrizione']);
        } else {
            echo Modules::link($modulo_fattura->id, $movimento['iddocumento'], $movimento['descrizione']);
        }

        echo '
        </td>';

        // Data
        echo '
        <td>
            '.dateFormat($movimento['data']).'
        </td>';

        // Dare
        if ($movimento['totale'] > 0) {
            echo '
        <td class="text-right">
            '.moneyFormat(abs($movimento['totale']), 2).'
        </td>
        <td></td>';
        }

        // Avere
        else {
            echo '
        <td></td>
        <td class="text-right">
            '.moneyFormat(abs($movimento['totale']), 2).'
        </td>';
        }

        echo '
        <td class="text-right">
            '.moneyFormat($scalare, 2).'
        </td>';

        $scalare -= $movimento['totale'];

        // Verificato
        $verified_by = '';
        if ($movimento['verified_by']) {
            $verified_user = User::find($movimento['verified_by']);
            $verified_by = ($movimento['verified_by'] ? tr('Verificato da _USER_ il _DATE_', [
                '_USER_' => $verified_user->username,
                '_DATE_' => dateFormat($movimento['verified_at']).' '.timeFormat($movimento['verified_at']),
            ]) : '');
        }
        echo '
        <td class="text-center">
            <input type="checkbox" id="checked_'.$movimento['id'].'" name="verified['.$movimento['id'].']" class="tip" title="'.$verified_by.'" '.($movimento['verified_at'] ? 'checked' : '').' onclick="Verifica('.$movimento['id'].');" />
        </td>';

        echo '
    </tr>';
    }

    echo '
</table>';
} else {
    echo '
<span>'.tr('Nessun movimento presente').'</span>';
}

echo '
<script>
/*
* Verifica il movimento contabile
*/
function Verifica(id_movimento) {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        data: {
            id_module: globals.id_module,
            op: "manage_verifica",
            id_movimento: id_movimento,
            is_verificato: $("#checked_"+id_movimento).is(":checked") ? 1 : 0
        },
        type: "post",
        success: function(response) {
            $("#checked_"+id_movimento).tooltipster("destroy");
            response = JSON.parse(response);
            if (response.result) {
                $("#checked_"+id_movimento).parent().parent().effect("highlight", {}, 500);
                if ($("#checked_"+id_movimento).is(":checked")) {
                    $("#checked_"+id_movimento).attr("title", "'.tr('Verificato da _USER_ il _DATE_', [
                        '_USER_' => $user->username,
                        '_DATE_' => dateFormat(date('Y-m-d')).' '.date('H:i'),
                    ]).'");
                } else {
                    $("#checked_"+id_movimento).attr("title", "");
                }
            } else {
                $("#checked_"+id_movimento).prop("checked", !$("#checked_"+id_movimento).is(":checked"));
                alert(response.message);
            }
            init();
        }
    });
}
init();
</script>';