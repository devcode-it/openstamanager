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

use Models\PrintTemplate;
use Models\Module;

$id_files = $dbo->select('zz_files_print', 'id_file', [], ['id_print' => $id_record]);

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI SEGMENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Informazioni della stampa'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Titolo'); ?>", "name": "title", "required": 1, "value": "$title$" ]}
				</div>

				<div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome del file'); ?>", "name": "filename", "required": 1, "value": "$filename$" ]}
				</div>

			</div>

            <div class="row">

                <div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module", "required": 1, "values": "query=SELECT `zz_modules`.`id`, `name` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = <?php echo prepare(\Models\Locale::getDefault()->id); ?>) WHERE (`enabled` = 1 AND `options` != 'custom') OR `zz_modules`.`id` = <?php echo $record['id_module']; ?> ORDER BY `name` ASC", "value": "<?php echo $record['id_module']; ?>", "disabled": "1" ]}
				</div>

                <div class="col-md-6">
					{[ "type": "select", "multiple": "1", "label": "<?php echo tr('File da accodare'); ?>", "name": "id_files[]", "value": "<?php echo implode(',', array_column($id_files, 'id_file')); ?>", "ajax-source": "allegati", "select-options": <?php echo json_encode(['id_module' => $id_module, 'id_record' => $id_record]); ?>, "link": "allegato" ]}
				</div>

            </div>

            <div class="row">

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "<?php echo tr('Attiva'); ?>", "name": "enabled", "value": "$enabled$", "disabled": "1" ]}
                </div>

                <div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Ordine'); ?>", "name": "order", "required": 0, "value": "$order$", "decimals":0 ]}
				</div>

                <?php
                $stampa_predefinita = PrintTemplate::where('predefined', true)
                    ->where('id_module', $record['id_module'])
                    ->orderBy('id')
                    ->first();
if (!empty($stampa_predefinita)) {
    $nome_stampa_predefinita = $stampa_predefinita->getTranslation('name');
} else {
    $nome_stampa_predefinita = 'Nessuna';
}
?>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "<?php echo tr('Predefinita'); ?>", "help" : "<?php echo tr('Attiva per impostare questa stampa come predefinita. Attualmente la stampa predefinita per questo modulo è: '.$nome_stampa_predefinita); ?>", "name": "predefined", "value": "$predefined$", "disabled": "<?php echo intval($record['predefined']); ?>" ]}
                </div>

            </div>

			<div class="row">

				<div class="col-md-12">
                    <?php
    echo input([
        'type' => 'textarea',
        'label' => tr('Opzioni'),
        'name' => 'options',
        'value' => $record['options'],
        'help' => tr('Impostazioni personalizzabili della stampa, in formato JSON'),
    ]);
?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php

// Opzioni utilizzabili
// Lettura delle opzioni per il template di stampa

echo '
<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title">'.tr('Opzioni').'</h3>
    </div>

    <div class="box-body">';

if (!empty($record['available_options'])) {
    $available_options = json_decode($record['available_options']);
    echo '
        <p>'.tr('Puoi utilizzare le seguenti opzioni per generare la stampa').':</p>
        <ul>';

    foreach ($available_options as $option => $value) {
        echo '
            <li><code>'.$option.'</code>'.((!empty($value)) ? ' <span class="label label-default" >'.$value.'</span>' : '').'</li>';
    }

    echo '
        </ul>';
} else {
    echo '
        <p><i class="fa fa-warning"></i> '.tr('Non sono state definite opzioni da utilizzare per la stampa').'.</p>';
}

echo '
    </div>
</div>';

// Variabili utilizzabili
$module = Module::find($record['id_module']);
$variables = $module->getPlaceholders($id_record);

echo '
<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title">'.tr('Variabili').'</h3>
    </div>

    <div class="box-body">';

if (!empty($variables)) {
    echo '
        <p>'.tr('Puoi utilizzare le seguenti variabili per generare il nome del file').':</p>
        <ul>';

    foreach ($variables as $variable => $value) {
        echo '
            <li><code>'.$variable.'</code></li>';
    }

    echo '
        </ul>';
} else {
    echo '
        <p><i class="fa fa-warning"></i> '.tr('Non sono state definite variabili da utilizzare nel template').'.</p>';
}

echo '
    </div>
</div>

<hr>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}';
?>
