<?php

include_once __DIR__.'/core.php';

if (file_exists($docroot.'/include/custom/top.php')) {
    include $docroot.'/include/custom/top.php';
} else {
    include $docroot.'/include/top.php';
}

// Lettura parametri iniziali del modulo
$module = Modules::get($id_module);

if (empty($module) || empty($module['enabled'])) {
    die(tr('Accesso negato'));
}

$module_dir = $module['directory'];

include $docroot.'/actions.php';

/*
 * Widget top
 */
 
 //se non sono mobile nascondo i widget controller_top
if (!isMobile()){
	echo Widgets::addModuleWidgets($id_module, 'controller_top');
}

// Lettura eventuali plugins modulo da inserire come tab
echo '
		<div class="nav-tabs-custom">
			<ul class="nav nav-tabs pull-right" id="tabs" role="tablist">
				<li class="pull-left active header">';

// Verifico se ho impostato un nome modulo personalizzato
$name = $module['title'];

echo '
					<a data-toggle="tab" href="#tab_0">
						<i class="'.$module['icon'].'"></i> '.$name;
// Pulsante "Aggiungi" solo se il modulo è di tipo "table" e se esiste il template per la popup
if (file_exists($docroot.'/modules/'.$module_dir.'/add.php') && $module['permessi'] == 'rw') {
    echo '
						<button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-target="#bs-popup" data-href="add.php?id_module='.$id_module.'"><i class="fa fa-plus"></i></button>';
}
echo '
					</a>
				</li>';

$plugins = $dbo->fetchArray('SELECT id, title FROM zz_plugins WHERE idmodule_to='.prepare($id_module)." AND position='tab_main' AND enabled = 1");
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

// Inclusione contenuti varie tab dei plugin
foreach ($plugins as $plugin) {
    echo '
				<div id="tab_'.$plugin['id'].'" class="tab-pane">';

    $id_plugin = $plugin['id'];

    include $docroot.'/include/manager.php';

    echo '
				</div>';
}

echo '
			</div>
		</div>
    </div>';

redirectOperation($id_module, $id_record);

/**
 * Widget laterali.
 */
// Controllo se ho widget per il lato destro dello schermo, altrimenti non creo la colonna di destra

//se sono mobile pesco anche i widget di controller_top
if (isMobile()){
	$extra_where = " OR location = 'controller_top'";
}else{
	$extra_where = "";
}


$result_widgets = $dbo->fetchArray('SELECT `id`, `location`, `class` FROM `zz_widgets` WHERE `id_module`='.prepare($id_module)." AND (`location`='controller_right' ".$extra_where." ) AND `enabled`=1 ORDER BY `order` ASC");
if (count($result_widgets) > 0) {
    echo '
	<div class="col-md-12">';
    echo Widgets::addModuleWidgets($id_module, 'controller_right');
    echo '
	</div>';
}

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include $docroot.'/include/custom/bottom.php';
} else {
    include $docroot.'/include/bottom.php';
}
