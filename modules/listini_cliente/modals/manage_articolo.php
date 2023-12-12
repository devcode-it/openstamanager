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
include_once __DIR__.'/../../../../core.php';

if (empty(get('id'))) {
    $listino = $dbo->selectOne('mg_listini', '*', ['id' => get('id_record')]);
    $data_scadenza = $listino['data_scadenza_predefinita'];
    $id_articolo = get('id_articolo');
} else {
    $articolo = $dbo->selectOne('mg_listini_articoli', '*', ['id' => get('id')]);
    $data_scadenza = $articolo['data_scadenza'];
    $id_articolo = $articolo['id_articolo'];
}

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
$prezzo_unitario = $prezzi_ivati ? $articolo['prezzo_unitario_ivato'] : $articolo['prezzo_unitario'];

echo '
<form id="add_form" action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="manage_articolo">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_articolo" value="'.get('id_articolo').'">
    <input type="hidden" name="id" value="'.get('id').'">

    <div class="row">
        <div class="col-md-12">
            {[ "type":"select", "label":"'.tr('Articolo').'", "name":"id_articolo", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1}, "value": "'.$id_articolo.'", "disabled": "1" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type":"date", "label":"'.tr('Data scadenza').'", "name":"data_scadenza", "value":"'.$data_scadenza.'", "required": "1" ]}
        </div>

        <div class="col-md-4">
            {[ "type":"number", "label":"'.tr('Prezzo unitario').'", "name":"prezzo_unitario", "icon-after": "'.currency().'", "value":"'.$prezzo_unitario.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type":"number", "label":"'.tr('Sconto percentuale').'", "name":"sconto_percentuale", "icon-after": "%", "value":"'.$articolo['sconto_percentuale'].'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> '.tr('Salva').'
            </button>
		</div>
	</div>
</form>';
?>

<script>
    var is_add = <?php echo empty(get('id')) ? '1' : '0' ?>;

    $(document).ready(function(){
        init();

        // Inizializzazione prezzo di vendita articolo
        if (is_add) {
            $('#prezzo_unitario').val( $('#id_articolo').selectData().prezzo_vendita );
        }
    });
    content_was_modified = false;
</script>
