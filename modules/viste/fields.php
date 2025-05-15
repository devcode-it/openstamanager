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

use Models\View;

echo '

<form action="" method="post" role="form">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="fields">

    <div class="row">
        <div class="col-md-9">
            <div class="data">';

$key = 0;
$fields = View::where('id_module', '=', $record->id)->orderBy('order', 'asc')->get();

foreach ($fields as $key => $field) {
    $editable = !($field->default && $enable_readonly);

    echo '
                <div class="card collapsed-card card-outline card-'.($field->visible ? 'success' : 'danger').'">
                    <div class="card-header with-border">
                        <h3 class="card-title">
                            <i class="fa '.($field->visible ? 'fa-eye text-success' : 'fa-eye-slash text-danger').'"></i>
                            <strong>'.$field->getTranslation('title').'</strong>
                            <small class="text-muted tip" title="'.(new Carbon\Carbon($field->updated_at))->format('d/m/Y H:i').'">
                                <i class="fa fa-clock-o"></i> '.tr('modificato').' '.(new Carbon\Carbon($field->updated_at))->diffForHumans().'
                            </small>
                        </h3>

                        <div class="card-tools pull-right">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div id="field-'.$field->id.'" class="card-body collapse">
                        <div class="row">
                            <input type="hidden" value="'.$field->id.'" name="id['.$key.']">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name['.$key.']">
                                        <i class="fa fa-tag"></i> '.tr('Nome').'
                                    </label>
                                    {[ "type": "text", "name": "name['.$key.']", "value": "'.$field->getTranslation('title').'", "readonly": "'.(!$editable).'", "show-label": "0", "help": "'.tr('Nome con cui il campo viene identificato e visualizzato nella tabella').'" ]}
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="query['.$key.']">
                                        <i class="fa fa-database"></i> '.tr('Query prevista').'
                                    </label>
                                    '.input([
        'type' => 'textarea',
        'name' => 'query['.$key.']',
        'required' => 1,
        'readonly' => ''.(!$editable).'',
        'value' => $field->query,
        'style' => 'height: 80px;',
        'show-label' => '0',
        'help' => tr('Nome effettivo del campo sulla tabella oppure subquery che permette di ottenere il valore del campo.').'<br>'.tr('ATTENZIONE: utilizza sempre i caratteri < o > seguiti da spazio!'),
    ]).'
                                </div>
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-group-title">
                                <i class="fa fa-users"></i> '.tr('Permessi e visibilità').'
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Gruppi con accesso').'", "name": "gruppi['.$key.'][]", "multiple": "1", "values": "query=SELECT DISTINCT `zz_groups`.`id`, `title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_groups`.`id` IN (SELECT `idgruppo` FROM `zz_permissions` WHERE `idmodule` = '.prepare($record->id).' AND `permessi` IN (\'r\', \'rw\')) OR `zz_groups`.`id` IN (SELECT `id_gruppo` FROM `zz_group_view` WHERE `id_vista` = '.prepare($field->id).') ORDER BY `zz_groups`.`id` ASC", "value": "';
    $results = $dbo->fetchArray('SELECT GROUP_CONCAT(DISTINCT `id_gruppo` SEPARATOR \',\') AS gruppi FROM `zz_group_view` WHERE `id_vista`='.prepare($field->id));

    echo $results[0]['gruppi'].'"';

    echo ', "help": "'.tr('Gruppi di utenti in grado di visualizzare questo campo').'" ]}
                                </div>

                                <div class="col-xs-12 col-md-6">
                                    {[ "type": "select", "label": "'.tr('Visibilità').'", "name": "visible['.$key.']", "values": "list=\"0\":\"'.tr('Nascosto (variabili di stato)').'\",\"1\": \"'.tr('Visibile nella sezione').'\"", "value": "'.$field->visible.'", "help": "'.tr('Stato del campo: visibile nella tabella oppure nascosto').'" ]}
                                </div>
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-group-title">
                                <i class="fa fa-search"></i> '.tr('Opzioni di ricerca').'
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    {[ "type": "checkbox", "label": "'.tr('Ricercabile').'", "name": "search['.$key.']", "value": "'.$field->search.'", "help": "'.tr('Indica se il campo è ricercabile').'" ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "checkbox", "label": "'.tr('Ricerca lenta').'", "name": "slow['.$key.']", "value": "'.$field->slow.'", "help": "'.tr("Indica se la ricerca per questo campo è lenta (da utilizzare nel caso di evidenti rallentamenti, mostra solo un avviso all'utente").'" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "text", "label": "'.tr('Ricerca tramite').'", "name": "search_inside['.$key.']", "value": "'.$field->search_inside.'", "readonly": "'.(!$editable).'", "help": "'.tr('Query personalizzata per la ricerca (consigliata per colori e icone)').'.<br>'.tr('ATTENZIONE: utilizza sempre i caratteri < o > seguiti da spazio!').'" ]}
                                </div>
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-group-title">
                                <i class="fa fa-table"></i> '.tr('Visualizzazione e formattazione').'
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    {[ "type": "select", "label": "'.tr('Calcolo a fine colonna').'", "name": "sum-avg['.$key.']", "values": "list=\"0\":\"'.tr('Nessuno').'\",\"sum\":\"'.tr('Somma').'\",\"avg\":\"'.tr('Media').'\"", "value": "'.($field->summable ? 'sum' : ($field->avg ? 'avg' : '')).'", "help": "'.tr('Scegli quale tipo di totale valori vuoi calcolare a fine tabella').'" ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "checkbox", "label": "'.tr('Formattazione automatica').'", "name": "format['.$key.']", "value": "'.$field->format.'", "help": "'.tr('Indica se il campo deve essere formattabile in modo automatico, per esempio valori numerici o date.').'" ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "checkbox", "label": "'.tr('Utilizza HTML').'", "name": "html_format['.$key.']", "value": "'.$field->html_format.'", "help": "'.tr('Indica se il campo deve mantenere la formattazione HTML. Impostazione utile per i campi di testo con editor.').'" ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "text", "label": "'.tr('Ordina tramite').'", "name": "order_by['.$key.']", "value": "'.$field->order_by.'", "readonly": "'.(!$editable).'", "help": "'.tr("Query personalizzata per l'ordinamento (date e numeri formattati tramite query)").'.<br>'.tr('ATTENZIONE: utilizza sempre i caratteri < o > seguiti da spazio!').'" ]}
                                </div>
                            </div>
                        </div>';

    if ($editable) {
        echo '
                        <div class="text-right mt-3">
                            <a class="btn btn-danger ask" data-backto="record-edit" data-id="'.$field->id.'">
                                <i class="fa fa-trash"></i> '.tr('Elimina').'
                            </a>
                        </div>';
    }
    echo '
                    </div>
                </div>';
}

echo '
            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="button" class="btn btn-primary" id="add">
                        <i class="fa fa-plus-circle"></i> '.tr('Aggiungi nuovo campo').'
                    </button>

                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> '.tr('Salva').'
                    </button>
                </div>
            </div>

        </div>

        <div class="col-md-3">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-sort"></i> '.tr('Ordine di visualizzazione').' <span class="tip pull-right" title="'.tr('Trascina per ordinare le colonne').'."><i class="fa fa-question-circle-o"></i></span></h3>
                </div>

                <div class="card-body sortable p-2">';

foreach ($fields as $field) {
    echo '
                    <p class="clickable no-selection" data-id="'.$field->id.'">
                        <i class="fa fa-sort"></i>
                        ';

    if ($field->visible) {
        echo '<strong class="text-success">'.$field->getTranslation('title').'</strong>';
    } else {
        echo '<span class="text-danger">'.$field->getTranslation('title').'</span>';
    }

    echo '
                    </p>';
}

echo '
                </div>
            </div>
        </div>
    </div>
</form>';

echo '
<form class="hide" id="template">
    <div class="card card-success">
        <div class="card-header with-border">
            <h3 class="card-title"><i class="fa fa-plus-circle"></i> '.tr('Nuovo campo').'</h3>
        </div>
        <div class="card-body">
            <div class="field-content p-2">
                <input type="hidden" value="" name="id[-id-]">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="name[-id-]">
                                <i class="fa fa-tag"></i> '.tr('Nome').'
                            </label>
                            {[ "type": "text", "name": "name[-id-]", "show-label": "0" ]}
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="query[-id-]">
                                <i class="fa fa-database"></i> '.tr('Query prevista').'
                            </label>
                            {[ "type": "textarea", "name": "query[-id-]", "style": "height: 80px;", "show-label": "0" ]}
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">
                        <i class="fa fa-users"></i> '.tr('Permessi e visibilità').'
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "select", "label": "'.tr('Gruppi con accesso').'", "name": "gruppi[-id-][]", "multiple": "1", "values": "query=SELECT DISTINCT `zz_groups`.`id`, `title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_groups`.`id` IN (SELECT `idgruppo` FROM `zz_permissions` WHERE `idmodule` = '.prepare($record->id).' AND `permessi` IN (\'r\', \'rw\')) OR `zz_groups`.`id` = 1 ORDER BY `zz_groups`.`id` ASC", "value": "';

// Ottieni tutti gli ID dei gruppi che hanno accesso al modulo
$groups_with_access = $dbo->fetchArray('SELECT DISTINCT `idgruppo` FROM `zz_permissions` WHERE `idmodule` = '.prepare($record->id).' AND `permessi` IN (\'r\', \'rw\')');
$group_ids = array_column($groups_with_access, 'idgruppo');

// Assicurati che il gruppo Amministratori (ID 1) sia incluso
$id_gruppo_admin = 1; // ID del gruppo Amministratori
if (!in_array($id_gruppo_admin, $group_ids)) {
    $group_ids[] = $id_gruppo_admin;
}

echo implode(',', $group_ids);

echo '" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Visibilità').'", "name": "visible[-id-]", "values": "list=\"0\":\"'.tr('Nascosto (variabili di stato)').'\",\"1\": \"'.tr('Visibile nella sezione').'\"" ]}
                </div>
            </div>
        </div>

        <div class="field-group">
            <div class="field-group-title">
                <i class="fa fa-search"></i> '.tr('Opzioni di ricerca').'
            </div>
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Ricercabile').'", "name": "search[-id-]" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Ricerca lenta').'", "name": "slow[-id-]" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Ricerca tramite').'", "name": "search_inside[-id-]" ]}
                </div>
            </div>
        </div>

        <div class="field-group">
            <div class="field-group-title">
                <i class="fa fa-table"></i> '.tr('Visualizzazione e formattazione').'
            </div>
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Calcolo a fine colonna').'", "name": "sum-avg[-id-]", "values": "list=\"0\":\"'.tr('Nessuno').'\",\"sum\":\"'.tr('Somma').'\",\"avg\":\"'.tr('Media').'\"" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Formattazione automatica').'", "name": "format[-id-]", "help": "'.tr('Indica se il campo deve essere formattabile in modo automatico, per esempio valori numerici o date.').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Utilizza HTML').'", "name": "html_format[-id-]", "help": "'.tr('Indica se il campo deve mantenere la formattazione HTML. Impostazione utile per i campi di testo con editor.').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Ordina tramite').'", "name": "order_by[-id-]" ]}
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>
</form>';

echo '
<script>
    var n = '.$key.';
    $(document).on("click", "#add", function() {
        cleanup_inputs();

        n++;
        var text = replaceAll($("#template").html(), "-id-", "" + n);

        $(this).parent().parent().parent().find(".data").append(text);
        restart_inputs();
    });

    $(document).ready(function() {
        $("#save-buttons").hide();

        sortable(".sortable", {
            axis: "y",
            cursor: "move",
            dropOnEmpty: true,
            scroll: true,
        })[0].addEventListener("sortupdate", function(e) {
            let order = $(".sortable p[data-id]").toArray().map(a => $(a).data("id"))

            $.post(globals.rootdir + "/actions.php", {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "update_position",
                order: order.join(","),
            });
        });
    });
</script>';
