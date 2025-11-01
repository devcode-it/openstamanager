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

use Models\Plugin;

include_once __DIR__.'/core.php';

if (!empty($id_record) && !empty($id_module)) {
    redirect_url(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
} elseif (empty($id_module)) {
    redirect_url(base_path().'/index.php');
}

include_once App::filepath('include|custom|', 'top.php');

// Inclusione gli elementi fondamentali
include_once base_dir().'/actions.php';

// Widget in alto
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "position": "top", "place": "controller" )}';

$segmenti = $dbo->FetchArray('SELECT `id` FROM `zz_segments` WHERE `id_module` = '.prepare($id_module));
if ($segmenti) {
    $segmenti = Modules::getSegments($id_module);
    if (empty($segmenti)) {
        echo '
<div class="alert alert-warning">
	<i class="fa fa-warning-circle"></i> '.tr('Questo gruppo di utenti non ha i permessi per visualizzare nessun segmento di questo modulo').'.
</div>';
    }
}

// Lettura eventuali plugins modulo da inserire come tab
echo '
<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>
					<i class="'.$structure['icon'].'"></i> '.$structure->getTranslation('title');

// Pulsante "Aggiungi" solo se il modulo è di tipo "table" e se esiste il template per la popup
if ($structure->hasAddFile() && $structure->permission == 'rw') {
    echo '
						<button type="button" class="btn btn-primary" data-widget="modal" data-title="'.tr('Aggiungi').'..." data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'"><i class="fa fa-plus"></i></button>';
}

echo '
				</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="'.$rootdir.'/">Home</a></li>
					<li class="breadcrumb-item active">'.$structure->getTranslation('title').'</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<div class="tab-content">
	<div id="tab_0" class="tab-pane active">';

include base_dir().'/include/manager.php';

echo '
	</div>';

// Plugins
$plugins = Plugin::where('idmodule_to', $id_module)->where('position', 'tab_main')->where('enabled', 1)->get();

$module_record = $record;
foreach ($plugins as $plugin) {
    $record = $module_record;

    echo '
	<div id="tab_'.$plugin->id.'" class="tab-pane">';

    $id_plugin = $plugin->id;

    include base_dir().'/include/manager.php';

    echo '
	</div>';
}

$record = $module_record;

redirectOperation($id_module, !empty($id_parent) ? $id_parent : $id_record);

// Interfaccia per la modifica dell'ordine e della visibilità delle colonne (Amministratore)
if ($user->is_admin && string_contains($module['option'], '|select|')) {
    echo '
	<br>
	<div class="row">
		<div class="col-md-12 text-right">
			<a class="btn btn-xs btn-default " style="margin-top: -1.25rem;" onclick="modificaColonne(this)">
				<i class="fa fa-th-list"></i> '.tr('Modifica colonne').'
			</a>
		</div>
	</div>
	<div class="clearfix" >&nbsp;
	</div>

<script>
function modificaColonne(button) {
	openModal("'.tr('Modifica colonne').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&op=aggiorna_colonne")
}
</script>';
}

echo '
</div>';

// Widget in basso
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "position": "right", "place": "controller" )}';

include_once App::filepath('include|custom|', 'bottom.php');
