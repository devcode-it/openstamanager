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
 * along with this program. If you see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Models\Module;

$dbo = database();

$type = post('type');
$id_record = (int) post('id_record');
$id_documento = (int) post('id_documento');
$start = post('start');
$end = post('end');

$tables = [
    'preventivi' => 'co_righe_preventivi',
    'contratti' => 'co_righe_contratti',
    'ordini_cliente' => 'or_righe_ordini',
    'interventi' => 'in_righe_interventi',
    'ddt' => 'dt_righe_ddt',
    'fatture' => 'co_righe_documenti',
];

$key_columns = [
    'preventivi' => 'id_preventivo',
    'contratti' => 'id_contratto',
    'ordini_cliente' => 'id_ordine',
    'interventi' => 'id_intervento',
    'ddt' => 'id_ddt',
    'fatture' => 'id_documento',
];

$source_tables = [
    'fattura' => 'co_righe_documenti',
    'ddt' => 'dt_righe_ddt',
];

$source_keys = [
    'fattura' => 'id_documento',
    'ddt' => 'id_ddt',
];

if (in_array($type, ['articoli_venduti', 'articoli_acquistati'])) {
    $id_articolo = (int) post('id_documento');
    $dir = $type == 'articoli_venduti' ? 'uscita' : 'entrata';

    if (empty($start) || empty($end)) {
        echo '<div class="alert alert-danger">'.tr('Periodo non valido').'</div>';
        return;
    }

    $righe = $dbo->fetchArray('
        SELECT d.id AS documento_id, d.numero, d.data, td.name AS tipo_documento,
            \'fattura\' AS source, rd.descrizione, rd.qta, rd.subtotale
        FROM co_righe_documenti rd
        INNER JOIN co_documenti d ON d.id = rd.id_documento
        INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
        WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = '.prepare($dir).' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).' AND rd.id_articolo = '.prepare($id_articolo).'
        UNION ALL
        SELECT dd.id AS documento_id, dd.numero, dd.data, tdd.name AS tipo_documento,
            \'ddt\' AS source, rdd.descrizione, rdd.qta, rdd.subtotale
        FROM dt_righe_ddt rdd
        INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
        INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
        WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = '.prepare($dir).' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end).' AND rdd.id_articolo = '.prepare($id_articolo).'
        ORDER BY data ASC');

    if (empty($righe)) {
        echo '
    <table class="table table-sm table-bordered" style="margin-bottom: 0;">
        <thead>
            <tr>
                <th class="text-left">'.tr('Documento').'</th>
                <th class="text-left">'.tr('Articolo').'</th>
                <th class="text-right" width="15%">'.tr('Qta').'</th>
                <th class="text-right" width="15%">'.tr('Imponibile').'</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" class="text-center text-muted">'.tr('Nessuna riga presente').'</td>
            </tr>
        </tbody>
    </table>';
        return;
    }

    $link_module_fatture = Module::where('name', 'Fatture di vendita')->first()->id;
    $link_module_ddt = Module::where('name', 'Ddt in uscita')->first()->id;

    echo '
    <table class="table table-sm table-bordered" style="margin-bottom: 0;">
        <thead>
            <tr>
                <th class="text-left">'.tr('Documento').'</th>
                <th class="text-left">'.tr('Articolo').'</th>
                <th class="text-right" width="15%">'.tr('Qta').'</th>
                <th class="text-right" width="15%">'.tr('Imponibile').'</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($righe as $riga) {
        $module_id = $riga['source'] == 'ddt' ? $link_module_ddt : $link_module_fatture;
        $link = base_path_osm().'/editor.php?id_module='.$module_id.'&id_record='.$riga['documento_id'];
        $numero = !empty($riga['numero']) ? $riga['numero'] : '#'.$riga['documento_id'].' - '.$riga['tipo_documento'];

        echo '
            <tr>
                <td class="text-left"><a href="'.$link.'" target="_blank">'.$numero.'</a></td>
                <td class="text-left">'.nl2br($riga['descrizione']).'</td>
                <td class="text-right">'.numberFormat($riga['qta'], 2).'</td>
                <td class="text-right">'.moneyFormat($riga['subtotale']).'</td>
            </tr>';
    }

    echo '
        </tbody>
    </table>';
    return;
}

$table = $tables[$type] ?? '';
$key = $key_columns[$type] ?? '';

if (empty($table) || empty($key) || empty($id_documento)) {
    return;
}

if ($type == 'interventi') {
    $select = 'descrizione, qta, (prezzo_unitario * qta) AS subtotale';
} else {
    $select = 'descrizione, qta, subtotale';
}

$righe = $dbo->fetchArray('SELECT '.$select.' FROM '.$table.' WHERE '.$key.' = ? ORDER BY id ASC', [$id_documento]);

echo '
<table class="table table-sm table-bordered" style="margin-bottom: 0;">
    <thead>
        <tr>
            <th class="text-left">'.tr('Descrizione').'</th>
            <th class="text-right" width="15%">'.tr('Qta').'</th>
            <th class="text-right" width="15%">'.tr('Imponibile').'</th>
        </tr>
    </thead>
    <tbody>';

if (empty($righe)) {
    echo '
        <tr>
            <td colspan="3" class="text-center text-muted">'.tr('Nessuna riga presente').'</td>
        </tr>';
} else {
    foreach ($righe as $riga) {
        echo '
        <tr>
            <td class="text-left">'.nl2br($riga['descrizione']).'</td>
            <td class="text-right">'.numberFormat($riga['qta'], 2).'</td>
            <td class="text-right">'.moneyFormat($riga['subtotale']).'</td>
        </tr>';
    }
}

echo '
    </tbody>
</table>';
