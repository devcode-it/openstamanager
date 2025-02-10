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

$sessioni = $documento->sessioni->sortBy('orario_inizio');

$firma = !empty($documento['firma_file']) ? '<img src="'.base_dir().'/files/interventi/'.$documento['firma_file'].'" style="width:70mm;">' : '';

echo '
<br>

<table class="table border-bottom">
    <tr>
        <th colspan="6" class="text-center bg-gray"><h4>'.tr('Cliente', [], ['upper' => true]).'</h4></th>
    </tr>
    <tr>
        <td width="80">
            <p class="text-muted">'.tr('Cliente', [], ['upper' => true]).':</p>
        </td>
        <td width="150">
            '.$c_ragionesociale.'
        </td>';
// Indirizzo
if (!empty($s_indirizzo) or !empty($s_cap) or !empty($s_citta) or !empty($s_provincia)) {
    echo '

        <td width="110">
            <p class="text-muted">'.tr('Indirizzo', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.$s_indirizzo.' '.$s_cap.' - '.$s_citta.' ('.strtoupper((string) $s_provincia).')
        </td>';
} elseif (!empty($c_indirizzo) or !empty($c_cap) or !empty($c_citta) or !empty($c_provincia)) {
    echo '

        <td>
            <p class="text-muted">'.tr('Indirizzo', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.$c_indirizzo.' '.$c_cap.' - '.$c_citta.' ('.strtoupper((string) $c_provincia).')
        </td>';
}
echo '
    </tr>

    <tr>
        <td>
            <p class="text-muted">'.tr('P.Iva', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.strtoupper((string) $c_piva).'
        </td>
        <td>
            <p class="text-muted">'.tr('C.F.', [], ['upper' => true]).':</p>
        </td>
        <td >
            '.strtoupper((string) $c_codicefiscale).'
        </td>
    </tr>

    <tr>
        <td>
            <p class="text-muted">'.tr('Telefono', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.$c_telefono.'
        </td>
        <td>
            <p class="text-muted">'.tr('Cellulare', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.$c_cellulare.'
        </td>
    </tr>
</table>';

// Dati attività
echo '
<table class="table border-bottom">
    <tr>
        <th colspan="6" class="text-center bg-gray">
            <h4>'.tr('Rapporto attività', [], ['upper' => true]).'</h4>
        </th>
    </tr>

    <tr>
        <td width="60">
            <p class="text-muted">'.tr('Codice', [], ['upper' => true]).':</p>
        </td>
        <td width="170">
            '.$documento['codice'].'
        </td>

        <td width="110">
            <p class="text-muted">'.tr('Data richiesta', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.Translator::dateToLocale($documento['data_richiesta']).'
        </td>

        <td width="100">
            <p class="text-muted">'.tr('Tipologia', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.$documento->tipo->getTranslation('title').'
        </td>
    </tr>';

// Dati preventivo o contratto
if (!empty($preventivo) or !empty($contratto)) {
    echo '
    <tr>
        <td colspan="2"></td>
        <td>
            <p class="text-muted">'.tr('Preventivo n.', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.(!empty($preventivo) ? $preventivo['numero'].' del '.Translator::dateToLocale($preventivo['data_bozza']) : '-').'
        </td>
        <td>
            <p class="text-muted">'.tr('Contratto n.', [], ['upper' => true]).':</p>
        </td>
        <td>
            '.(!empty($contratto) ? $contratto['numero'].' del '.Translator::dateToLocale($contratto['data_bozza']) : '-').'
        </td>
    </tr>';
}

// riga 3
// Elenco impianti su cui è stato fatto l'intervento
$rs2 = $dbo->fetchArray('SELECT *, (SELECT nome FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS nome, (SELECT matricola FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS matricola FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
$impianti = [];
for ($j = 0; $j < count($rs2); ++$j) {
    $impianti[] = ''.$rs2[$j]['nome']." <small style='color:#777;'>(".$rs2[$j]['matricola'].')</small>';
}
echo '
    <tr>
        <td>
            <p class="text-muted">'.tr('Impianti', [], ['upper' => true]).':</p>
        </td>

        <td colspan="5">
            '.implode(', ', $impianti).'
        </td>
    </tr>';

// Richiesta
echo '
    <tr>
        <td colspan="6" style="line-height:1.5em">
            <p class="text-muted">'.tr('Richiesta', [], ['upper' => true]).':</p>
            <p>'.$documento['richiesta'].'</p>
        </td>
    </tr>';

// Descrizione
// Rimosso nl2br, non necessario con ckeditor
echo '
    <tr>
        <td colspan="6" style="line-height:1.5em">
            <p class="text-muted">'.tr('Descrizione', [], ['upper' => true]).':</p>
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
            <th colspan="4" class="text-center bg-gray">
                <h4>'.tr('Materiale utilizzato e spese aggiuntive', [], ['upper' => true]).'</h4>
            </th>
        </tr>

        <tr>
            <td class="text-muted text-left">
                '.tr('Descrizione', [], ['upper' => true]).'
            </td>

            <td style="width:17%" class="text-muted text-right">
                '.tr('Q.tà', [], ['upper' => true]).'
            </td>

            <td style="width:20%" class="text-muted text-right">
                '.tr('Prezzo unitario', [], ['upper' => true]).'
            </td>

            <td style="width:20%" class="text-muted text-right">
                '.tr('Importo', [], ['upper' => true]).'
            </td>
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
                    $text = ''.$key.'<br>';

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
        <td class="text-right">
            '.$qta.' '.$riga->um.'
        </td>';

        // Prezzo unitario
        echo '
        <td class="text-right">
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
        <td class="text-right">
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
        <td colspan="3" class="text-right bg-gray">
            <b>'.tr('Totale', [], ['upper' => true]).'</b>
        </td>

        <th class="text-right">
            '.moneyFormat($righe->sum('importo'), $d_totali).'
        </th>
    </tr>';
    }

    echo '
</table>';
}

// INTESTAZIONE ELENCO TECNICI
echo '
<table class="table border-bottom vertical-middle">
    <thead>
        <tr>
            <th class="text-center bg-gray" colspan="5">
                <h4>'.tr('Ore tecnici', [], ['upper' => true]).'</h4>
            </th>
        </tr>
        <tr>
            <td class="text-center text-muted" style="width:30%">
                '.tr('Tecnico', [], ['upper' => true]).'
            </td>

            <td class="text-center text-muted" colspan="3" style="width:35%">
                '.tr('Orario', [], ['upper' => true]).'
            </td>

            <td class="text-center" style="font-size:6pt;width:35%; border-left:1px solid #aaa;">
                '.tr('I dati del ricevente verrano trattati in base alla normativa europea UE 2016/679 del 27 aprile 2016 (GDPR)').'
            </td>
        </tr>
    </thead>

    <tbody>';

// Sessioni di lavoro dei tecnici
$i = 0;
if (count($sessioni) > 0) {
    foreach ($sessioni as $id => $sessione) {
        echo '
        <tr>';

        // Nome tecnico
        echo '
            <td class="text-center">
                '.$sessione->anagrafica->ragione_sociale.'
                ('.$sessione->tipo->getTranslation('title').')';
        if ($sessione->tipo->note) {
            echo '<br><small class="text-muted">'.$sessione->tipo->note.'</small>';
        }
        echo '
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

        // Testo lavori eseguiti 1/2
        if ($i == 0) {
            echo '
            <td class="text-center" style="font-size:6pt; vertical-align:top; border-left:1px solid #aaa;">
                '.tr('Si dichiara che i lavori sono stati eseguiti ed i materiali installati nel rispetto delle vigenti normative tecniche').'
            </td>';
        }

        // Firma 1/3
        if ($i == 1) {
            echo '
            <td rowspan="'.(count($sessioni) + 1).'" class="text-center" style="font-size:6pt; vertical-align:bottom; border-left:1px solid #aaa;">
                '.$firma.'<br>';

            if (empty($documento['firma_file'])) {
                echo '      <i>('.tr('Timbro e firma leggibile').')</i>';
            } else {
                echo '      <i>'.$documento['firma_nome'].'</i>';
            }

            echo '
            </td>';
        }

        echo '
        </tr>';

        ++$i;
    }
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
            <small class="text-muted">'.tr('Ore lavorate', [], ['upper' => true]).'</small><br/>'.$ore_totali.'
        </td>';

// Costo totale manodopera
if ($options['pricing']) {
    echo '
        <td colspan="3" class="text-center">
            <small class="text-muted">'.tr('Totale manodopera', [], ['upper' => true]).'</small><br/>'.moneyFormat($sessioni->sum('prezzo_manodopera'), $d_totali).'
        </td>';
} else {
    echo '
        <td colspan="3" class="text-center">-</td>';
}

// Testo lavori eseguiti 2/2
if (count($sessioni) == 0) {
    echo '
        <td class="text-center" style="font-size:6pt; vertical-align:top; border-left:1px solid #aaa;">
            '.tr('Si dichiara che i lavori sono stati eseguiti ed i materiali installati nel rispetto delle vigenti normative tecniche').'
        </td>';
}

// Firma 2/3
if (count($sessioni) == 1) {
    echo '<td rowspan="2" class="text-center" style="font-size:6pt; vertical-align:bottom; border-left:1px solid #aaa;">
            '.$firma.'<br>';

    if (empty($documento['firma_file'])) {
        echo '      <br><br><br><i>('.tr('Timbro e firma leggibile').')</i>';
    } else {
        echo '      <i>'.$documento['firma_nome'].'</i>';
    }

    echo '
        </td>';
}

echo '
    </tr>';

// Totale km
echo '
    <tr>
        <td class="text-center">
            <small class="text-muted">'.tr('Km percorsi', [], ['upper' => true]).'</small><br/>'.Translator::numberToLocale($documento->km_totali, $d_qta).'
        </td>';

// Costo trasferta
if ($options['pricing']) {
    echo '
        <td class="text-center">
            <small class="text-muted">'.tr('Costi di trasferta', [], ['upper' => true]).'</small><br/>'.moneyFormat($sessioni->sum('prezzo_viaggio'), $d_totali).'
        </td>';
} else {
    echo '
        <td class="text-center">-</td>';
}

// Diritto di chiamata
if ($options['pricing']) {
    echo '
        <td class="text-center" colspan="2" width="120px" >
            <small class="text-muted">'.tr('Diritto di chiamata', [], ['upper' => true]).'</small><br/>'.moneyFormat($sessioni->sum('prezzo_diritto_chiamata'), $d_totali).'
        </td>';
} else {
    echo '
        <td class="text-center" colspan="2">-</td>';
}

// Firma 3/3
if (count($sessioni) == 0) {
    echo '<td class="text-center" style="font-size:6pt; vertical-align:bottom; border-left:1px solid #aaa;">
            '.$firma.'<br>';

    if (empty($documento['firma_file'])) {
        echo '      <br><br><br><i>('.tr('Timbro e firma leggibile').')</i>';
    } else {
        echo '      <i>'.$documento['firma_nome'].'</i>';
    }

    echo '
        </td>';
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
        <td colspan="4" class="text-right bg-gray">
            '.tr('Imponibile', [], ['upper' => true]).'
        </td>

        <td class="text-right">
            '.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, $d_totali).'
        </td>
    </tr>';

    // Eventuale sconto totale
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="4" class="text-right bg-gray">
        '.tr('Sconto', [], ['upper' => true]).'
        </td>

        <td class="text-right">
            '.moneyFormat($sconto, $d_totali).'
        </td>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="4" class="text-right bg-gray">
            '.tr('Totale imponibile', [], ['upper' => true]).'
        </td>

        <td class="text-right">
            '.moneyFormat($totale_imponibile, $d_totali).'
        </td>
    </tr>';
    }

    // IVA
    // Totale intervento
    echo '
    <tr>
        <td colspan="4" class="text-right bg-gray">
            '.tr('Iva', [], ['upper' => true]).'
        </td>

        <td class="text-right">
            '.moneyFormat($totale_iva, $d_totali).'
        </td>
    </tr>';

    // TOTALE INTERVENTO
    echo '
    <tr>
    	<td colspan="4" class="text-right bg-gray">
            <b>'.tr('Totale', [], ['upper' => true]).'</b>
    	</td>
    	<td class="text-right">
    		<b>'.moneyFormat($totale, $d_totali).'</b>
    	</td>
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
            CHECKLIST
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
                    '.tr('Impianto', [], ['upper' => true]).' '.$impianto['matricola'].' - '.$impianto['nome'].'
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
