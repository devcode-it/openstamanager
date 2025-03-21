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
use Models\Hook;

echo '
<table class="table table-hover table-sm">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th class="text-center">'.tr('Ultima esecuzione').'</th>
            <th class="text-center">'.tr('Stato').'</th>
        </tr>
    </thead>';

$hooks = $dbo->fetchArray('SELECT 
    `zz_hooks`.*, 
    `zz_modules_lang`.`title` AS modulo
    FROM `zz_hooks`
        LEFT JOIN `zz_hooks_lang` ON (`zz_hooks`.`id` = `zz_hooks_lang`.`id_record` AND `zz_hooks_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `zz_modules` ON `zz_hooks`.`id_module` = `zz_modules`.`id`
        LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    ORDER BY
        `id_module` ASC, `zz_hooks`.`id` ASC');

$gruppi = collect($hooks)->groupBy('modulo');
foreach ($gruppi as $modulo => $hooks) {
    echo '
    <thead>
        <tr>
            <th colspan="4" class="text-center text-muted" >'.$modulo.'</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($hooks as $hook) {
        $hook = Hook::where('id', '=', $hook['id'])->withoutGlobalScopes()->first();

        $class = $hook->enabled ? 'success' : 'warning';
        $nome_tipo = 'hook';

        echo '
            <tr class="'.$class.'" data-id="'.$hook->id.'" data-nome='.json_encode($hook->name).'>
                <td>
                    '.$hook->name.(!empty($hook->help) ? '
                    <i class="tip fa fa-question-circle-o" title="'.$hook->help.'"</i>' : '').'
                </td>
             
                <td class="text-center">
                    '.(!empty($hook->processing_at) ? Translator::timestampToLocale($hook->processing_at) : '').'
                </td>

                <td class="text-center">';

        // Possibilità di disabilitare o abilitare il hook
        if ($hook->enabled) {
            echo '
                <div class="tip" data-widget="tooltip" title="'.tr('Questo _TYPE_ è abilitato: clicca qui per disabilitarlo', [
                '_TYPE_' => $nome_tipo,
            ]).'">
                    <button type="button" class="btn btn-warning" onclick="disabilitaHook(this)">
                        <i class="fa fa-power-off" title="'.tr('Disabilita').'"></i>
                    </button>
                </div>';
        } else {
            echo '
                <div class="tip" data-widget="tooltip" title="'.tr('Questo _TYPE_ è disabilitato: clicca qui per abilitarlo', [
                '_TYPE_' => $nome_tipo,
            ]).'">
                    <button type="button" class="btn btn-success" onclick="abilitaHook(this)">
                        <i class="fa fa-plug" title="'.tr('Abilita').'"></i>
                    </button>
                </div>';
        }

        echo '
            </td>
        </tr>';
    }
}

echo '<tr><td colspan="3"><p>&nbsp;</p><button type="button" class="btn btn-danger pull-right" onclick="svuotaCacheHooks(this)">
    <i class="fa fa-trash" title="'.tr('Svuota cache degli hooks').'"></i> '.tr('Svuota cache').'</button>
    </td></tr>';

echo '
</tbody>';

echo '
</table>

<script>

function svuotaCacheHooks(button){
    swal({
        title: "'.tr('Svuota la cache degli hooks', []).'",
        html: "'.tr('Sei sicuro di voler svuotare la cache degli hooks?', []).'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "svuota-cache-hooks",
            },
            success: function (response) {
                buttonRestore(button, restore);
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);
                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}
function disabilitaHook(button){
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const nome_tipo = "hook";

    swal({
        title: "'.tr('Disabilita _TYPE_', [
    '_TYPE_' => '" + nome_tipo + "',
]).'",
        html: "'.tr('Sei sicuro di voler disabilitare l\'_TYPE_ _NAME_?', [
    '_TYPE_' => '" + nome_tipo + "',
    '_NAME_' => '" + nome + "',
]).'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "disabilita-hook",
                id: id,
            },
            success: function (response) {
                caricaElencoHooks();
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);

                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}

function abilitaHook(button) {
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const nome_tipo = "hook";

    swal({
        title: "'.tr('Abilita _TYPE_', [
    '_TYPE_' => '" + nome_tipo + "',
]).'",
        html: "'.tr('Sei sicuro di voler abilitare l\'_TYPE_ _NAME_?', [
    '_TYPE_' => '" + nome_tipo + "',
    '_NAME_' => '" + nome + "',
]).'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "abilita-hook",
                id: id,
            },
            success: function (response) {
                caricaElencoHooks();
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);

                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}
</script>';
