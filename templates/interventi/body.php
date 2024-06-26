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

use Carbon\Carbon;
use Models\Module;
use Modules\Checklists\Check;

include_once __DIR__.'/../../core.php';

$d_qta = (int) setting('Cifre decimali per quantità in stampa');
$d_importi = (int) setting('Cifre decimali per importi in stampa');
$d_totali = (int) setting('Cifre decimali per totali in stampa');

/*
    Dati intervento
*/
echo '
<table class="table border-bottom">
    <tr>
        <th colspan="4" class="text-center" style="font-size:11pt;">'.tr('Rapporto attività', [], ['upper' => true]).'</th>
    </tr>
    
    <tr>
        <td>
            <p class="small-bold text-muted">'.tr('Cliente', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$c_ragionesociale.'
        </td>';
// Indirizzo
if (!empty($s_indirizzo) or !empty($s_cap) or !empty($s_citta) or !empty($s_provincia)) {
    echo '

        <td>
            <p class="small-bold text-muted">'.tr('Indirizzo', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$s_indirizzo.' '.$s_cap.' - '.$s_citta.' ('.strtoupper((string) $s_provincia).')
        </td>';
} elseif (!empty($c_indirizzo) or !empty($c_cap) or !empty($c_citta) or !empty($c_provincia)) {
    echo '

        <td>
            <p class="small-bold text-muted">'.tr('Indirizzo', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$c_indirizzo.' '.$c_cap.' - '.$c_citta.' ('.strtoupper((string) $c_provincia).')
        </td>';
}
echo '
    </tr>
    <tr>
        <td>
            <p class="small-bold text-muted">'.tr('Attività n.', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$documento['codice'].'
        </td>
        <td>
            <p class="small-bold text-muted">'.tr('Data richiesta', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.Translator::dateToLocale($documento['data_richiesta']).'
        </td>
    </tr>';

// Dati cliente

if (!empty($preventivo) or !empty($contratto)) {
    echo '
    <tr>
        <td>
            <p class="small-bold text-muted">'.tr('Preventivo n.', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.(!empty($preventivo) ? $preventivo['numero'].' del '.Translator::dateToLocale($preventivo['data_bozza']) : 'Nessuno').'
        </td>
        <td>
            <p class="small-bold text-muted">'.tr('Contratto n.', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.(!empty($contratto) ? $contratto['numero'].' del '.Translator::dateToLocale($contratto['data_bozza']) : 'Nessuno').'
        </td>
    </tr>';
}

echo '
    <tr>
        <td>
            <p class="small-bold text-muted">'.tr('P.Iva', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.strtoupper((string) $c_piva).'
        </td>
        <td>
            <p class="small-bold text-muted">'.tr('C.F.', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.strtoupper((string) $c_codicefiscale).'
        </td>
    </tr>

    <tr>
        <td>
            <p class="small-bold text-muted">'.tr('Telefono', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$c_telefono.'
        </td>
        <td>
            <p class="small-bold text-muted">'.tr('Cellulare', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$c_cellulare.'
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
        <td>
            <p class="small-bold text-muted">'.tr('Impianti', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.implode(', ', $impianti).'
        </td>
        <td>
            <p class="small-bold text-muted">'.tr('Tipo intervento', [], ['upper' => true]).'</p>
        </td>
        <td class="text-right">
            '.$documento->tipo->getTranslation('title').'
        </td>
    </tr>';

// Richiesta
// Rimosso nl2br, non necessario con ckeditor
echo '
    <tr>
        <td colspan="2">
            <p class="small-bold text-muted">'.tr('Richiesta', [], ['upper' => true]).'</p>
        </td>
        <td style="width:350px" colspan="2" class="text-right">
            <p>'.$documento['richiesta'].'</p>
        </td>
    </tr>';

// Descrizione
// Rimosso nl2br, non necessario con ckeditor
echo '
    <tr>
        <td colspan="2">
            <p class="small-bold text-muted">'.tr('Descrizione', [], ['upper' => true]).'</p>
        </td>
        <td style="width:350px" colspan="2" class="text-right">
            <p>'.$documento['descrizione'].'</p>
        </td>
    </tr>
</table>';

$righe = $documento->getRighe();

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

if (!$righe->isEmpty()) {
    echo '
<table class="table border-bottom">
    <thead>
        <tr>
            <th colspan="4" class="text-center">
                <b>'.tr('Materiale utilizzato e spese aggiuntive', [], ['upper' => true]).'</b>
            </th>
        </tr>

        <tr>
            <th style="font-size:8pt;width:50%" class="text-center text-muted">
                <b>'.tr('Descrizione').'</b>
            </th>

            <th style="font-size:8pt;width:15%" class="text-center text-muted">
                <b>'.tr('Q.tà').'</b>
            </th>

            <th style="font-size:8pt;width:15%" class="text-center text-muted">
                <b>'.tr('Prezzo unitario').'</b>
            </th>

            <th style="font-size:8pt;width:20%" class="text-center text-muted">
                <b>'.tr('Importo').'</b>
            </th>
        </tr>
    </thead>

    <tbody>';

    foreach ($righe as $riga) {
        if (setting('Formato ore in stampa') == 'Sessantesimi') {
            if ($riga->um == 'ore') {
                $qta = Translator::numberToHours($riga->qta);
            } else {
                $qta = Translator::numberToLocale($riga->qta, $d_qta);
            }
        } else {
            $qta = Translator::numberToLocale($riga->qta, $d_qta);
        }
        // Articolo
        echo '
        <tr>
            <td>';
        $text = '';

        foreach ($riferimenti as $key => $riferimento) {
            if (in_array($riga->id, $riferimento)) {
                if ($riga->id === $riferimento[0]) {
                    $riga_ordine = $riga->getOriginalComponent()->getDocument();
                    $text = '<b>'.$key.'</b><br>';

                    if ($options['pricing']) {
                        $text = $text.'</td><td></td><td>';
                    }
                    $text = $text.'</td><td></td></tr><tr><td>';

                    echo nl2br($text);
                }
            }
            $riga['descrizione'] = str_replace('Rif. '.strtolower((string) $key), '', $riga['descrizione']);
        }

        $source_type = $riga::class;

        if (!setting('Visualizza riferimento su ogni riga in stampa')) {
            echo $riga['descrizione'];
        } else {
            echo nl2br((string) $riga['descrizione']);
        }

        if ($riga->isArticolo()) {
            echo nl2br('<br><small>'.$riga->codice.'</small>');
        }

        if ($riga->isArticolo()) {
            // Seriali
            $seriali = $riga->serials;
            if (!empty($seriali)) {
                $text = tr('SN').': '.implode(', ', $seriali);
                echo '
                        <small>'.$text.'</small>';
            }
        }

        echo '
            </td>';

        // Quantità
        echo '
        <td class="text-center">
            '.$qta.' '.$riga->um.'
        </td>';

        // Prezzo unitario
        echo '
        <td class="text-center">
            '.($options['pricing'] ? moneyFormat($riga->prezzo_unitario_corrente, $d_importi) : '-');

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
            '.($options['pricing'] ? moneyFormat($riga->importo, $d_importi) : '-').'
        </td>
    </tr>';
    }

    echo '
    </tbody>';

    if ($options['pricing']) {
        // Totale spese aggiuntive
        echo '
    <tr>
        <td colspan="3" class="text-right text-muted">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.moneyFormat($righe->sum('importo'), $d_totali).'</b>
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
            <th class="text-center" colspan="5">
                <b>'.tr('Ore tecnici', [], ['upper' => true]).'</b>
            </th>
        </tr>
        <tr>
            <th class="text-center small-bold text-muted" style="font-size:8pt;width:30%">
                <b>'.tr('Tecnico').'</b>
            </th>

            <th class="text-center small-bold text-muted" colspan="3" style="font-size:8pt;width:35%">
                <b>'.tr('Orario').'</b>
            </th>

            <td class="text-center" style="font-size:6pt;width:35%">
                '.tr('I dati del ricevente verrano trattati in base alla normativa europea UE 2016/679 del 27 aprile 2016 (GDPR)').'
            </td>
        </tr>
    </thead>

    <tbody>';

// Sessioni di lavoro dei tecnici
$sessioni = $documento->sessioni->sortBy('orario_inizio');
foreach ($sessioni as $i => $sessione) {
    echo '
    <tr>';
    // Nome tecnico
    echo '
    	<td>
            '.$sessione->anagrafica->ragione_sociale.'
            ('.$sessione->tipo->getTranslation('title').')
    	</td>';

    $inizio = new Carbon($sessione['orario_inizio']);
    $fine = new Carbon($sessione['orario_fine']);
    if ($inizio->isSameDay($fine)) {
        $orario = timestampFormat($inizio).' - '.timeFormat($fine);
    } else {
        $orario = timestampFormat($inizio).' - '.timestampFormat($fine);
    }

    // Orario
    echo '
    	<td class="text-center" colspan="3">
            '.$orario.'
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
if (setting('Formato ore in stampa') == 'Sessantesimi') {
    $ore_totali = Translator::numberToHours($documento->ore_totali);
} else {
    $ore_totali = Translator::numberToLocale($documento->ore_totali, $d_totali);
}

echo '
    <tr>
        <td class="text-center">
            <small>'.tr('Ore lavorate').':</small><br/><b>'.$ore_totali.'</b>
        </td>';

// Costo totale manodopera
if ($options['pricing']) {
    echo '
        <td colspan="3" class="text-center">
            <small>'.tr('Totale manodopera').':</small><br/><b>'.moneyFormat($sessioni->sum('prezzo_manodopera'), $d_totali).'</b>
        </td>';
} else {
    echo '
        <td colspan="3" class="text-center">-</td>';
}

// Timbro e firma
$firma = !empty($documento['firma_file']) ? '<img src="'.base_dir().'/files/interventi/'.$documento['firma_file'].'" style="width:70mm;">' : '';

echo '
        <td rowspan="2" class="text-center" style="font-size:8pt;height:30mm;vertical-align:bottom">
            '.$firma.'<br>';

if (empty($documento['firma_file'])) {
    echo '      <i>('.tr('Timbro e firma leggibile').')</i>';
} else {
    echo '      <i>'.$documento['firma_nome'].'</i>';
}

echo '
        </td>
    </tr>';

// Totale km
echo '
    <tr>
        <td class="text-center">
            <small>'.tr('Km percorsi').':</small><br/><b>'.Translator::numberToLocale($documento->km_totali, $d_qta).'</b>
        </td>';

// Costo trasferta
if ($options['pricing']) {
    echo '
        <td class="text-center">
            <small>'.tr('Costi di trasferta').':</small><br/><b>'.moneyFormat($sessioni->sum('prezzo_viaggio'), $d_totali).'</b>
        </td>';
} else {
    echo '
        <td class="text-center">-</td>';
}

// Diritto di chiamata
if ($options['pricing']) {
    echo '
        <td class="text-center" colspan="2" width="120px" >
            <small>'.tr('Diritto di chiamata').':</small><br/><b>'.moneyFormat($sessioni->sum('prezzo_diritto_chiamata'), $d_totali).'</b>
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

// TOTALE COSTI FINALI
if ($options['pricing']) {
    // Totale imponibile
    echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            '.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, $d_totali).'
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
            <b>'.moneyFormat($sconto, $d_totali).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="4" class="text-right">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th class="text-center">
            <b>'.moneyFormat($totale_imponibile, $d_totali).'</b>
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
            <b>'.moneyFormat($totale_iva, $d_totali).'</b>
        </th>
    </tr>';

    // TOTALE INTERVENTO
    echo '
    <tr>
    	<td colspan="4" class="text-right">
            <b>'.tr('Totale intervento', [], ['upper' => true]).':</b>
    	</td>
    	<th class="text-center">
    		<b>'.moneyFormat($totale, $d_totali).'</b>
    	</th>
    </tr>';
}

echo '
</table>';

if ($options['checklist']) {
    $structure = Module::where('name', 'Interventi')->first();
    $checks = $structure->mainChecks($id_record);

    if (!empty($checks)) {
        echo '
<pagebreak class="page-break-after" />
<table class="table table-bordered vertical-middle">
    <tr>
        <th class="text-center" colspan="2" style="font-size:11pt;">
            <b>CHECKLIST</b>
        </th>
    </tr>';

        $structure = Module::where('name', 'Interventi')->first();
        $checks = $structure->mainChecks($id_record);

        foreach ($checks as $check) {
            echo renderChecklistHtml($check);
        }

        $impianti_collegati = $dbo->fetchArray('SELECT * FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto = my_impianti.id WHERE idintervento = '.prepare($id_record));
        foreach ($impianti_collegati as $impianto) {
            $checks = Check::where('id_module_from', Module::where('name', 'Impianti')->first()->id)->where('id_record_from', $impianto['id'])->where('id_module', Module::where('name', 'Interventi')->first()->id)->where('id_record', $id_record)->where('id_parent', null)->get();

            if (sizeof($checks)) {
                echo '
            <tr>
                <th class="text-center" colspan="2" style="font-size:11pt;">
                    <b>'.tr('Impianto', [], ['upper' => true]).' '.$impianto['matricola'].' - '.$impianto['nome'].'</b>
                </th>
            </tr>';
                foreach ($checks as $check) {
                    echo renderChecklistHtml($check);
                }
            }
        }
        echo '
</table>';
    }
}
