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

use Models\Module;

include_once __DIR__.'/../../core.php';

$enable_readonly = !setting('Modifica Viste di default');

echo '
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Opzioni generali').'</h3>
		</div>

		<div class="panel-body">';
$options = ($record['options2'] == '') ? $record['options'] : $record['options2'];
if ($options == 'menu') {
    echo '
			<p><strong>'.tr('Il modulo che stai analizzando è un semplice menu').'.</strong></p>';
} elseif ($options == 'custom') {
    echo '
			<p><strong>'.tr("Il modulo che stai analizzando possiede una struttura complessa, che prevede l'utilizzo di file personalizzati per la gestione delle viste").'.</strong></p>';
}

echo '
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Codice del modulo').'", "name": "name", "value": "'.$record['name'].'", "readonly": "1" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Nome del modulo').'", "name": "title", "value": "'.$record['title'].'", "help": "'.tr('Il nome che identifica il modulo').'" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query di default').'", "name": "options", "value": '.json_encode(str_replace(']}', '] }', $record['options'])).', "readonly": "1", "class": "autosize" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query personalizzata').'", "name": "options2", "value": '.json_encode(str_replace(']}', '] }', $record['options2'])).', "class": "autosize", "help": "'.tr('La query in sostituzione a quella di default: custom, menu oppure SQL').'" ]}
				</div>
            </div>';

if ($options != '' && $options != 'menu' && $options != 'custom') {
    $module_query = Util\Query::getQuery(Module::find($id_record));

    $beautiful_query = nl2br(htmlentities($module_query));
    $beautiful_query = str_replace('   ', '&nbsp;&nbsp;&nbsp;&nbsp;', $beautiful_query);

    echo '
			<div class="row">
				<div class="col-md-12">
					<p><strong>'.tr('Query risultante').':</strong></p>
                    <div class="well">'.$beautiful_query.'</div>

                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-warning" onclick="testQuery()">
                                <i class="fa fa-file-text-o"></i> '.tr('Testa la query').'
                            </button>

                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-check"></i> '.tr('Salva').'
                            </button>
                        </div>
                    </div>
				</div>
			</div>';
}

echo '
		</div>
	</div>
</form>';

if (!empty($options) && $options != 'custom' && $options != 'menu') {
    echo '
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs nav-justified">
        <li class="active"><a data-toggle="tab" href="#fields">'.tr('Campi').' <span class="badge">'.($dbo->fetchNum('SELECT * FROM zz_views WHERE id_module='.prepare($record['id']).' ORDER BY `order` ASC')).'</a></li>
        <li><a data-toggle="tab" href="#filters">'.tr('Filtri').' <span class="badge">'.($dbo->fetchNum('SELECT * FROM zz_group_module WHERE idmodule='.prepare($record['id']).' ORDER BY `id` ASC')).'</span></a></li>
    </ul>

    <div class="tab-content">

        <!-- CAMPI -->
        <div id="fields" class="tab-pane fade in active">';

    include $module->filepath('fields.php');

    echo '
        </div>

        <!-- FILTRI -->
        <div id="filters" class="tab-pane fade">';

    include $module->filepath('filters.php');

    echo '
        </div>

    </div>
</div>';

    echo '
<script>
function testQuery(){
    $("#main_loading").fadeIn();

    $.ajax({
        url: "'.ROOTDIR.'/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=test",
        cache: false,
        type: "post",
        processData: false,
        contentType: false,
        dataType : "html",
        success: function(data) {
            $("#main_loading").fadeOut();

            swal("'.tr('Query funzionante').'", "'.tr('La query attuale funziona correttamente!').'", "success");
        },
        error: function(data) {
            $("#main_loading").fadeOut();

            swal("'.tr('Errore').'", "'.tr('Errore durante il test della query!').'", "error");
        }
    })
}
</script>';
}
