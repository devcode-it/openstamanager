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

echo '
<table class="table table-hover table-condensed">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Dimensione').'</th>
            <th>'.tr('Ubicazione').'</th>
            <th>'.tr('Stato').'</th>
            <th>'.tr('Posizione').'</th>
        </tr>
    </thead>';

$widgets = $dbo->fetchArray('SELECT zz_widgets.*, zz_modules.name AS modulo
FROM zz_widgets
    INNER JOIN zz_modules ON zz_widgets.id_module = zz_modules.id
ORDER BY `id_module` ASC, `zz_widgets`.`order` ASC');

$gruppi = collect($widgets)->groupBy('modulo');
foreach ($gruppi as $modulo => $widgets) {
    echo '
    <thead>
        <tr>
            <th colspan="4">'.$modulo.'</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($widgets as $widget) {
        $class = $widget['enabled'] ? 'success' : 'warning';
        $nome_tipo = 'widget';

        echo '
            <tr class="'.$class.'" data-id="'.$widget['id'].'" data-nome='.json_encode($widget['name']).'>
                <td>
                    '.$widget['name'].(!empty($widget['help']) ? '
                    <i class="tip fa fa-question-circle-o" title="'.$widget['help'].'"</i>' : '').'
                </td>
                <td>
                {[ "type": "select", "name": "dimensione[]", "class": "widgets", "value": "'.$widget['class'].'", "values": "list=\"0\": \"'.tr('Da impostazioni').'\", \"col-md-3\": \"'.tr('Piccolo').'\", \"col-md-4\": \"'.tr('Medio').'\", \"col-md-6\": \"'.tr('Grande').'\", \"col-md-12\": \"'.tr('Molto grande').'\"", "extra": "data-id=\"'.$widget['id'].'\"" ]}
                </td>
                <td><small>'.(
                    string_starts_with($widget['location'], 'controller') ?
                        tr('Schermata modulo') :
                        tr('Schermata dettagli')
                ).'</small></td>
                <td class="text-center">';

        // Possibilità di disabilitare o abilitare il widget
        if ($widget['enabled']) {
            echo '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo _TYPE_ è abilitato: clicca qui per disabilitarlo', [
                        '_TYPE_' => $nome_tipo,
                    ]).'">
                    <button type="button" class="btn btn-warning btn-xs" onclick="disabilitaWidget(this)">
                        <i class="fa fa-power-off" title="'.tr('Disabilita').'"></i>
                    </button>
                </div>';
        } else {
            echo '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo _TYPE_ è disabilitato: clicca qui per abilitarlo', [
                        '_TYPE_' => $nome_tipo,
                    ]).'">
                    <button type="button" class="btn btn-success btn-xs" onclick="abilitaWidget(this)">
                        <i class="fa fa-plug" title="'.tr('Abilita').'"></i>
                    </button>
                </div>';
        }

        echo '
            </td>
            <td class="text-center">';

        // Possibilità di spostare il widget
        if (string_ends_with($widget['location'], 'top')) {
            echo '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo widget è posizionato nella parte superiore della pagina').'">
                    <i class="fa fa-arrow-up" title="'.tr('Parte superiore').'"></i>
                </div>

                <div class="tip" data-toggle="tooltip" title="'.tr('Clicca qui per spostare il widget nella parte laterale').'">
                    <button type="button" class="btn btn-info btn-xs" onclick="spostaWidget(this)">
                        <i class="fa fa-arrow-right" title="'.tr('Sposta').'"></i>
                    </button>
                </div>';
        } else {
            echo '
                <div class="tip" data-toggle="tooltip" title="'.tr('Clicca qui per spostare il widget nella parte superiore').'">
                    <button type="button" class="btn btn-info btn-xs" onclick="spostaWidget(this)">
                        <i class="fa fa-arrow-up" title="'.tr('Sposta').'"></i>
                    </button>
                </div>

                <div class="tip" data-toggle="tooltip" title="'.tr('Questo widget è posizionato nella parte laterale della pagina').'">
                    <i class="fa fa-arrow-right" title="'.tr('Parte laterale').'"></i>
                </div>';
        }

        echo '
            </td>
        </tr>';
    }

    echo '
    </tbody>';
}

echo '
</table>

<script>
function disabilitaWidget(button){
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const nome_tipo = "widget";

    swal({
        title: "'.tr('Disabilitare il _TYPE_?', [
            '_TYPE_' => '" + nome_tipo + "',
        ]).'",
        html: "'.tr('Sei sicuro di voler disabilitare il _TYPE_ _NAME_?', [
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
                op: "disabilita-widget",
                id: id,
            },
            success: function (response) {
                caricaElencoWidget();
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

function abilitaWidget(button) {
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const nome_tipo = "widget";

    swal({
        title: "'.tr('Abilitare il _TYPE_?', [
            '_TYPE_' => '" + nome_tipo + "',
        ]).'",
        html: "'.tr('Sei sicuro di voler abilitare il _TYPE_ _NAME_?', [
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
                op: "abilita-widget",
                id: id,
            },
            success: function (response) {
                caricaElencoWidget();
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

function spostaWidget(button) {
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    let restore = buttonLoading(button);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "sposta-widget",
            id: id,
        },
        success: function (response) {
            caricaElencoWidget();
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
}

$(".widgets").on("change", function() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "POST",
        dataType: "JSON",
        data: {
            op: "cambia-dimensione",
            id_module: globals.id_module,
            id: $(this).data("id"),
            valore: $(this).val()
        },
        success: function(data) {
            renderMessages();
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio dei dati').'", "error");
        }
    });
});

</script>';
