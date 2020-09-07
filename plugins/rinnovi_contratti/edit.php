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

include_once __DIR__.'/../../core.php';

$id_contratto_precedente = $record['idcontratto_prev'];

if (empty($id_contratto_precedente)) {
    echo '
    <script>$("#link-tab_'.$plugin['id'].'").addClass("disabled");</script>';
}

echo '
    <table class="table table-hover table-condensed table-bordered table-striped">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="100">'.tr('Totale').'</th>
            <th width="150">'.tr('Data inizio').'</th>
            <th width="150">'.tr('Data conclusione').'</th>
        </tr>';

while (!empty($id_contratto_precedente)) {
    $rs = $dbo->fetchArray('SELECT nome, numero, data_accettazione, data_conclusione, budget, idcontratto_prev FROM co_contratti WHERE id='.prepare($id_contratto_precedente));

    echo '
        <tr>
            <td>
                '.Modules::link($id_module, $id_contratto_precedente, tr('Contratto num. _NUM_', [
                    '_NUM_' => $rs[0]['numero'],
                ]).'<br><small class="text-muted">'.$rs[0]['nome'].'</small>').'
            </td>
            <td class="text-right">'.moneyFormat($rs[0]['budget']).'</td>
            <td align="center">'.Translator::dateToLocale($rs[0]['data_accettazione']).'</td>
            <td align="center">'.Translator::dateToLocale($rs[0]['data_conclusione']).'</td>
        </tr>';

    $id_contratto_precedente = $rs[0]['idcontratto_prev'];
}

echo '
    </table>';
