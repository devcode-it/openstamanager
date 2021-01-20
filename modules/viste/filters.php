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

echo '
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="filters">

    <div class="data">';

$num = 0;
$additionals = $dbo->fetchArray('SELECT * FROM zz_group_module WHERE idmodule='.prepare($record['id']).' ORDER BY `id` ASC');

if (!empty($additionals)) {
    foreach ($additionals as $num => $additional) {
        $editable = !($additional['default'] && $enable_readonly);

        echo '
        <div class="box collapsed-box box-'.($additional['enabled'] ? 'success' : 'danger').'">
            <div class="box-header with-border">
                <h3 class="box-title">'.
                    tr('Filtro: _NAME_', [
'_NAME_' => $additional['name'],
]).'
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-plus"></i>
                    </button>
                </div>';

        if ($editable) {
            echo '
                <a class="btn btn-danger ask pull-right" data-backto="record-edit" data-op="delete_filter" data-id="'.$additional['id'].'">
                    <i class="fa fa-trash"></i> '.tr('Elimina').'
                </a>';
        }

        echo '
                <a class="btn btn-warning ask pull-right" data-backto="record-edit" data-msg="'.($additional['enabled'] ? tr('Disabilitare questo elemento?') : tr('Abilitare questo elemento?')).'" data-op="change" data-id="'.$additional['id'].'" data-class="btn btn-lg btn-warning" data-button="'.($additional['enabled'] ? tr('Disabilita') : tr('Abilita')).'">
                    <i class="fa fa-eye-slash"></i> '.($additional['enabled'] ? tr('Disabilita') : tr('Abilita')).'
                </a>';
        echo '
            </div>
            <div id="additional-'.$additional['id'].'" class="box-body collapse">

                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "textarea", "label": "'.tr('Query').'", "name": "query['.$num.']", "value": "'.prepareToField($additional['clause']).'"';
        if (!$editable) {
            echo ', "readonly": '.intval(!$editable).'';
        }
        echo ' ]}
                    </div>
                </div>

                <div class="row">
                    <input type="hidden" value="'.$additional['id'].'" name="id['.$num.']">

                    <div class="col-md-6">
                        {[ "type": "text", "label": "'.tr('Name').'", "name": "name['.$num.']", "value": "'.$additional['name'].'"  ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "select", "label": "'.tr('Gruppo').'", "name": "gruppo['.$num.']", "values": "query=SELECT id, nome AS descrizione FROM zz_groups ORDER BY id ASC", "value": "'.$additional['idgruppo'].'", "readonly": '.intval(!$editable).' ]}
                    </div>

                    <div class="col-md-3">
                        {[ "type": "select", "label": "'.tr('Posizione').'", "name": "position['.$num.']", "values": "list=\"0\":\"'.tr('WHERE').'\",\"1\": \"'.tr('HAVING').'\"", "value": "'.$additional['position'].'", "readonly": '.intval(!$editable).' ]}
                    </div>
                </div>

            </div>
        </div>';
    }
} else {
    echo '<br>
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <b>'.tr('Informazione:').'</b> '.tr('Nessun filtro per questo modulo').'.
    </div>';
}

echo '
    </div>

    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-info" id="add_filter">
                <i class="fa fa-plus"></i> '.tr('Aggiungi nuovo filtro').'
            </button>

            <button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>';

echo '
<form class="hide" id="template_filter">
	<div class="box">
		<div class="box-header with-border">
			<h3 class="box-title">'.tr('Nuovo filtro').'</h3>
		</div>
		<div class="box-body">

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "'.tr('Query').'", "name": "query[-id-]" ]}
				</div>
			</div>

			<div class="row">
				<input type="hidden" value="" name="id[-id-]">

				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Nome').'", "name": "name[-id-]" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Gruppo').'", "name": "gruppo[-id-]", "values": "query=SELECT id, nome AS descrizione FROM zz_groups ORDER BY id ASC" ]}
				</div>

                <div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Posizione').'", "name": "position[-id-]", "values": "list=\"0\":\"'.tr('WHERE').'\",\"1\": \"'.tr('HAVING').'\"" ]}
				</div>
            </div>
        </div>
    </div>
</form>';

echo '
<script>
    var i = '.$num.';
	$(document).on("click", "#add_filter", function() {
	    cleanup_inputs();

		i++;
		var text = replaceAll($("#template_filter").html(), "-id-", "" + i);
		$(this).parent().parent().parent().find(".data").append(text);

		restart_inputs();
	});
</script>';
