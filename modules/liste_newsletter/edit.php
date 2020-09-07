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

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Dati campagna').'</h3>
		</div>

		<div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "required": 1, "value": "$name$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "description", "required": 0, "value": "$description$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "'.tr('Query dinamica').'", "name": "query", "required": 0, "value": "$query$", "help": "'.tr("La query SQL deve restituire gli identificativi delle anagrafiche da inserire nella lista, sotto un campo di nome ''id''").'. '.tr('Per esempio: _SQL_', [
                        '_SQL_' => 'SELECT idanagrafica AS id FROM an_anagrafiche',
                    ]).'" ]}
                </div>
            </div>
        </div>
	</div>
</form>

<form action="" method="post" id="receivers-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_receivers">

	<!-- Destinatari -->
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">'.tr('Aggiunta destinatari').'</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Destinatari').'", "name": "receivers[]", "ajax-source": "anagrafiche_newsletter", "multiple": 1, "disabled": '.intval(!empty($lista->query)).' ]}
                </div>
            </div>

            <div class="row pull-right">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>';

$anagrafiche = $lista->anagrafiche;

echo '
<!-- Destinatari -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            '.tr('Destinatari').'
            <span class="badge">'.$anagrafiche->count().'</span>
        </h3>
    </div>

    <div class="panel-body">';

if (!$anagrafiche->isEmpty()) {
    echo '
        <table class="table table-hover table-condensed table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Nome').'</th>
                    <th class="text-center">'.tr('Indirizzo').'</th>
                    <th class="text-center" width="60">#</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($anagrafiche as $anagrafica) {
        echo '
                <tr '.(empty($anagrafica->email) ? 'class="bg-danger"' : '').'>
                    <td>'.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'</td>
                    <td class="text-center">'.$anagrafica->email.'</td>
                    <td class="text-center">
                        <a class="btn btn-danger ask btn-sm '.(!empty($lista->query) ? 'disabled' : '').'" data-backto="record-edit" data-op="remove_receiver" data-id="'.$anagrafica->id.'" '.(!empty($lista->query) ? 'disabled' : '').'>
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>';
} else {
    echo '
        <p>'.tr('Nessuna anagrafica collegata alla lista').'.</p>';
}

    echo '
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
