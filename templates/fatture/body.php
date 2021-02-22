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

$v_iva = [];
$v_totale = [];

// Creazione righe fantasma
$autofill = new \Util\Autofill(6, 40);
$rows_per_page = $fattura_accompagnatoria ? 13 : 18;
if (!empty($options['last-page-footer'])) {
    $rows_per_page += 7;
}
$autofill->setRows($rows_per_page);

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:5%'>".tr('#', [], ['upper' => true])."</th>
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:14%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:16%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:20%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
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

    $v_iva[$r['desc_iva']] = sum($v_iva[$r['desc_iva']], $riga->iva);
    $v_totale[$r['desc_iva']] = sum($v_totale[$r['desc_iva']], $riga->totale_imponibile);

    echo '
        <tr>';

    echo '
        <td class="text-center" style="vertical-align: middle">
            '.$num.'
        </td>';

    echo '
            <td>
                '.nl2br(strip_tags($riga->descrizione));

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
    if (!empty($record['ref_documento'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti WHERE id = ".prepare($record['ref_documento']));

        $text = tr('Rif. fattura _NUM_ del _DATE_', [
            '_NUM_' => $data[0]['numero'],
            '_DATE_' => Translator::dateToLocale($data[0]['data']),
        ]);

        echo '
        <br><small>'.$text.'</small>';

        $autofill->count($text, true);
    }

    // Aggiunta dei riferimenti ai documenti
    /*
    if (setting('Riferimento dei documenti nelle stampe') && $riga->hasOriginal()) {
        $ref = $riga->getOriginal()->getDcocument()->getReference();
        if (!empty($riga->getOriginal()->getDcocument()->numero_cliente)) {
            $ref .= '<br>'.tr('_DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => 'Rif. Vs. ordine cliente',
                '_NUM_' => $riga->getOriginalComponent()->getDocument()->numero_cliente,
                '_DATE_' => dateFormat($riga->getOriginalComponent()->getDocument()->data_cliente),
            ]);
        }
        if (!empty($ref)) {
            echo '
                <br><small>'.$ref.'</small>';

            $autofill->count($ref, true);
        }
    }*/

    // Informazioni su CIG, CUP, ...
    if ($riga->hasOriginalComponent()) {
        $documento_originale = $riga->getOriginalComponent()->getDocument();

        $num_item = $documento_originale['num_item'];
        $codice_commessa = $documento_originale['codice_commessa'];
        $codice_cig = $documento_originale['codice_cig'];
        $codice_cup = $documento_originale['codice_cup'];
        $id_documento_fe = $documento_originale['id_documento_fe'];

        $extra_riga = replace('_ID_DOCUMENTO__NUMERO_RIGA__CODICE_COMMESSA__CODICE_CIG__CODICE_CUP_', [
            '_ID_DOCUMENTO_' => $id_documento_fe ? 'DOC: '.$id_documento_fe : null,
            '_NUMERO_RIGA_' => $num_item ? ', NRI: '.$num_item : null,
            '_CODICE_COMMESSA_' => $codice_commessa ? ', COM: '.$codice_commessa : null,
            '_CODICE_CIG_' => $codice_cig ? ', CIG: '.$codice_cig : null,
            '_CODICE_CUP_' => $codice_cup ? ', CUP: '.$codice_cup : null,
        ]);

        echo '
        <br><small>'.$extra_riga.'</small>';
    }

    echo '
            </td>';

    if (!$riga->isDescrizione()) {
        echo '
            <td class="text-center">
                '.Translator::numberToLocale(abs($riga->qta), 'qta').' '.$r['um'].'
            </td>';

        // Prezzo unitario
        echo '
            <td class="text-right">
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
            <td class="text-right">
				'.moneyFormat($riga->totale_imponibile).'
            </td>';

        // Iva
        echo '
            <td class="text-center">
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
            </td>';
    } else {
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    }

    echo '
        </tr>';

    $autofill->next();
}

echo '
        |autofill|
    </tbody>
</table>';

// Aggiungo diciture particolari per l'anagrafica cliente
$dicitura = $dbo->fetchOne('SELECT diciturafissafattura AS dicitura FROM an_anagrafiche WHERE idanagrafica = '.prepare($id_cliente));

if (!empty($dicitura['dicitura'])) {
    echo '
<p class="text-left">
    <span>'.nl2br($dicitura['dicitura']).'</span>
</p>';
}

// Aggiungo diciture per condizioni iva particolari
foreach ($v_iva as $key => $value) {
    $dicitura = $dbo->fetchOne('SELECT dicitura FROM co_iva WHERE descrizione = '.prepare($key));

    if (!empty($dicitura['dicitura'])) {
        echo '
<p class="text-center">
    <b>'.nl2br($dicitura['dicitura']).'</b>
</p>';
    }
}
echo '
<table class="table">';
echo '
    <tr>
        <td width="100%">';

    if (!empty($record['note'])) {
        echo '
            <p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
            <p>'.nl2br($record['note']).'</p>';
    }

    echo '
        </td>';

echo '
    </tr>';
echo '
</table>';
