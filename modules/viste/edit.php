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

use Models\Clause;
use Models\Module;
use Models\View;
use Doctrine\SqlFormatter\SqlFormatter;

include_once __DIR__.'/../../core.php';

$enable_readonly = !setting('Modifica Viste di default');

echo '
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title">'.tr('Opzioni generali').'</h3>
		</div>

		<div class="card-body">';
$options = ($record->options2 == '') ? $record->options : $record->options2;
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
					{[ "type": "text", "label": "'.tr('Codice del modulo').'", "name": "name", "value": "'.$record->getTranslation('title').'", "readonly": "1" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Nome del modulo').'", "name": "title", "value": "'.$record->getTranslation('title').'", "help": "'.tr('Il nome che identifica il modulo').'" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query originale').'", "name": "options", "value": '.json_encode(str_replace(']}', '] }', $record->options)).', "readonly": "1", "class": "autosize" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query personalizzata').'", "name": "options2", "value": '.json_encode(str_replace(']}', '] }', $record->options2 ?: '')).', "class": "autosize", "help": "'.tr('La query in sostituzione a quella di default: custom, menu oppure SQL').'" ]}
				</div>
            </div>';

if ($options != '' && $options != 'menu' && $options != 'custom') {
    $module_query = Util\Query::getQuery(Module::find($id_record));

    // Utilizzo di SqlFormatter per formattare e colorare la query
    $sqlFormatter = new SqlFormatter();
    $beautiful_query = $sqlFormatter->highlight($module_query);

    // Salva la query originale (senza formattazione HTML) per il copia-incolla
    $original_query = (string) $module_query;

    echo '
			<div class="row">
				<div class="col-md-12">
					<p><strong>'.tr('Query risultante').':</strong>
                        <button type="button" class="btn btn-sm btn-info" id="copy-query-btn" title="'.tr('Copia query').'">
                            <i class="fa fa-copy"></i>
                        </button>
                    </p>
                    <div class="sql-formatted well">'.$beautiful_query.'</div>
                    <textarea id="query-to-copy" style="position: absolute; left: -9999px;">'.$original_query.'</textarea>

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
        <li class="active nav-item clicked"><a class="nav-link" data-card-widget="tab" href="#fields">'.tr('Campi').' <span class="badge badge-info">'.View::where('id_module', $record->id)->count().'</a></li>
        <li class="nav-item"><a class="nav-link" data-card-widget="tab" href="#filters">'.tr('Filtri').' <span class="badge badge-info">'.Clause::where('idmodule', $record->id)->count().' </span></a></li>
    </ul>
    <br>

    <div class="tab-content">

        <!-- CAMPI -->
        <div id="fields" class="tab-pane active">';

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
}

// Traduzioni per JavaScript
echo '<script>
    if (typeof globals.translations === "undefined") {
        globals.translations = {};
    }
    globals.translations.copied = "'.tr('Copiato!').'";
    globals.translations.query_copied = "'.tr('La query è stata copiata negli appunti').'";
    globals.translations.working_query = "'.tr('Query funzionante').'";
    globals.translations.query_works_correctly = "'.tr('La query attuale funziona correttamente!').'";
    globals.translations.error = "'.tr('Errore').'";
    globals.translations.select_all = "'.tr('Seleziona tutti').'";
</script>
<link rel="stylesheet" href="'.base_path().'/modules/viste/css/main.css">
<script src="'.base_path().'/modules/viste/js/main.js"></script>
';