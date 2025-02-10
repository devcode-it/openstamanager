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

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Creazione righe fantasma
$autofill = new Util\Autofill(6, 70);
$rows_per_page = ($fattura_accompagnatoria ? 24 : 26);
$autofill->setRows($rows_per_page, 0);

// Conteggio le righe da sottrarre al totale
$c = 0;
$documento['note'] ? $c += 3 : null;
foreach ($v_iva as $desc_iva => $tot_iva) {
    ++$c;
}
$destinazione ? ($codice_destinatario ? $c += 2 : ++$c) : null;

// Diminuisco le righe disponibili per pagina
$autofill->setRows($rows_per_page - $c, 0);

// Intestazione tabella per righe
echo "
<table class='table table-striped' id='contents'>
    <thead>
        <tr>
            <th class='text-center border-bottom' style='width:5%'>".tr('#', [], ['upper' => true])."</th>
            <th class='text-center border-bottom' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center border-bottom' style='width:14%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center border-bottom' style='width:16%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center border-bottom' style='width:20%'>".tr('Importo', [], ['upper' => true])."</th>
            <th class='text-center border-bottom' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>
        </tr>
    </thead>

    <tbody>';

// Righe documento
if (setting('Raggruppa attività per tipologia in fattura')) {
    $righe = get_righe_composte($documento);
} else {
    $righe = $documento->getRighe();
}

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

    $v_iva[$r['desc_iva']] = sum($v_iva[$r['desc_iva']], $riga->iva);
    $v_totale[$r['desc_iva']] = sum($v_totale[$r['desc_iva']], $riga->totale_imponibile);

    echo '
    <tr>
        <td class="text-center" style="vertical-align: middle">';

    $text = '';

    foreach ($riferimenti as $key => $riferimento) {
        if (in_array($riga->id, $riferimento)) {
            if ($riga->id === $riferimento[0]) {
                $riga_ordine = $database->fetchOne('SELECT numero_cliente, data_cliente FROM or_ordini WHERE id = '.prepare($riga->idordine));
                if (!setting('Visualizza numero ordine cliente')) {
                    if (!empty($riga_ordine['numero_cliente']) && !empty($riga_ordine['data_cliente'])) {
                        $text = $text.'<b>Ordine n. '.$riga_ordine['numero_cliente'].' del '.Translator::dateToLocale($riga_ordine['data_cliente']).'</b><br>';
                    }
                }
                $r['descrizione'] = str_replace("\nRif. ".strtolower((string) $key), '', $r['descrizione']);

                if (preg_match("/Rif\.(.*)/s", $r['descrizione'], $rif2)) {
                    $r['descrizione'] = str_replace('\nRif.'.strtolower($rif2[1] ?: ''), '', $r['descrizione']);
                    $text .= '<b>'.$rif2[0].'</b><br>';
                }

                $text .= '<b>'.$key.'</b></td><td></td><td></td><td></td><td></td></tr><tr><td class="text-center" nowrap="nowrap" style="vertical-align: middle">';

                echo '
                    </td>
            
                    <td>
                        '.nl2br($text);
                $autofill->count($text);
            }
        }
        $r['descrizione'] = preg_replace("/(\r\n|\r|\n)Rif\.(.*)/s", '', (string) $r['descrizione']);
    }

    $source_type = $riga::class;

    $autofill->count($r['descrizione']);
    echo $num.'
    </td>
    <td>'.nl2br((string) $r['descrizione']);

    if ($riga->isArticolo()) {
        echo '<small><br>'.$riga->codice.'</small>';
        $autofill->count($riga->codice, true);
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
                '.Translator::numberToLocale(abs($riga->qta), $d_qta).' '.$r['um'].'
            </td>';

        // Prezzo unitario
        echo '
            <td class="text-right">
				'.moneyFormat($prezzi_ivati ? $riga->prezzo_unitario_ivato : $riga->prezzo_unitario, $d_importi);

        if ($riga->sconto > 0) {
            $text = discountInfo($riga, false);

            echo '
                <br><small class="text-muted">'.$text.'</small>';
        }

        echo '
            </td>';

        // Imponibile
        echo '
            <td class="text-right">
				'.moneyFormat($prezzi_ivati ? ($riga->totale_imponibile + $riga->iva) : $riga->totale_imponibile, $d_importi).'
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

$diciture = [];

// Aggiungo diciture particolari per l'anagrafica cliente
$diciture[] = $dbo->fetchOne('SELECT `diciturafissafattura` AS dicitura FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($id_cliente))['dicitura'];

// Aggiungo diciture per condizioni iva particolari
foreach ($v_iva as $key => $value) {
    $diciture[] = $dbo->fetchOne('SELECT `dicitura` FROM `co_iva` LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `title` = '.prepare($key))['dicitura'];
}

// Conteggio righe per dicitura condizioni IVA
foreach ($diciture as $dicitura) {
    $autofill->count($dicitura);
}

$autofill->next();

echo '
        |autofill|
    </tbody>
</table>';

// Aggiungo diciture per condizioni iva particolari
foreach ($diciture as $dicitura) {
    if (!empty($dicitura)) {
        echo '
<p class="text-center">
    <b>'.nl2br((string) $dicitura).'</b>
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
            <p class="small-bold text-muted">'.tr('Note', [], ['upper' => true]).':</p>
            <p><small>'.nl2br((string) $record['note']).'</small></p>';
}

echo '
        </td>';

echo '
    </tr>';
echo '
</table>';
