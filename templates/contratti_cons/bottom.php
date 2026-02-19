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

use Modules\TipiIntervento\Tipo;

// fix per generazione allegato email
include __DIR__.'/../riepilogo_interventi/bottom.php';

$budget = get_imponibile_contratto($id_record);
$somma_totale_imponibile = get_totale_interventi_contratto($id_record);
$rapporto = floatval($budget) - floatval($somma_totale_imponibile) - $documento->provvigione;

$rs = $dbo->fetchArray("SELECT SUM(qta) AS totale_ore FROM `co_righe_contratti` WHERE um='ore' AND idcontratto = ".prepare($id_record));
$totale_ore = $rs[0]['totale_ore'];
$totale_ore_impiegate = $records->sum('ore_totali_da_conteggiare');

if ($pricing || !empty($totale_ore)) {
    // Totale imponibile
    echo '
<table class="table table-bordered">';
    if ($pricing && empty($options['dir'])) {
        // TOTALE
        echo '
    <tr>
    	<td colspan="3" class="text-right border-top">
            <b>'.tr('Totale consuntivo (no iva)', [], ['upper' => true]).':</b>
    	</td>
    	<th colspan="2" class="text-center">
    		<b>'.moneyFormat($somma_totale_imponibile).'</b>
    	</th>
    </tr>';

        // BUDGET
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Budget (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.moneyFormat($budget).'</b>
        </th>
    </tr>';

        // RAPPORTO
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Rapporto budget/spesa (no IVA)', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.moneyFormat($rapporto).'</b>
        </th>
    </tr>';
    }

    // ORE RESIDUE
    if (!empty($totale_ore)) {
        echo '
    <tr>
        <td colspan="3" class="text-right border-top">
            <b>'.tr('Ore residue', [], ['upper' => true]).':</b>
        </td>
        <th colspan="2" class="text-center">
            <b>'.Translator::numberToLocale($totale_ore - $totale_ore_impiegate).'</b><br>
            <p>'.tr('Ore erogate').': '.Translator::numberToLocale($totale_ore_impiegate).'</p>
            <p>'.tr('Ore a contratto').': '.Translator::numberToLocale($totale_ore).'</p>
        </th>
    </tr>';
    }

    echo '
</table>';

    // Riepilogo ore per tipo di attività
    if (!empty($totale_ore)) {
        // Raggruppa le ore per tipo di attività
        $tipologie_ore_per_riga = [];
        
        foreach ($records as $intervento) {
            $sessioni = $intervento->sessioni()
                ->leftJoin('in_tipiintervento', 'in_interventi_tecnici.idtipointervento', 'in_tipiintervento.id')
                ->where('non_conteggiare', 0)
                ->get();
            
            foreach ($sessioni as $sessione) {
                $tipo_title = $sessione->tipo->getTranslation('title');
                
                if (!isset($tipologie_ore_per_riga[$tipo_title])) {
                    $tipologie_ore_per_riga[$tipo_title] = [
                        'ore_totali' => 0,
                        'ore_completate' => 0,
                    ];
                }
                
                $tipologie_ore_per_riga[$tipo_title]['ore_totali'] += $sessione->ore;
                
                if (!empty($intervento->stato->is_bloccato)) {
                    $tipologie_ore_per_riga[$tipo_title]['ore_completate'] += $sessione->ore;
                }
            }
        }
        
        // Raggruppa le ore a contratto per tipo di attività dalle righe del documento
        $ore_contratto_per_tipo = [];
        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if ($riga->um == 'ore') {
                $id_tipointervento = $riga->id_tipointervento ?? null;
                
                if (!empty($id_tipointervento)) {
                    $tipo_intervento = Tipo::where('id', $id_tipointervento)->first();
                    if ($tipo_intervento) {
                        $tipo_name = $tipo_intervento->name;
                        if (!isset($ore_contratto_per_tipo[$tipo_name])) {
                            $ore_contratto_per_tipo[$tipo_name] = 0;
                        }
                        $ore_contratto_per_tipo[$tipo_name] += $riga->qta;
                    }
                }
            }
        }
        
        // Crea una mappatura tra tipi attività sessioni e tipi attività contratto (case-insensitive)
        $mappa_tipi = [];
        foreach ($tipologie_ore_per_riga as $tipo_sessione => $dati) {
            // Cerca una corrispondenza diretta case-insensitive
            foreach ($ore_contratto_per_tipo as $tipo_contratto => $ore) {
                if (strcasecmp($tipo_sessione, $tipo_contratto) == 0) {
                    $mappa_tipi[$tipo_sessione] = $tipo_contratto;
                    break;
                }
            }
            // Se non trovato, usa null
            if (!isset($mappa_tipi[$tipo_sessione])) {
                $mappa_tipi[$tipo_sessione] = null;
            }
        }
        
        echo '
<br>
<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="6" class="text-center">'.tr('Riepilogo ore per tipo di attività', [], ['upper' => true]).'</th>
        </tr>
        <tr>
            <th>'.tr('Tipo attività').'</th>
            <th class="text-center">'.tr('Ore a contratto').'</th>
            <th class="text-center">'.tr('Ore erogate').'</th>
            <th class="text-center">'.tr('Ore residue').'</th>
            <th class="text-center">'.tr('Ore concluse').'</th>
            <th class="text-center">'.tr('Ore residue concluse').'</th>
        </tr>
    </thead>
    <tbody>';
        
        // Mostra le righe raggruppate per tipo di attività delle sessioni
        foreach ($tipologie_ore_per_riga as $tipo => $dati) {
            // Cerca le ore a contratto per questo tipo di attività usando la mappatura
            $ore_contratto = 0;
            $tipo_riga_mappato = $mappa_tipi[$tipo] ?? null;
            
            if ($tipo_riga_mappato !== null && isset($ore_contratto_per_tipo[$tipo_riga_mappato])) {
                $ore_contratto = $ore_contratto_per_tipo[$tipo_riga_mappato];
            }
            
            $ore_erogate = $dati['ore_totali'];
            $ore_concluse = $dati['ore_completate'];
            
            $ore_residue = floatval($ore_contratto) - floatval($ore_erogate);
            $ore_residue_concluse = floatval($ore_contratto) - floatval($ore_concluse);
            
            $bg_class_residue = $ore_residue >= 0 ? 'text-primary' : 'text-danger';
            $bg_class_residue_concluse = $ore_residue_concluse >= 0 ? 'text-primary' : 'text-danger';
            
            echo '
        <tr>
            <td><strong>'.$tipo.'</strong></td>
            <td class="text-center">'.Translator::numberToLocale($ore_contratto).'</td>
            <td class="text-center">'.Translator::numberToLocale($ore_erogate).'</td>
            <td class="text-center font-weight-bold '.$bg_class_residue.'">'.Translator::numberToLocale($ore_residue).'</td>
            <td class="text-center">'.Translator::numberToLocale($ore_concluse).'</td>
            <td class="text-center font-weight-bold '.$bg_class_residue_concluse.'">'.Translator::numberToLocale($ore_residue_concluse).'</td>
        </tr>';
        }
        
        // Calcola i totali
        $totale_ore_erogate = 0;
        $totale_ore_concluse = 0;
        
        foreach ($tipologie_ore_per_riga as $tipo => $dati) {
            $totale_ore_erogate += $dati['ore_totali'];
            $totale_ore_concluse += $dati['ore_completate'];
        }
        
        $totale_ore_residue = floatval($totale_ore) - floatval($totale_ore_erogate);
        $totale_ore_residue_concluse = floatval($totale_ore) - floatval($totale_ore_concluse);
        
        $bg_class_residue_totali = $totale_ore_residue >= 0 ? 'text-primary' : 'text-danger';
        $bg_class_residue_concluse_totali = $totale_ore_residue_concluse >= 0 ? 'text-primary' : 'text-danger';
        
        // Riga totali
        echo '
        <tr class="table-dark font-weight-bold">
            <td>'.tr('TOTALE').'</td>
            <td class="text-center">'.Translator::numberToLocale($totale_ore).'</td>
            <td class="text-center">'.Translator::numberToLocale($totale_ore_erogate).'</td>
            <td class="text-center '.$bg_class_residue_totali.'">'.Translator::numberToLocale($totale_ore_residue).'</td>
            <td class="text-center">'.Translator::numberToLocale($totale_ore_concluse).'</td>
            <td class="text-center '.$bg_class_residue_concluse_totali.'">'.Translator::numberToLocale($totale_ore_residue_concluse).'</td>
        </tr>
    </tbody>
</table>';
    }
}
