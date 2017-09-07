<?php

include_once __DIR__.'/core.php';

if (empty($id_record) && !empty($id_module)) {
    redirect(ROOTDIR.'/controller.php?id_module='.$id_module);
} elseif (empty($id_record) && empty($id_module)) {
    redirect(ROOTDIR.'/index.php');
}

if (file_exists($docroot.'/include/custom/top.php')) {
    include $docroot.'/include/custom/top.php';
} else {
    include $docroot.'/include/top.php';
}

// Lettura parametri iniziali del modulo
$module = Modules::getModule($id_module);

if (empty($module) || empty($module['enabled'])) {
    die(tr('Accesso negato'));
}

$module_dir = $module['directory'];

// Inclusione elementi fondamentali del modulo
include $docroot.'/actions.php';

$advanced_sessions = get_var('Attiva notifica di presenza utenti sul record');
if ($advanced_sessions) {
    $dbo->query('DELETE FROM zz_semaphores WHERE id_utente='.prepare($_SESSION['id_utente']).' AND posizione='.prepare($id_module.', '.$id_record));
    $dbo->query('INSERT INTO zz_semaphores (id_utente, posizione, updated) VALUES ('.prepare($_SESSION['id_utente']).', '.prepare($id_module.', '.$id_record).', NOW())');

    echo '
		<div class="box box-warning box-solid text-center info-active hide">
			<div class="box-header with-border">
				<h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Attenzione!').'</h3>
			</div>
			<div class="box-body">
				<p>'.tr('I seguenti utenti stanno visualizzando questa pagina').':</p>
				<ul class="list">
				</ul>
				<p>'.tr('Prestare attenzione prima di effettuare modifiche, poichè queste potrebbero essere perse a causa di multipli salvataggi contemporanei').'.</p>
			</div>
		</div>';
}

if (empty($records)) {
    echo '
		<p>'.tr('Record non trovato').'.</p>';
} else {
    /*
        * Lettura eventuali plugins modulo da inserire come tab
        */
    echo '
		<div class="nav-tabs-custom">
			<ul class="nav nav-tabs pull-right" role="tablist">
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
					<a class="back-btn" href="controller.php?id_module='.$id_module.'"><i class="fa fa-chevron-left"></i> '.tr("Torna all'elenco").'</a>
				</li>';

    $plugins = $dbo->fetchArray('SELECT id, title FROM zz_plugins WHERE idmodule_to='.prepare($id_module)." AND position='tab' AND enabled = 1");
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

    // Lettura template modulo (verifico se ci sono template personalizzati, altrimenti uso quello base)
    if (file_exists($docroot.'/modules/'.$module_dir.'/custom/edit.php')) {
        include $docroot.'/modules/'.$module_dir.'/custom/edit.php';
    } elseif (file_exists($docroot.'/modules/'.$module_dir.'/custom/edit.html')) {
        include $docroot.'/modules/'.$module_dir.'/custom/edit.html';
    } elseif (file_exists($docroot.'/modules/'.$module_dir.'/edit.php')) {
        include $docroot.'/modules/'.$module_dir.'/edit.php';
    } elseif (file_exists($docroot.'/modules/'.$module_dir.'/edit.html')) {
        include $docroot.'/modules/'.$module_dir.'/edit.html';
    }

    echo '
            </div>';

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
		</div>';
}

redirectOperation();

echo '
		<hr>
		<a href="controller.php?id_module='.$id_module.'"><i class="fa fa-chevron-left"></i> '.tr('Indietro').'</a>';

/*
* Widget laterali
*/
echo '
	</div>
	<div class="col-xs-12 col-md-12">';
echo Widgets::addModuleWidgets($id_module, 'editor_right');
echo '
	</div>';

?>
<script>
<?php

// Se l'utente ha i permessi in sola lettura per il modulo, converto tutti i campi di testo in span
if ($module['permessi'] == 'r') {
    ?>
			$(document).ready( function(){
				$('input, textarea, select', 'section.content').attr('readonly', 'true');
				$('select, input[type="checkbox"]').prop('disabled', true);
				$('a.btn, button, input[type=button], input[type=submit]', 'section.content').hide();
				$('a.btn-info, button.btn-info, input[type=button].btn-info', 'section.content').show();
			});
<?php

} ?>

		var content_was_modified = false;

		//controllo se digito qualche valore o cambio qualche select
		$("input, textarea, select").bind("change paste keyup", function(event) {
			if( event.keyCode >= 32 ){
				content_was_modified = true;
			}
		});

		//tolgo il controllo se sto salvando
		$(".btn-success, button[type=submit]").bind("click", function() {
			content_was_modified = false;
		});

		// questo controllo blocca il modulo vendita al banco, dopo la lettura con barcode, appare il messaggio di conferma
		window.onbeforeunload = function(){
			if(content_was_modified) {
				return  'Uscire senza salvare?';
			}
		};
<?php
if ($advanced_sessions) {
    ?>

		function getActiveUsers(){
			$.getJSON('<?php echo ROOTDIR; ?>/call.php', {
				id_module: <?php echo $id_module; ?>,
				id_record: <?php echo $id_record; ?>
			},
			function(data) {
				if (data.length != 0) {
					$(".info-active").removeClass("hide");
					$(".info-active .list").html("");
					$.each( data, function( key, val ) {
						$(".info-active .list").append("<li>"+val.username+"</li>");
					});
				}
				else $(".info-active").addClass("hide");
			});
		}

		getActiveUsers();

		setInterval(getActiveUsers, <?php echo get_var('Timeout notifica di presenza (minuti)') * 1000; ?>);
<?php

}
?>
	</script>
<?php

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include $docroot.'/include/custom/bottom.php';
} else {
    include $docroot.'/include/bottom.php';
}
