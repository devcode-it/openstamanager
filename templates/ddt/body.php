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

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Creazione righe fantasma
$autofill = new Util\Autofill($options['pricing'] ? 6 : 3, 70);
$rows_per_page = $options['pricing'] ? 20 : 18;
$autofill->setRows($rows_per_page, 0, $options['pricing'] ? 20 : 18);

// Intestazione tabella per righe
echo "
<table class='table table-striped border-bottom' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:5%'>".tr('#', [], ['upper' => true])."</th>
            <th class='text-center'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center'>".tr('Q.tà', [], ['upper' => true]).'</th>';

if ($options['pricing']) {
    echo "
            <th class='text-center'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center'>".tr('IVA', [], ['upper' => true]).' (%)</th>';
}

echo '
        </tr>
    </thead>

    <tbody>';

// Righe documento
$righe = $documento->getRighe();
$num = 0;

if (!setting('Visualizza riferimento su ogni riga in stampa')) {
    $riferimenti = [];
    $id_rif = [];

    foreach ($righe as $riga) {
        $riferimento = ($riga->getOriginalComponent() ? $riga->getOriginalComponent()->getDocument()->getReference() : null);
        if (!empty($riferimento)) {
            if (!array_key_exists($riferimento, $riferimenti)) {
                $riferimenti[$riferimento] = [];
            }

            if (!in_array($riga->id, $riferimenti[$riferimento])) {
                $id_rif[] = $riga->id;
                $riferimenti[$riferimento][] = $riga->id;
            }
        }
    }
}

foreach ($righe as $riga) {
    ++$num;
    $r = $riga->toArray();

    echo '
    <tr>
        <td class="text-center" style="vertical-align: middle">';

    $text = '';

    foreach ($riferimenti as $key => $riferimento) {
        if (in_array($riga->id, $riferimento)) {
            if ($riga->id === $riferimento[0]) {
                $riga_ordine = $database->fetchOne('SELECT numero_cliente, data_cliente FROM or_ordini WHERE id = '.prepare($riga->idordine));
                if (!empty($riga_ordine['numero_cliente']) && !empty($riga_ordine['data_cliente'])) {
                    $text = $text.'<b>Ordine n. '.$riga_ordine['numero_cliente'].' del '.Translator::dateToLocale($riga_ordine['data_cliente']).'</b><br>';
                }
                $r['descrizione'] = str_replace('Rif. '.strtolower((string) $key), '', $r['descrizione']);

                if (preg_match("/Rif\.(.*)/s", $r['descrizione'], $rif2)) {
                    $r['descrizione'] = str_replace('Rif.'.strtolower($rif2[1]), '', $r['descrizione']);
                    $text .= '<b>'.$rif2[0].'</b>';
                }

                $text .= '<b>'.$key.'</b>';

                if ($options['pricing']) {
                    $text .= '</td><td></td><td></td><td>';
                }
                $text .= '</td><td></td></tr><tr><td class="text-center" nowrap="nowrap" style="vertical-align: middle">';

                echo '
                </td>
        
                <td>
                    '.nl2br($text);
                    $autofill->count($text);
            }
        }
        $r['descrizione'] = preg_replace("/Rif\.(.*)/s", '', (string) $r['descrizione']);
    }

    $source_type = $riga::class;
    $autofill->count($r['descrizione']);
    
    echo $num.'
        </td>
        <td>'.nl2br((string) $r['descrizione']);
    

    if ($riga->isArticolo()) {
        echo '<br><small>'.$riga->codice.'</small>';
        $autofill->count($riga->codice);
    } else {
        echo '-';
    }

    if ($riga->isArticolo()) {
        // Seriali
        $seriali = $riga->serials;
        if (!empty($seriali)) {
            $text = tr('SN').': '.implode(', ', $seriali);
            echo '
                    <small>'.$text.'</small>';

            $autofill->count($text, true);
        }
    }

    echo '
        </td>';

    if (!$riga->isDescrizione()) {
        echo '
            <td class="text-center" nowrap="nowrap">
                '.Translator::numberToLocale(abs($riga->qta), $d_qta).' '.$r['um'].'
            </td>';

        if ($options['pricing']) {
            // Prezzo unitario
            echo '
            <td class="text-right" nowrap="nowrap">
				'.moneyFormat($prezzi_ivati ? $riga->prezzo_unitario_ivato : $riga->prezzo_unitario, $d_importi);

            if ($riga->sconto > 0) {
                $text = discountInfo($riga, false);

                echo '
                <br><small class="text-muted">'.$text.'</small>';

                $autofill->count($text, true);
            }

            echo '
            </td>';

            // Imponibile
            echo '
            <td class="text-right" nowrap="nowrap">
				'.moneyFormat($prezzi_ivati ? $riga->totale : $riga->totale_imponibile, $d_importi).'
            </td>';

            // Iva
            echo '
            <td class="text-center" nowrap="nowrap">
                '.Translator::numberToLocale($riga->aliquota->percentuale, $d_importi).'
            </td>';
        }
    } else {
        echo '
            <td></td>';

        if ($options['pricing']) {
            echo '
            <td></td>
            <td></td>
            <td></td>';
        }
    }

    echo '
        </tr>';

    $autofill->next();
}

echo '
        |autofill|
    </tbody>
</table>';
