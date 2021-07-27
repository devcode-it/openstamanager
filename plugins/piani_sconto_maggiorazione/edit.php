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

echo '
<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Prezzo articolo secondo i piani di sconto/magg.').'</h3>
		</div>

		<div class="panel-body">';

        $listini = $dbo->fetchArray('SELECT * FROM mg_listini ORDER BY id ASC');

        if (!empty($listini)) {
            echo '
<table class="table table-striped table-condensed table-bordered">
                <tr>
                    <th>'.tr('Piano di sconto/magg.').'</th>
                    <th>'.tr('Prezzo di vendita finale').'</th>
                </tr>';

            // listino base
            echo '
                <tr>
                    <td>'.tr('Base').'</td>
                    <td>'.moneyFormat($articolo->prezzo_vendita).'</td>
                </tr>';

            foreach ($listini as $listino) {
                $prezzo_vendita = $articolo->prezzo_vendita - $articolo->prezzo_vendita * $listino['prc_guadagno'] / 100;
                echo '
<tr>
                    <td>'.$listino['nome'].'</td>
                    <td>'.moneyFormat($prezzo_vendita).'</td>
                </tr>';
            }

            echo '
            </table>';
        } else {
            echo '
    <div class="alert alert-info">
'.tr('Non ci sono piani di sconto/magg. caricati').'... '.Modules::link('Piani di sconto/maggiorazione', null, tr('Crea')).'
</div>';
        }
echo '
    </div>
</div>';
