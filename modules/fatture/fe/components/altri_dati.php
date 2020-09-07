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

// Altri dati gestionali
echo '
    <tbody>
        <tr class="fourth-level">
            <th colspan="2">
                '.str_repeat($space, 3).'2.2.1.16 AltriDatiGestionali - '.tr('Riga _NUM_', [
                    '_NUM_' => $key,
                ]);

if ($key == 1) {
    echo '
                <button type="button" class="btn btn-xs btn-info pull-right" onclick="add_altri_dati(this)" id="add-altri_dati">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>';
}

echo '
            </th>
        </tr>';

// Tipo Dato
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).'2.2.1.16.1 TipoDato</td>
            <td>
                {[ "type": "text", "name": "altri_dati['.$key.'][tipo_dato]", "value": "'.$dato['tipo_dato'].'", "maxlength": 10 ]}
            </td>
        </tr>';

// Riferimento Testo
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).'2.2.1.16.2 RiferimentoTesto</td>
            <td>
                {[ "type": "text", "name": "altri_dati['.$key.'][riferimento_testo]", "value": "'.$dato['riferimento_testo'].'", "maxlength": 60 ]}
            </td>
        </tr>';

// Riferimento Numero
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).'2.2.1.16.3 RiferimentoNumero</td>
            <td>
                {[ "type": "number", "name": "altri_dati['.$key.'][riferimento_numero]", "value": "'.$dato['riferimento_numero'].'" ]}
            </td>
        </tr>';

// Riferimento Data
echo '
        <tr class="fifth-level" id="last-altri_dati-'.$key.'">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).'2.2.1.16.4 RiferimentoData</td>
            <td>
                {[ "type": "date", "name": "altri_dati['.$key.'][riferimento_data]", "value": "'.$dato['riferimento_data'].'"]}
            </td>
        </tr>
    </tbody>';
