<?php

include_once __DIR__.'/core.php';

use Carbon\Carbon;

if (empty($id_record) && !empty($id_module)) {
    redirect(ROOTDIR.'/controller.php?id_module='.$id_module);
} elseif (empty($id_record) && empty($id_module)) {
    redirect(ROOTDIR.'/index.php');
}

include_once App::filepath('include|custom|', 'top.php');

// Inclusione gli elementi fondamentali
include_once $docroot.'/actions.php';

// Widget in alto
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "position": "top", "place": "editor" )}';

$advanced_sessions = setting('Attiva notifica di presenza utenti sul record');
if (!empty($advanced_sessions)) {
    $dbo->query('DELETE FROM zz_semaphores WHERE id_utente='.prepare(Auth::user()['id']).' AND posizione='.prepare($id_module.', '.$id_record));

    $dbo->query('INSERT INTO zz_semaphores (id_utente, posizione, updated) VALUES ('.prepare(Auth::user()['id']).', '.prepare($id_module.', '.$id_record).', NOW())');

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

if (empty($record)) {
    echo '
        <div class="text-center">
    		<h3 class="text-muted">'.
                '<i class="fa fa-question-circle"></i> '.tr('Record non trovato').'
                <br><br>
                <small class="help-block">'.tr('Stai cercando di accedere ad un record eliminato o non presente').'</small>
            </h3>
            <br>

            <a class="btn btn-default" href="'.ROOTDIR.'/controller.php?id_module='.$id_module.'">
                <i class="fa fa-chevron-left"></i> '.tr('Indietro').'
            </a>
        </div>';
} else {
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

    // Tab per le informazioni sulle operazioni
    if (Auth::admin()) {
        echo '
				<li class="bg-warning">
					<a data-toggle="tab" href="#tab_info" id="link-tab_info">'.tr('Info').'</a>
				</li>';
    }

    $plugins = $dbo->fetchArray('SELECT id, title FROM zz_plugins WHERE idmodule_to='.prepare($id_module)." AND position='tab' AND enabled = 1 ORDER BY zz_plugins.order DESC");

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

    // Pulsanti di default
    echo '
                    <div id="pulsanti">
                        <a class="btn btn-warning" href="'.ROOTDIR.'/controller.php?id_module='.$id_module.'">
                            <i class="fa fa-chevron-left"></i> '.tr("Torna all'elenco").'
                        </a>

                        <div class="pull-right">
                            {( "name": "button", "type": "print", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'" )}

                            {( "name": "button", "type": "email", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'" )}

                            <a class="btn btn-success" id="save">
                                <i class="fa fa-check"></i> '.tr('Salva').'
                            </a>
                        </div>
                    </div>

                    <script>
                    $(document).ready(function(){
                        var form = $("#module-edit").find("form").first();

                        // Aggiunta del submit
                        form.prepend(\'<button type="submit" id="submit" class="hide"></button>\');

                        $("#save").click(function(){
                            $("#submit").trigger("click");
                        });';

    // Pulsanti dinamici
    if (!isMobile()) {
        echo '
                        $("#pulsanti").affix({
                            offset: {
                                top: 200
                            }
                        });

                        if ($("#pulsanti").hasClass("affix")) {
                            $("#pulsanti").css("width", $("#tab_0").css("width"));
                        }

                        $("#pulsanti").on("affix.bs.affix", function(){
                            $("#pulsanti").css("width", $("#tab_0").css("width"));
                        });

                        $("#pulsanti").on("affix-top.bs.affix", function(){
                            $("#pulsanti").css("width", "100%");
                        });';
    }

    echo '
                    });
                    </script>

                    <div class="clearfix"></div>
                    <br>';

    // Pulsanti personalizzati
    $buttons = $structure->filepath('buttons.php');
    if (!empty($buttons)) {
        ob_start();
        include $buttons;
        $buttons = ob_get_clean();

        echo '
                    <div class="pull-right" id="pulsanti-modulo">
                        '.$buttons.'
                    </div>

                    <div class="clearfix"></div>
                    <br>';
    }

    // Contenuti del modulo
    echo '

                    <div id="module-edit">';

    include $structure->getEditFile();

    echo '
                    </div>
                </div>';

    // Campi personalizzati
    echo '

                <div class="hide" id="custom_fields_top-edit">
                    {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "position": "top" )}
                </div>

                <div class="hide" id="custom_fields_bottom-edit">
                    {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'" )}
                </div>

                <script>
                $(document).ready(function(){
                    var form = $("#custom_fields_top-edit").parent().find("form").first();

                    // Campi a inizio form
                    form.prepend($("#custom_fields_top-edit").html());

                    // Campi a fine form
                    var last = form.find(".panel").last();

                    if (!last.length) {
                        last = form.find(".box").last();
                    }

                    if (!last.length) {
                        last = form.find(".row").eq(-2);
                    }

                    last.after($("#custom_fields_bottom-edit").html());
                });
                </script>';

    // Informazioni sulle operazioni
    if (Auth::admin()) {
        echo '
                <div id="tab_info" class="tab-pane">';

        $operations = $dbo->fetchArray('SELECT `zz_operations`.*, `zz_users`.`username` FROM `zz_operations` JOIN `zz_users` ON `zz_operations`.`id_utente` = `zz_users`.`id` WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record).' ORDER BY `created_at` ASC LIMIT 200');

        if (!empty($operations)) {
            echo '
                    <ul class="timeline">';

            foreach ($operations as $operation) {
                $description = $operation['op'];
                $icon = 'pencil-square-o';
                $color = null;
                $timeline_class = null;

                switch ($operation['op']) {
                    case 'add':
                        $description = tr('Creazione');
                        $icon = 'plus';
                        $color = 'success';
                        break;

                    case 'update':
                        $description = tr('Modifica');
                        $icon = 'pencil';
                        $color = 'info';
                        break;

                    case 'delete':
                        $description = tr('Eliminazione');
                        $icon = 'times';
                        $color = 'danger';
                        break;

                    default:
                        $timeline_class = ' class="timeline-inverted"';
                        break;
                }

                echo '
                        <li '.$timeline_class.'>
                            <div class="timeline-badge '.$color.'"><i class="fa fa-'.$icon.'"></i></div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h4 class="timeline-title">'.$description.'</h4>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <p><small class="label label-default tip" title="'.Translator::timestampToLocale($operation['created_at']).'"><i class="fa fa-clock-o"></i> '.Carbon::parse($operation['created_at'])->diffForHumans().'</small></p>
                                            <p><small class="label label-default"><i class="fa fa-user"></i> '.tr('_USER_', [
                                                '_USER_' => $operation['username'],
                                            ]).
                                            '</small></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="timeline-body">

                                </div>
                                <div class="timeline-footer">

                                </div>
                            </div>
                        </li>';
            }

            echo '  </ul>';
        } else {
            echo '
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <b>'.tr('Informazione:').'</b> '.tr('Nessun log disponibile per questa scheda').'.
                    </div>';
        }
        echo '
                </div>';
    }

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
}

redirectOperation($id_module, isset($id_parent) ? $id_parent : $id_record);

// Widget in basso
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "position": "right", "place": "editor" )}';

if (!empty($record)) {
    echo '
    		<hr>
            <a class="btn btn-default" href="'.ROOTDIR.'/controller.php?id_module='.$id_module.'">
                <i class="fa fa-chevron-left"></i> '.tr('Indietro').'
            </a>';
}

echo '
        <script>';

// Se l'utente ha i permessi in sola lettura per il modulo, converto tutti i campi di testo in span
$read_only = $structure->permission == 'r';
if ($read_only || !empty($block_edit)) {
    $not = $read_only ? '' : '.not(".unblockable")';

    echo '
			$(document).ready(function(){
				$("input, textarea, select", "section.content")'.$not.'.attr("readonly", "true");
                $("select, input[type=checkbox]", "section.content")'.$not.'.prop("disabled", true);';

    if ($read_only) {
        echo '
				$("a.btn, button, input[type=button], input[type=submit]", "section.content").hide();
                $("a.btn-info, a.btn-warning, button.btn-info, button.btn-warning, input[type=button].btn-info", "section.content").show();';
    }

    echo '
			});';
}
?>

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

			$( "form" ).bind( "submit", function() {
				content_was_modified = false;
			})

			// questo controllo blocca il modulo vendita al banco, dopo la lettura con barcode, appare il messaggio di conferma
            window.onbeforeunload = function(e){
                if(content_was_modified) {
					var dialogText = "Uscire senza salvare?";
					e.returnValue = dialogText;
					$("#main_loading").fadeOut();
					return dialogText;
                }
            };

			 window.addEventListener("unload", function(e) {
				 //console.log(e);
				$("#main_loading").show();
			});


<?php
if (!empty($advanced_sessions)) {
    ?>

            function getActiveUsers(){
                $.getJSON('<?php echo ROOTDIR; ?>/ajax.php?op=active_users', {
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

            setInterval(getActiveUsers, <?php echo setting('Timeout notifica di presenza (minuti)') * 60 * 1000; ?>);
<?php
}
?>
	    </script>
<?php

include_once App::filepath('include|custom|', 'bottom.php');
