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

use Models\Module;
use Modules\DDT\DDT;

$module = Module::find($id_module);
$id_module_articoli = (new Module())->getByField('name', 'Articoli');

// Controllo sulla direzione monetaria
$uscite = [
    'Fatture di acquisto',
    'Ddt di acquisto',
    'Ordini fornitore',
];

if (in_array($module->getTranslation('name'), $uscite)) {
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
    'veb' => [
        'table' => 'vb_righe_venditabanco',
        'id' => 'idvendita',
    ],
    'con' => [
        'table' => 'co_righe_contratti',
        'id' => 'idcontratto',
    ],
];

// Individuazione delle tabelle interessate
if (in_array($module->getTranslation('name'), ['Fatture di vendita', 'Fatture di acquisto'])) {
    $modulo = 'fat';
} elseif (in_array($module->getTranslation('name'), ['Ddt di vendita', 'Ddt di acquisto'])) {
    $modulo = 'ddt';
    $ddt = DDT::find($id_record);
    $is_rientrabile = $database->fetchOne('SELECT * FROM `dt_causalet` WHERE `id` = '.prepare($ddt->idcausalet))['is_rientrabile'];
} elseif (in_array($module->getTranslation('name'), ['Ordini cliente', 'Ordini fornitore'])) {
    $modulo = 'ord';
} elseif ($module->getTranslation('name') == 'Interventi') {
    $modulo = 'int';
} elseif ($module->getTranslation('name') == 'Contratti') {
    $modulo = 'con';
} else {
    $modulo = 'veb';
}

$table = $data[$modulo]['table'];
$id = $data[$modulo]['id'];
$riga = str_replace('id', 'id_riga_', $id);

$idriga = get('idriga') ?: get('riga_id');

$rs = $dbo->fetchArray('SELECT `mg_articoli`.`id` AS idarticolo, `mg_articoli`.`codice`, `mg_articoli_lang`.`name`, '.$table.'.`qta` FROM '.$table.' INNER JOIN `mg_articoli` ON '.$table.'.`idarticolo`=`mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id`=`mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE '.$table.'.'.$id.'='.prepare($id_record).' AND '.$table.'.`id`='.prepare($idriga));

echo '
<h4 class="text-center">'.tr('Articolo').': '.$rs[0]['codice'].' - '.$rs[0]['descrizione'].'</h4>

<form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post" id="serial-form">
    <input type="hidden" name="op" value="add_serial">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="idriga" value="'.$idriga.'">
    <input type="hidden" name="idarticolo" value="'.$rs[0]['idarticolo'].'">
    <input type="hidden" name="dir" value="'.$dir.'">';

$info = $dbo->fetchArray('SELECT * FROM mg_prodotti WHERE serial IS NOT NULL AND '.$riga.'='.prepare($idriga));
$serials = $info ? array_column($info, 'serial') : [];

if ($dir == 'entrata') {
    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Serial').'", "name": "serial[]", "multiple": 1, "value": "'.implode(',', $serials).'", "ajax-source": "serial-articolo", "select-options": '.json_encode(['idarticolo' => $rs[0]['idarticolo']]).', "extra": "data-maximum=\"'.intval($rs[0]['qta']).'\"" ]}
        </div>
    </div>';
} else {
    echo '
    <div class="row">
        <div class="col-md-5">
            {[ "type": "text", "label": "'.tr('Inizio').'", "name": "serial_start" ]}
        </div>

        <div class="col-md-2 text-center" style="padding-top: 20px;">
            <i class="fa fa-arrow-circle-right fa-2x"></i>
        </div>

        <div class="col-md-5">
            {[ "type": "text", "label": "'.tr('Fine').'", "name": "serial_end" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="button" class="btn btn-info" onclick="generaSerial();"><i class="fa fa-magic"></i> '.tr('Genera').'</button>
        </div>
    </div>
    <hr>
    <h5>'.tr('Inserisci i numeri seriali degli articoli aggiunti:').'</h5>';
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
            {[ "type": "text", "name": "serial['.$i.']", "class": "serial", "id": "serial_'.$i.'", "value": "'.$serials[$i].'"'.(!empty($res) ? ', "readonly": 1' : '').' ]}';

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
            } elseif (!empty($res[0]['id_riga_contratto'])) {
                $modulo = 'Contratti';
                $pos = 'con';
            } elseif (!empty($res[0]['id_riga_venditabanco'])) {
                $modulo = 'Vendita al banco';
                $pos = 'veb';
            }

            $r = $dbo->select($data[$pos]['table'], $data[$pos]['id'], [], ['id' => $res[0][str_replace('id', 'id_riga_', $data[$pos]['id'])]]);

            echo '
        '.Modules::link($modulo, $r[0][$data[$pos]['id']], tr('Visualizza vendita'), null);
        }
        echo '
        </div>';

        if ($is_rientrabile) {
            echo '
            <div class="col-md-6">
                {[ "type": "select", "name": "select_serial_'.$i.'", "value": "'.implode(',', $serials).'", "values": "query=SELECT serial AS id, serial AS descrizione FROM mg_prodotti WHERE id_articolo = '.prepare($rs[0]['idarticolo']).' AND mg_prodotti.dir=\'entrata\' AND id=(SELECT MAX(id) FROM mg_prodotti AS prodotti WHERE prodotti.id_articolo=mg_prodotti.id_articolo AND prodotti.serial=mg_prodotti.serial)", "onchange": "aggiornaSerial('.$i.');"'.(!empty($res) ? ', "readonly": 1' : '').' ]}
            </div>';
        }

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

    $module_fatture = (new Module())->getByField('name', 'Fatture di acquisto');

    echo '
    <br>
    <div class="alert alert-warning text-center has_serial hidden">
        <i class="fa fa-warning"></i>
        <b>'.tr('Attenzione!').'</b> '.tr('Il Serial su questo articolo è già stato utilizzato in un altro documento di acquisto').'.
    </div>

    <script>
        $(".serial").on("keyup change", function() {
            controllaSerial($(this).val());
        });

        function controllaSerial(value) {
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "post",
                data: {
                    id_module: "'.$module_fatture.'",
                    id_record: globals.id_record,
                    serial: value,
                    is_rientrabile: "'.$is_rientrabile.'",
                    id_articolo: input("idarticolo").get(),
                    op: "controlla_serial"
                },
                success: function(data){
                    data = JSON.parse(data);
                    if (data) {
                        $(".has_serial").removeClass("hidden");
                        $("#aggiorna").addClass("disabled");
                    } else {
                        $(".has_serial").addClass("hidden");
                        $("#aggiorna").removeClass("disabled");
                    }
                }
            });
        }

        function aggiornaSerial(i) {
            let select_serial = $("#select_serial_"+i);
            if (select_serial.val()) {
                $("#serial_"+i).val(select_serial.val());
                controllaSerial(select_serial.val());
                select_serial.selectClear();
            }
        }

        function generaSerial() {
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: "'.$id_module_articoli.'",
                    id_record: "'.$rs[0]['idarticolo'].'",
                    serial_start: input("serial_start").get(),
                    serial_end: input("serial_end").get(),
                    check: 1,
                    op: "generate_serials"
                },
                success: function (response) {
                    let i = 0;
                    $(".serial").each(function() {
                        $(this).val(response[i]);
                        controllaSerial(response[i]);
                        i++;
                    });
                }
            });
        }
    </script>';
}

echo '

    <!-- PULSANTI -->
	<div class="row">
        <div class="col-md-2">
            <button type="button" class="btn btn-info '.($dir == 'uscita' ? 'hidden' : '').'" data-toggle="modal" data-title="'.tr('Aggiungi serial').'" data-href="'.base_path().'/modules/articoli/plugins/articoli.lotti.php?id_module='.$id_module_articoli.'&id_record='.$rs[0]['idarticolo'].'&modal=1"><i class="fa fa-magic"></i> '.tr('Crea').'</button>
        </div>

		<div class="col-md-10 text-right">
			<button type="button" id="aggiorna" class="btn btn-primary pull-right"><i class="fa fa-barcode"></i> '.tr('Aggiorna').'</button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';

echo '
<script>
    $("#aggiorna").on("click", function() {
        var form = input("#serial-form");
        salvaForm("#serial-form", {
            id_module: "'.$id_module.'",
            id_record: "'.$id_record.'",
        }).then(function(response) {
            form.getElement().closest("div[id^=bs-popup").modal("hide");
            caricaRighe(null);
        });

        return false;
    });
</script>';
