<?php

include_once __DIR__.'/core.php';

if (!empty($id_record) && !empty($id_module)) {
    redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
} elseif (empty($id_module)) {
    redirect(ROOTDIR.'/index.php');
}

include_once App::filepath('include|custom|', 'top.php');

// Inclusione gli elementi fondamentali
include_once $docroot.'/actions.php';

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

include $docroot.'/include/manager.php';

echo '
				</div>';

// Plugin
$module_record = $record;
foreach ($plugins as $plugin) {
    $record = $module_record;

    echo '
				<div id="tab_'.$plugin['id'].'" class="tab-pane">';

    $id_plugin = $plugin['id'];

    include $docroot.'/include/manager.php';

    echo '
				</div>';
}

$record = $module_record;

echo '
			</div>
		</div>';

redirectOperation($id_module, isset($id_parent) ? $id_parent : $id_record);

// Widget in basso
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "position": "right", "place": "controller" )}';

include_once App::filepath('include|custom|', 'bottom.php');
