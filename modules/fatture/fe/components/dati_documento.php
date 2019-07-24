<?php

// Altri dati gestionali
echo '
    <tbody>
        <tr class="fourth-level">
            <th colspan="2">
                '.str_repeat($space, 3).$info['code'].' '.$info['name'].' - '.tr('Riga _NUM_', [
                    '_NUM_' => $key,
                ]);

if ($key == 1) {
    echo '
                <button type="button" class="btn btn-xs btn-info pull-right" onclick="add_blocco(this, \''.$nome.'\')">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>';
}

echo '
            </th>
        </tr>';

// RiferimentoNumeroLinea
if (empty($dato['riferimento_linea'])) {
    $dato['riferimento_linea'][] = 0;
}

$index = 1;
foreach ($dato['riferimento_linea'] as $linea) {
    echo '
        <tr class="fifth-level" title="RiferimentoNumeroLinea-'.$nome.'-'.$key.'">
            <td style="vertical-align: middle;">
                '.str_repeat($space, 4).$info['code'].'.1 RiferimentoNumeroLinea - '.tr('Riga _NUM_', [
                    '_NUM_' => $index,
                ]);

    if ($index == 1) {
        echo '
                <button type="button" class="btn btn-xs btn-info pull-right" onclick="add_riferimento(this, \''.$nome.'\', \''.$key.'\')">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>';
    }

    echo ' 
            </td>
            <td>
                {[ "type": "number", "name": "'.$nome.'['.$key.'][riferimento_linea][]", "value": "'.$linea.'", "maxlength": 4, "decimals": 0 ]}
            </td>
        </tr>';

    ++$index;
}

// IdDocumento
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).$info['code'].'.2 IdDocumento</td>
            <td>
                {[ "type": "text", "name": "'.$nome.'['.$key.'][id_documento]", "value": "'.$dato['id_documento'].'", "maxlength": 20 ]}
            </td>
        </tr>';

// Data
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).$info['code'].'.3 Data</td>
            <td>
                {[ "type": "date", "name": "'.$nome.'['.$key.'][data]", "value": "'.$dato['data'].'" ]}
            </td>
        </tr>';

// NumItem
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).$info['code'].'.4 NumItem</td>
            <td>
                {[ "type": "text", "name": "'.$nome.'['.$key.'][num_item]", "value": "'.$dato['num_item'].'", "maxlength": 20 ]}
            </td>
        </tr>';

// CodiceCommessaConvenzione
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).$info['code'].'.5 CodiceCommessaConvenzione</td>
            <td>
                {[ "type": "text", "name": "'.$nome.'['.$key.'][codice_commessa]", "value": "'.$dato['codice_commessa'].'", "maxlength": 100 ]}
            </td>
        </tr>';

// CodiceCUP
echo '
        <tr class="fifth-level">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).$info['code'].'.6 CodiceCUP</td>
            <td>
                {[ "type": "text", "name": "'.$nome.'['.$key.'][codice_cup]", "value": "'.$dato['codice_cup'].'", "maxlength": 15 ]}
            </td>
        </tr>';

// CodiceCIG
echo '
        <tr class="fifth-level" id="last-'.$nome.'-'.$key.'">
            <td style="vertical-align: middle;">'.str_repeat($space, 4).$info['code'].'.7 CodiceCIG</td>
            <td>
                {[ "type": "text", "name": "'.$nome.'['.$key.'][codice_cig]", "value": "'.$dato['codice_cig'].'", "maxlength": 15 ]}
            </td>
        </tr>
    </tbody>';
