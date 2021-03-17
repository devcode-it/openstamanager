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

include_once __DIR__.'/core.php';

if (!empty($id_record) && !empty($id_module)) {
    redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
} elseif (empty($id_module)) {
    redirect(base_path().'/index.php');
}

include_once App::filepath('include|custom|', 'top.php');

// Inclusione gli elementi fondamentali
include_once base_dir().'/actions.php';

// Widget in alto
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "position": "top", "place": "controller" )}';

// Lettura eventuali plugins modulo da inserire come tab
echo '
		<div class="nav-tabs-custom">
			<ul class="nav nav-tabs pull-right" id="tabs" role="tablist">
				<li class="pull-left active header">
					<a data-toggle="tab" href="#tab_0">
                        <i class="'.$structure['icon'].'"></i> '.$structure['title'];

// Pulsante "Aggiungi" solo se il modulo è di tipo "table" e se esiste il template per la popup
if ($structure->hasAddFile() && $structure->permission == 'rw') {
    echo '
						<button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'"><i class="fa fa-plus"></i></button>';
}

echo '
					</a>
				</li>';

$plugins = $dbo->fetchArray('SELECT id, title FROM zz_plugins WHERE idmodule_to='.prepare($id_module)." AND position='tab_main' AND enabled = 1");

// Tab dei plugin
foreach ($plugins as $plugin) {
    echo '
				<li>
					<a data-toggle="tab" href="#tab_'.$plugin['id'].'" id="link-tab_'.$plugin['id'].'">'.$plugin['title'].'</a>
				</li>';
}

echo '
			</ul>
			<div class="tab-content">
				<div id="tab_0" class="tab-pane active">';

include base_dir().'/include/manager.php';

echo '
				</div>';

// Plugin
$module_record = $record;
foreach ($plugins as $plugin) {
    $record = $module_record;

    echo '
				<div id="tab_'.$plugin['id'].'" class="tab-pane">';

    $id_plugin = $plugin['id'];

    include base_dir().'/include/manager.php';

    echo '
				</div>';
}

$record = $module_record;

echo '
			</div>
		</div>';

redirectOperation($id_module, isset($id_parent) ? $id_parent : $id_record);

// Interfaccia per la modifica dell'ordine e della visibilità delle colonne (Amministratore)
if ($user->is_admin && string_contains($module['option'], '|select|')) {
    echo '
<a class="btn btn-xs btn-default pull-right" style="margin-top: -1.25rem;" onclick="modificaColonne(this)">
    <i class="fa fa-th-list"></i> '.tr('Modifica colonne').'
</a><div class="clearfix" >&nbsp;</div>

<script>
function modificaColonne(button) {
    openModal("'.tr('Modifica colonne').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&op=aggiorna_colonne")
}
</script>';
}

// Widget in basso
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "position": "right", "place": "controller" )}';

include_once App::filepath('include|custom|', 'bottom.php');
