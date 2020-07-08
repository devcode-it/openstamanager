<?php

include_once __DIR__.'/../../core.php';

/*
    Dati intervento
*/
echo '
<table class="table table-bordered">
    <tr>
        <th colspan="4" style="font-size:13pt;" class="text-center">'.tr('Rapporto attività e interventi', [], ['upper' => true]).'</th>
    </tr>

    <tr>
        <td class="text-left" style="width:30%">'.tr('Intervento n.').': <b>'.$documento['codice'].'</b></td>
        <td class="text-left" style="width:20%">'.tr('Data richiesta').': <b>'.Translator::dateToLocale($documento['data_richiesta']).'</b></td>
        <td class="text-left" style="width:25%">'.tr('Preventivo n.').': <b>'.(!empty($preventivo) ? $preventivo['numero'] : '').'</b></td>
        <td class="text-left" style="width:25%">'.tr('Contratto n.').': <b>'.(!empty($contratto) ? $contratto['numero'] : '').'</b></td>
    </tr>';

    // Dati cliente
    echo '
        <tr>
            <td colspan=2>
                '.tr('Cliente').': <b>'.$c_ragionesociale.'</b>
            </td>';

    // Codice fiscale o P.Iva

    if (!empty($c_piva)) {
        echo '
				<td colspan=2>
					'.tr('P.Iva').': <b>'.strtoupper($c_piva).'</b>
				</td>';
    } else {
        echo '
    			<td colspan=2>
    				'.tr('C.F.').': <b>'.strtoupper($c_codicefiscale).'</b>
    			</td>';
    }

    echo '</tr>';

    //Indirizzo
    if (!empty($s_indirizzo) or !empty($s_cap) or !empty($s_citta) or !empty($s_provincia)) {
        echo '
			<tr>
				<td colspan="4">
					'.((!empty($s_indirizzo)) ? tr('Via').': <b>'.$s_indirizzo.'</b>' : '').'
					'.((!empty($s_cap)) ? tr('CAP').': <b>'.$s_cap.'</b>' : '').'
					'.((!empty($s_citta)) ? tr('Città').': <b>'.$s_citta.'</b>' : '').'
					'.((!empty($s_provincia)) ? tr('Provincia').': <b>'.strtoupper($s_provincia).'</b>' : '').'
				</td>
			</tr>';
    } elseif (!empty($c_indirizzo) or !empty($c_cap) or !empty($c_citta) or !empty($c_provincia)) {
        echo '
			<tr>
				<td colspan="4">
					'.((!empty($c_indirizzo)) ? tr('Via').': <b>'.$c_indirizzo.'</b>' : '').'
					'.((!empty($c_cap)) ? tr('CAP').': <b>'.$c_cap.'</b>' : '').'
					'.((!empty($c_citta)) ? tr('Città').': <b>'.$c_citta.'</b>' : '').'
					'.((!empty($c_provincia)) ? tr('Provincia').': <b>'.strtoupper($c_provincia).'</b>' : '').'
				</td>
			</tr>';
    }

echo '
    <tr>
        <td colspan="4">
            '.tr('Telefono').': <b>'.$c_telefono.'</b>';
    if (!empty($c_cellulare)) {
        echo' - '.tr('Cellulare').': <b>'.$c_cellulare.'</b>';
    }
echo '
        </td>
    </tr>';

// riga 3
// Elenco impianti su cui è stato fatto l'intervento
$rs2 = $dbo->fetchArray('SELECT *, (SELECT nome FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS nome, (SELECT matricola FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS matricola FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
$impianti = [];
for ($j = 0; $j < count($rs2); ++$j) {
    $impianti[] = '<b>'.$rs2[$j]['nome']."</b> <small style='color:#777;'>(".$rs2[$j]['matricola'].')</small>';
}
echo '
    <tr>
        <td colspan="4">
        '.tr('Impianti').': '.implode(', ', $impianti).'
        </td>
    </tr>';

// Tipo intervento
echo '
    <tr>
        <td colspan="4">
            <b>'.tr('Tipo intervento').':</b> '.$documento->tipo->descrizione.'
        </td>
    </tr>';

// Richiesta
echo '
    <tr>
        <td colspan="4" style="height:20mm;">
            <b>'.tr('Richiesta').':</b>
            <p>'.nl2br($documento['richiesta']).'</p>
        </td>
    </tr>';

// Descrizione
// Rimosso nl2br, non necessario con ckeditor
echo '
    <tr>
        <td colspan="4" style="height:20mm;">
            <b>'.tr('Descrizione').':</b>
            <p>'.($documento['descrizione']).'</p>
        </td>
    </tr>';

echo '
</table>';

$righe = $documento->getRighe();

if (!$righe->isEmpty()) {
    echo '
<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="4" class="text-center">
                <b>'.tr('Materiale utilizzato e spese aggiuntive', [], ['upper' => true]).'</b>
            </th>
        </tr>

        <tr>
            <th style="font-size:8pt;width:50%" class="text-center">
                <b>'.tr('Descrizione').'</b>
            </th>

            <th style="font-size:8pt;width:15%" class="text-center">
                <b>'.tr('Q.tà').'</b>
            </th>

            <th style="font-size:8pt;width:15%" class="text-center">
                <b>'.tr('Prezzo unitario').'</b>
            </th>

            <th style="font-size:8pt;width:20%" class="text-center">
                <b>'.tr('Importo').'</b>
            </th>
        </tr>
    </thead>

    <tbody>';

    foreach ($righe as $riga) {
        // Articolo
        echo '
    <tr>
        <td>
            '.nl2br($riga->descrizione);

        if ($riga->isArticolo()) {
            // Codice articolo
            $text = tr('COD. _COD_', [
                '_COD_' => $riga->codice,
            ]);
            echo '
                <br><small>'.$text.'</small>';

            // Seriali
            $seriali = $riga->serials;
            if (!empty($seriali)) {
                $text = tr('SN').': '.implode(', ', $seriali);
                echo '
                    <br><small>'.$text.'</small>';
            }
        }

        echo '
        </td>';

        // Quantità
        echo '
        <td class="text-center">
            '.Translator::numberToLocale($riga->qta, 'qta').' '.$riga->um.'
        </td>';

        // Prezzo unitario
        echo '
        <td class="text-center">
            '.($options['pricing'] ? moneyFormat($riga->prezzo_unitario_corrente) : '-');

        if ($options['pricing'] && $riga->sconto > 0) {
            $text = discountInfo($riga, false);

            echo '
            <br><small class="text-muted">'.$text.'</small>';
        }

        echo '
        </td>';

        // Prezzo totale
        echo '
        <td class="text-center">
            '.($options['pricing'] ? Translator::numberToLocale($riga->importo) : '-').'
        </td>
    </tr>';
    }

    echo '
    </tbody>';

    if ($options['pricing']) {
        // Totale spese aggiuntive
        echo '
    <tr>
        <td colspan="3" class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.moneyFormat($righe->sum('importo'), 2).'</b>
        </th>
    </tr>';
    }

    echo '
</table>';
}

// INTESTAZIONE ELENCO TECNICI
echo '
<table class="table table-bordered vertical-middle">
    <thead>
        <tr>
            <th class="text-center" colspan="5" style="font-size:11pt;">
                <b>'.tr('Ore tecnici', [], ['upper' => true]).'</b>
            </th>
        </tr>
        <tr>
            <th class="text-center" style="font-size:8pt;">
                <b>'.tr('Tecnico').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:14%">
                <b>'.tr('Data').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:7%">
                <b>'.tr('Dalle').'</b>
            </th>

            <th class="text-center" style="font-size:8pt;width:7%">
                <b>'.tr('Alle').'</b>
            </th>

            <td class="text-center" style="font-size:6pt;width:35%">
                '.tr('I dati del ricevente verrano trattati in base alla normativa europea UE 2016/679 del 27 aprile 2016 (GDPR)').'
            </td>
        </tr>
    </thead>

    <tbody>';

// Sessioni di lavoro dei tecnici

$sessioni = $documento->sessioni;
foreach ($sessioni as $i => $sessione) {
    echo '
    <tr>';

    // Nome tecnico
    echo '
    	<td>
    	    '.$sessione->anagrafica->ragione_sociale.'
    	</td>';

    // Data
    echo '
    	<td class="text-center">
            '.Translator::dateToLocale($sessione['orario_inizio']).'
    	</td>';

    // Ora inizio
    echo '
    	<td class="text-center">
            '.Translator::timeToLocale($sessione['orario_inizio']).'
    	</td>';

    // Ora fine
    echo '
    	<td class="text-center">
            '.Translator::timeToLocale($sessione['orario_fine']).'
        </td>';

    // Spazio aggiuntivo
    if ($i == 0) {
        echo '
    	<td class="text-center" style="font-size:6pt;">
            '.tr('Si dichiara che i lavori sono stati eseguiti ed i materiali installati nel rispetto delle vigenti normative tecniche').'
        </td>';
    } else {
        echo '
    	<td class="text-center" style="border-bottom:0px;border-top:0px;"></td>';
    }

    echo '
    </tr>';
}

// Ore lavorate
echo '
    <tr>
        <td class="text-center">
            <small>'.tr('Ore lavorate').':</small><br/><b>'.Translator::numberToLocale($documento->ore_totali, 2).'</b>
        </td>';

// Costo totale manodopera
if ($options['pricing']) {
    echo '
        <td colspan="3" class="text-center">
            <small>'.tr('Totale manodopera').':</small><br/><b>'.moneyFormat($sessioni->sum('prezzo_manodopera'), 2).'</b>
        </td>';
} else {
    echo '
        <td colspan="3" class="text-center">-</td>';
}

// Timbro e firma
$firma = !empty($documento['firma_file']) ? '<img src="'.DOCROOT.'/files/interventi/'.$documento['firma_file'].'" style="width:70mm;">' : '';
echo '
        <td rowspan="2" class="text-center" style="font-size:8pt;height:30mm;vertical-align:bottom">
            '.$firma.'<br>
            <i>('.tr('Timbro e firma leggibile').'.)</i>
        </td>
    </tr>';

// Totale km
echo '
    <tr>
        <td class="text-center">
            <small>'.tr('Km percorsi').':</small><br/><b>'.Translator::numberToLocale($documento->km_totali, 2).'</b>
        </td>';

// Costo trasferta
if ($options['pricing']) {
    echo '
        <td class="text-center">
            <small>'.tr('Costi di trasferta').':</small><br/><b>'.moneyFormat($sessioni->sum('prezzo_viaggio'), 2).'</b>
        </td>';
} else {
    echo '
        <td class="text-center">-</td>';
}

// Diritto di chiamata
if ($options['pricing']) {
    echo '
        <td class="text-center" colspan="2">
            <small>'.tr('Diritto di chiamata').':</small><br/><b>'.moneyFormat($sessioni->sum('prezzo_diritto_chiamata'), 2).'</b>
        </td>';
} else {
    echo '
        <td class="text-center" colspan="2">-</td>';
}

// Calcoli
$imponibile = abs($documento->imponibile);
$sconto = $documento->sconto;
$totale_imponibile = abs($documento->totale_imponibile);
$totale_iva = abs($documento->iva);
$totale = abs($documento->totale);
$netto_a_pagare = abs($documento->netto);

$show_sconto = $sconto > 0;

$incorpora_iva = setting('Utilizza prezzi di vendita comprensivi di IVA');

// TOTALE COSTI FINALI
if ($options['pricing']) {
    if ($incorpora_iva) {
        // TOTALE INTERVENTO
        echo '
    <tr>
    	<td colspan="4" class="text-right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
    	</td>
    	<th class="text-center">
    		<b>'.moneyFormat($totale, 2).'</b>
    	</th>
    </tr>';
    } else {
        // Totale imponibile
        echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            '.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, 2).'
        </th>
    </tr>';

        // Eventuale sconto totale
        if ($show_sconto) {
            echo '
        <tr>
            <td colspan="4" class="text-right">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
            </td>

            <th class="text-center">
                <b>'.moneyFormat($sconto, 2).'</b>
            </th>
        </tr>';

            // Totale imponibile
            echo '
        <tr>
            <td colspan="4" class="text-right">
                <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
            </td>

            <th class="text-center">
                <b>'.moneyFormat($totale_imponibile, 2).'</b>
            </th>
        </tr>';
        }

        // IVA
        // Totale intervento
        echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

        // TOTALE INTERVENTO
        echo '
    <tr>
    	<td colspan="4" class="text-right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
    	</td>
    	<th class="text-center">
    		<b>'.moneyFormat($totale, 2).'</b>
    	</th>
    </tr>';
    }
}

echo '
</table>';
