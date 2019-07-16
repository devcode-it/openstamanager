<?php

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
                {[ "type": "number", "name": "altri_dati['.$key.'][riferimento_numero]", "value": "'.$dato['tipo_dato'].'" ]}
            </td>
        </tr>';

// Riferimento Data
echo '
        <tr class="fifth-level" id="last-altri_dati-'.$key.'">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).'2.2.1.16.4 RiferimentoData</td>
            <td>
                {[ "type": "date", "name": "altri_dati['.$key.'][riferimento_data]", "value": "'.$dato['tipo_dato'].'"]}
            </td>
        </tr>
    </tbody>';
