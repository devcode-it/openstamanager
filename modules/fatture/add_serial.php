<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

$module = Modules::get($id_module);

// Controllo sulla direzione monetaria
$uscite = [
    'Fatture di acquisto',
    'Ddt di acquisto',
    'Ordini fornitore',
];

if (in_array($module['name'], $uscite)) {
    $dir = 'uscita';
} else {
    $dir = 'entrata';
}

$data = [
    'fat' => [
        'table' => 'co_righe_documenti',
        'id' => 'iddocumento',
    ],
    'ddt' => [
        'table' => 'dt_righe_ddt',
        'id' => 'idddt',
    ],
    'ord' => [
        'table' => 'or_righe_ordini',
        'id' => 'idordine',
    ],
    'int' => [
        'table' => 'in_righe_interventi',
        'id' => 'idintervento',
    ],
];

// Individuazione delle tabelle interessate
if (in_array($module['name'], ['Fatture di vendita', 'Fatture di acquisto'])) {
    $modulo = 'fat';
} elseif (in_array($module['name'], ['Ddt di vendita', 'Ddt di acquisto'])) {
    $modulo = 'ddt';
} elseif (in_array($module['name'], ['Ordini cliente', 'Ordini fornitore'])) {
    $modulo = 'ord';
} else {
    $modulo = 'int';
}

$table = $data[$modulo]['table'];
$id = $data[$modulo]['id'];
$riga = str_replace('id', 'id_riga_', $id);

$idriga = get('idriga') ?: get('riga_id');

$rs = $dbo->fetchArray('SELECT mg_articoli.id AS idarticolo, mg_articoli.codice, mg_articoli.descrizione, '.$table.'.qta FROM '.$table.' INNER JOIN mg_articoli ON '.$table.'.idarticolo=mg_articoli.id WHERE '.$table.'.'.$id.'='.prepare($id_record).' AND '.$table.'.id='.prepare($idriga));

echo '
<p>'.tr('Articolo').': '.$rs[0]['codice'].' - '.$rs[0]['descrizione'].'</p>

<form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="add_serial">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="idriga" value="'.$idriga.'">
    <input type="hidden" name="idarticolo" value="'.$rs[0]['idarticolo'].'">
    <input type="hidden" name="dir" value="'.$dir.'">';

$info = $dbo->fetchArray('SELECT * FROM mg_prodotti WHERE serial IS NOT NULL AND '.$riga.'='.prepare($idriga));
$serials = array_column($info, 'serial');

if ($dir == 'entrata') {
    $in = [];
    foreach ($serials as $value) {
        $in[] = prepare($value);
    }
    $in = implode(',', $in);

    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Serial').'", "name": "serial[]", "multiple": 1, "value": "'.implode(',', $serials).'", "values": "query=SELECT DISTINCT serial AS id, serial AS descrizione FROM mg_prodotti WHERE dir=\'uscita\' AND mg_prodotti.id_articolo = '.prepare($rs[0]['idarticolo']).' AND serial NOT IN (SELECT serial FROM mg_prodotti WHERE dir=\'entrata\' AND serial NOT IN (SELECT serial FROM mg_prodotti WHERE '.$riga.' = \''.$idriga.'\'))'.(!empty($in) ? ' OR serial IN ('.$in.')' : '').'", "extra": "data-maximum=\"'.intval($rs[0]['qta']).'\"" ]}
        </div>
    </div>';
} else {
    echo '
    <p>'.tr('Inserisci i numeri seriali degli articoli aggiunti:').'</p>';
    for ($i = 0; $i < $rs[0]['qta']; ++$i) {
        if ($i % 3 == 0) {
            echo '
    <div class="row">';
        }

        $res = [];
        if (!empty($serials[$i])) {
            $res = $dbo->fetchArray("SELECT * FROM mg_prodotti WHERE dir='entrata' AND serial = ".prepare($serials[$i]));
        }

        echo '
        <div class="col-md-4">
            {[ "type": "text", "name": "serial[]", "value": "'.$serials[$i].'"'.(!empty($res) ? ', "readonly": 1' : '').' ]}';

        if (!empty($res)) {
            if (!empty($res[0]['id_riga_intervento'])) {
                $modulo = 'Interventi';
                $pos = 'int';
            } elseif (!empty($res[0]['id_riga_ddt'])) {
                $modulo = 'Ddt di vendita';
                $pos = 'ddt';
            } elseif (!empty($res[0]['id_riga_documento'])) {
                $modulo = 'Fatture di vendita';
                $pos = 'fat';
            } elseif (!empty($res[0]['id_riga_ordine'])) {
                $modulo = 'Ordini cliente';
                $pos = 'ord';
            }

            $r = $dbo->select($data[$pos]['table'], $data[$pos]['id'], ['id' => $res[0][str_replace('id', 'id_riga_', $data[$pos]['id'])]]);

            echo '
        '.Modules::link($modulo, $r[0][$data[$pos]['id']], tr('Visualizza vendita'), null);
        }
        echo '
        </div>';

        if (($i + 1) % 3 == 0) {
            echo '
    </div>
    <br>';
        }
    }
    if ($i % 3 != 0) {
        echo '
    </div>';
    }
}

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-barcode"></i> '.tr('Aggiorna').'</button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';
