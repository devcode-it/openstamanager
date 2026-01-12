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

include_once __DIR__.'/../../../core.php';

$idautomezzo = get('id');
$idmanutenzione = get('idmanutenzione');

// Recupero dati manutenzione se in modifica
if (!empty($idmanutenzione)) {
    $record = $dbo->fetchOne('SELECT * FROM an_automezzi_scadenze WHERE id = '.prepare($idmanutenzione));
    $title = tr('Modifica');
    $button_icon = 'edit';
} else {
    $record = [];
    $title = tr('Aggiungi');
    $button_icon = 'plus';
}

echo '
<form action="" method="post" id="form-manutenzione">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="'.(!empty($idmanutenzione) ? 'editmanutenzione' : 'addmanutenzione').'">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_record" value="'.$idautomezzo.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="idmanutenzione" value="'.$idmanutenzione.'">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1, "value": "'.($record['descrizione'] ?? '').'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data').'", "name": "data_inizio", "required": 1, "value": "'.($record['data_inizio'] ?? '').'" ]}
        </div>
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Chilometraggio').'", "name": "km", "value": "'.($record['km'] ?? '').'", "icon-after": "km", "decimals": 0 ]}
        </div>
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Completato').'", "name": "is_completato", "value": "'.$record['is_completato'].'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-'.$button_icon.'"></i> '.$title.'</button>
        </div>
    </div>
</form>
<hr>
{( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$idmanutenzione.'", "id_plugin": "'.$id_plugin.'" )}';

// Recupero elenco descrizioni già utilizzate per l'autocompletamento
$descrizioni = $dbo->fetchArray('SELECT DISTINCT(BINARY `descrizione`) AS `descrizione` FROM `an_automezzi_scadenze` WHERE `is_manutenzione` = 1 AND `descrizione` IS NOT NULL AND `descrizione` != "" ORDER BY `descrizione`');
$descrizioni_list = array_clean(array_column($descrizioni, 'descrizione'));

echo '
<script>
var descrizioni = '.json_encode($descrizioni_list).';

// Auto-completamento descrizione
$(document).ready(function () {
    const input = $("input[name=\'descrizione\']")[0];

    if (input) {
        autocomplete({
            minLength: 0,
            showOnFocus: true,
            input: input,
            emptyMsg: globals.translations.noResults,
            fetch: function (text, update) {
                text = text.toLowerCase();
                const suggestions = descrizioni.filter(n => n.toLowerCase().startsWith(text));

                // Trasformazione risultati in formato leggibile
                const results = suggestions.map(function (result) {
                    return {
                        label: result,
                        value: result
                    }
                });

                update(results);
            },
            onSelect: function (item) {
                input.value = item.label;
            },
        });
    }

    init();
});
</script>';
