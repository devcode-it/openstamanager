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

?>

<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<?php

$elementi = $dbo->fetchArray('SELECT an_referenti.nome, an_anagrafiche.ragione_sociale, an_anagrafiche.idanagrafica FROM an_referenti LEFT JOIN an_anagrafiche ON an_referenti.idanagrafica=an_anagrafiche.idanagrafica WHERE idmansione='.prepare($id_record));

if (!empty($elementi)) {
    echo '
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Referenti collegati: _NUM_', [
        '_NUM_' => count($elementi),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_REF_  (_ANAGRAFICA_)', [
            '_REF_' => $elemento['nome'],
            '_ANAGRAFICA_' => $elemento['ragione_sociale'],
        ]);

        $plugin = 'Referenti';
        $id = $elemento['idanagrafica'];

        echo '
            <li>'.Plugins::link($plugin, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

echo'
<a class="btn btn-danger ask '.($elementi ? "disabled" : "").'" data-backto="record-list'.($elementi ? 'disabled' : '').' >
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
