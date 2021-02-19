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

// Creazione righe fantasma
$autofill = new \Util\Autofill($options['pricing'] ? 7 : 4);
$rows_per_page = 16;
if (!empty($options['last-page-footer'])) {
    $rows_per_page += 10;
}
$autofill->setRows($rows_per_page);

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:5%'>".tr('#', [], ['upper' => true])."</th>
            <th class='text-center'>".tr('Cod.', [], ['upper' => true])."</th>
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
foreach ($righe as $riga) {
    ++$num;
    $r = $riga->toArray();

    $autofill->count($r['descrizione']);

    echo '
    <tr>
        <td class="text-center" style="vertical-align: middle">
            '.$num.'
        </td>

        <td class="text-center" nowrap="nowrap" style="vertical-align: middle">';

    $source_type = get_class($riga);
    if ($riga->isArticolo()) {
        echo $riga->codice;
    } else {
        echo '-';
    }

    echo '
        </td>

        <td>
            '.nl2br($r['descrizione']);

    //Riferimenti odrini/ddt righe
    if ($riga->referenceTargets()->count()) {
        $source = $source_type::find($riga->id);
        $riferimenti = $source->referenceTargets;

        foreach ($riferimenti as $riferimento) {
            $documento_riferimento = $riferimento->target->getDocument();
            echo '
            <br><small>'.$riferimento->target->descrizione.'<br>'.tr('Rif. _DOCUMENT_', [
                '_DOCUMENT_' => strtolower($documento_riferimento->getReference()),
            ]).'</small>';
        }
    }

    if ($riga->isArticolo()) {
        // Codice articolo
        $text = tr('COD. _COD_', [
            '_COD_' => $riga->codice,
        ]);
        echo '
                <br><small>'.$text.'</small>';

        $autofill->count($text, true);

        // Seriali
        $seriali = $riga->serials;
        if (!empty($seriali)) {
            $text = tr('SN').': '.implode(', ', $seriali);
            echo '
                    <br><small>'.$text.'</small>';

            $autofill->count($text, true);
        }
    }

    // Aggiunta dei riferimenti ai documenti
    /*
    if (setting('Riferimento dei documenti nelle stampe') && $riga->hasOriginal()) {
        $ref = $riga->getOriginal()->getDocument()->getReference();

        if (!empty($ref)) {
            echo '
                <br><small>'.$ref.'</small>';

            $autofill->count($ref, true);
        }
    }
    */

    echo '
        </td>';

    if (!$riga->isDescrizione()) {
        echo '
            <td class="text-center" nowrap="nowrap">
                '.Translator::numberToLocale(abs($riga->qta), 'qta').' '.$r['um'].'
            </td>';

        if ($options['pricing']) {
            // Prezzo unitario
            echo '
            <td class="text-right" nowrap="nowrap">
				'.moneyFormat($riga->prezzo_unitario);

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
				'.moneyFormat($riga->totale_imponibile).'
            </td>';

            // Iva
            echo '
            <td class="text-center" nowrap="nowrap">
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
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
