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

use Carbon\Carbon;
use Models\Module;

// Disabilitazione dei campi
$read_only = $structure->permission == 'r';
$module_header_html = '';

if (empty($id_record) && !empty($id_module) && empty($id_plugin)) {
    redirect(base_path().'/controller.php?id_module='.$id_module);
} elseif (empty($id_record) && empty($id_module) && empty($id_plugin)) {
    redirect(base_path().'/index.php');
}

include_once App::filepath('include|custom|', 'top.php');

if (!empty($id_record)) {
    Util\Query::setSegments(false);
    $query = Util\Query::getQuery($structure, [
        'id' => $id_record,
    ]);
    Util\Query::setSegments(true);
}
// Rimozione della condizione deleted_at IS NULL per visualizzare anche i record eliminati
if (!empty($query)) {
    if (preg_match('/[`]*([a-z0-9_]*)[`]*[\.]*([`]*deleted_at[`]* IS NULL)/si', $query, $m)) {
        $query = str_replace(["\n", "\t"], ' ', $query);
        $conditions_to_remove = [];
        $condition = trim($m[0]);

        if (!empty($table_name)) {
            $condition = $table_name.'.'.$condition;
        }

        $conditions_to_remove[] = ' AND\s*'.$condition;
        $conditions_to_remove[] = $condition.'\s*AND ';

        foreach ($conditions_to_remove as $condition_to_remove) {
            $query = preg_replace('/'.$condition_to_remove.'/si', '', $query);
        }
        $query = str_replace($condition, '', $query);
    }
}

$has_access = !empty($query) ? $dbo->fetchNum($query) !== 0 : true;

if ($has_access) {
    // Inclusione gli elementi fondamentali
    include_once base_dir().'/actions.php';
}

if (empty($record) || !$has_access) {
    echo '
        <div class="text-center">
    		<h3 class="text-muted">'.
                '<i class="fa fa-question-circle"></i> '.tr('Record non trovato').'
                <br><br>
                <small class="help-block">'.tr('Stai cercando di accedere ad un record eliminato o non presente').'.</small>
            </h3>
            <br>

            <a class="btn btn-default" href="'.base_path().'/controller.php?id_module='.$id_module.'">
                <i class="fa fa-chevron-left"></i> '.tr('Indietro').'
            </a>
        </div>';
} else {
    // Widget in alto
    echo '{( "name": "widgets", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "position": "top", "place": "editor" )}';

    $advanced_sessions = setting('Attiva notifica di presenza utenti sul record');
    if (!empty($advanced_sessions)) {
        $dbo->query('DELETE FROM zz_semaphores WHERE id_utente='.prepare(Auth::user()['id']).' AND posizione='.prepare($id_module.', '.$id_record));

        $dbo->query('INSERT INTO zz_semaphores (id_utente, posizione, updated) VALUES ('.prepare(Auth::user()['id']).', '.prepare($id_module.', '.$id_record).', NOW())');

        echo '
		<div class="card card-warning card-solid text-center info-active hide">
			<div class="card-header with-border">
				<h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Attenzione!').'</h3>
			</div>
			<div class="card-body">
				<p>'.tr('I seguenti utenti stanno consultando questa scheda').':</p>
				<ul class="list">
				</ul>
				<p>'.tr('Prestare attenzione prima di effettuare modifiche, poiché queste potrebbero essere perse a causa di una sovrascrittura delle informazioni').'.</p>
			</div>
		</div>';
    }

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
                        <li class="breadcrumb-item"><a href="'.$rootdir.'/controller.php?id_module='.$id_module.'">'.$structure->getTranslation('title').'</a></li>
                    </ol>
                </div>
            </div>
        </section>

        <div class="tab-content">
            <div id="tab_0" class="tab-pane active nav-item">';

    if (!empty($record['deleted_at'])) {
        $operation = $dbo->fetchOne("SELECT zz_operations.created_at, username FROM zz_operations INNER JOIN zz_users ON zz_operations.id_utente =  zz_users.id  WHERE op='delete' AND id_module=".prepare($id_module).' AND id_record='.prepare($id_record).' ORDER BY zz_operations.created_at DESC');

        $info = tr('Il record è stato eliminato il <b>_DATE_</b> da <b>_USER_</b>', [
            '_DATE_' => (($operation['created_at']) ? Translator::timestampToLocale($operation['created_at']) : Translator::timestampToLocale($record['deleted_at'])),
            '_USER_' => ((!empty($operation['username'])) ? $operation['username'] : 'N.D.'),
        ]).'. ';

        echo '
        <div class="alert alert-warning">
            <div class="row" >
                <div class="col-md-8">
                    <i class="fa fa-warning"></i> '.$info.'
                </div>
            </div>
		</div>

		<script>
            $(document).ready(function(){
                $("#restore, #restore-close").click(function(){
                    $("input[name=op]").attr("value", "restore");
                    $("#submit").trigger("click");
                })
            });
        </script>';
    }

    // Pulsanti di default
    echo '

                <div id="pulsanti">
                    <a class="btn btn-default" id="back" href="'.base_path().'/controller.php?id_module='.$id_module.'">
                        <i class="fa fa-chevron-left"></i> '.tr("Torna all'elenco").'
                    </a>';

    // Pulsante Precedente e Successivo
    // Aggiungo eventuali filtri applicati alla vista
    if (count(getSearchValues($id_module)) > 0) {
        foreach (getSearchValues($id_module) as $key => $value) {
            $where[$key] = $value;
        }
    }

    // Ricavo la posizione per questo id_record
    $order = $_SESSION['module_'.$id_module]['order'] ?: [];
    $module_query = Util\Query::getQuery($structure, $where, $order);
    $posizioni = $module_query ? $dbo->fetchArray($module_query) : 0;
    $key = $posizioni ? array_search($id_record, array_column($posizioni, 'id')) : 0;

    if (is_array($posizioni)) {
        // Precedente
        $prev = $posizioni[$key - 1]['id'];

        // Successivo
        $next = $posizioni[$key + 1]['id'];

        echo '<span class="d-sm-inline">';

        echo '
                    <div class="btn-group">
                        <a class="btn btn-default'.($prev ? '' : ' disabled').'" href="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$prev.'">
                            <i class="fa fa-arrow-circle-left"></i>
                        </a>
                        <span class="btn btn-default disabled">'.($key + 1).'/'.count($posizioni).'</span>
                        <a class="btn btn-default'.($next ? '' : ' disabled').'" href="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$next.'">
                            <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </span>';
    }

    echo '<div class="extra-buttons d-sm-inline">';

    // Pulsanti personalizzati
    $buttons = $structure->filepath('buttons.php');

    if (!empty($buttons)) {
        include $buttons;
    }

    echo '
                        {( "name": "button", "type": "print", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "id_record": "'.$id_record.'" )}

                        {( "name": "button", "type": "email", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "id_record": "'.$id_record.'" )}';

    if (Module::where('name', 'Account SMS')->first()->id) {
        echo '
                        {( "name": "button", "type": "sms", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "id_record": "'.$id_record.'" )}';
    }

    echo '

                        <div class="btn-group" id="save-buttons">
                            <button type="button" class="btn btn-success" id="'.(!empty($record['deleted_at']) ? 'restore' : 'save').'">
                                <i class="fa fa-'.(!empty($record['deleted_at']) ? 'undo' : 'check').'"></i> '.(!empty($record['deleted_at']) ? tr('Salva e ripristina') : tr('Salva')).'
                            </button>
                            <button type="button" class="btn btn-success dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a class="btn dropdown-item" href="#" id="'.(!empty($record['deleted_at']) ? 'restore' : 'save').'-close">
                                    <i class="fa fa-'.(!empty($record['deleted_at']) ? 'undo' : 'check-square-o').'"></i>
                                    '.(!empty($record['deleted_at']) ? tr('Ripristina e chiudi') : tr('Salva e chiudi')).'
                                </a>
                            </div>
                        </div>
                    </div>
                </div>


                <script>
                $(document).ready(function(){
                    var form = $("#module-edit").find("form").first();

                    // Aggiunta del submit
                    form.prepend(\'<button type="submit" id="submit" class="hide"></button>\');

                    $("#save").click(function(){
                        //submitAjax(form);

                        $("#submit").trigger("click");
                    });

                    $("#save-close").on("click", function (){
                        form.find("[name=backto]").val("record-list");
                        $("#submit").trigger("click");
                    });
                });
                </script>

                <div class="clearfix"></div>
                <br>';

    // Eventuale header personalizzato
    $module_header = $structure->filepath('header.php');
    $module_header_html = '';

    if (!empty($module_header)) {
        ob_start();
        include $module_header;
        $module_header_html = ob_get_clean();
    }

    // Eventuale header personalizzato
    if ($module_header_html) {
        echo '<div class="module-header">';
        echo $module_header_html;
        echo '</div>';
    }

    // Contenuti del modulo
    echo '

                <div id="module-edit">';

    $path = $structure->getEditFile();
    if (!empty($path)) {
        include $path;
    }

    echo '
                </div>
            </div>';

    // Campi personalizzati
    echo '

            <div class="hide" id="custom_fields_top-edit_'.$id_module.'-'.$id_plugin.'">
                {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "position": "top" )}
            </div>

            <div class="hide" id="custom_fields_bottom-edit_'.$id_module.'-'.$id_plugin.'">
                {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'" )}
            </div>

            <script>
            $(document).ready(function(){
                let form = $("#edit-form").first();

                // Ultima sezione/campo del form
                let last = form.find(".panel").last();

                if (!last.length) {
                    last = form.find(".box").last();
                }

                if (!last.length) {
                    last = form.find(".row").last();
                }

                // Campi a inizio form
                aggiungiContenuto(form, "#custom_fields_top-edit_'.$id_module.'-'.$id_plugin.'", {}, true);

                // Campi a fine form
                aggiungiContenuto(last, "#custom_fields_bottom-edit_'.$id_module.'-'.$id_plugin.'", {});
            });
            </script>';

    if ($structure->permission != '-' && $structure->use_notes && $user->gruppo != 'Clienti') {
        echo '
            <div id="tab_note" class="tab-pane">';

        // Eventuale header personalizzato
        if ($module_header_html) {
            echo '<div class="module-header">';
            echo $module_header_html;
            echo '</div>';
        }

        include base_dir().'/plugins/notes.php';

        echo '
            </div>';
    }

    if ($structure->permission != '-' && $structure->use_checklists) {
        echo '
            <div id="tab_checks" class="tab-pane">';

        // Eventuale header personalizzato
        if ($module_header_html) {
            echo '<div class="module-header">';
            echo $module_header_html;
            echo '</div>';
        }

        include base_dir().'/plugins/checks.php';

        echo '
            </div>';
    }

    // Informazioni sulle operazioni
    if (Auth::admin()) {
        echo '
            <div id="tab_info" class="tab-pane">';

        // Eventuale header personalizzato
        if ($module_header_html) {
            echo '<div class="module-header">';
            echo $module_header_html;
            echo '</div>';
        }

        echo '
                <div class="card card-outline card-primary mx-auto" style="max-width: 800px;">
                    <div class="card-header">
                        <h5 class="mb-0 text-center">
                            <i class="fa fa-history mr-2"></i> '.tr('Cronologia operazioni').'
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline timeline-sm">';

        $operations = $dbo->fetchArray('SELECT `zz_operations`.*, `zz_users`.`username` FROM `zz_operations` LEFT JOIN `zz_users` ON `zz_operations`.`id_utente` = `zz_users`.`id` WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record).' ORDER BY `created_at` DESC LIMIT 200');

        if (!empty($operations)) {
            $current_date = null;

            foreach ($operations as $operation) {
                $description = $operation['op'];
                $icon = 'pencil-square-o';
                $color = 'warning';
                $date = Carbon::parse($operation['created_at']);
                $formatted_date = $date->format('d/m/Y');

                // Mostra l'intestazione della data se è cambiata
                if ($current_date !== $formatted_date) {
                    echo '
                        <div class="time-label">
                            <span class="bg-primary px-2 py-1 small">
                                '.$formatted_date.'
                            </span>
                        </div>';
                    $current_date = $formatted_date;
                }

                switch ($operation['op']) {
                    case 'add':
                        $description = tr('Creazione');
                        $icon = 'plus-circle';
                        $color = 'success';
                        break;

                    case 'update':
                        $description = tr('Modifica');
                        $icon = 'edit';
                        $color = 'info';
                        break;

                    case 'delete':
                        $description = tr('Eliminazione');
                        $icon = 'trash';
                        $color = 'danger';
                        break;

                    case 'copy':
                        $description = tr('Duplicato');
                        $icon = 'copy';
                        $color = 'primary';
                        break;
                }

                echo '
                    <div>
                        <i class="fa fa-'.$icon.' bg-'.$color.' small"></i>
                        <div class="timeline-item small shadow-sm">
                            <span class="time small"><i class="fa fa-clock-o"></i> '.$date->format('H:i').' ('.$date->diffForHumans().')</span>
                            <h5 class="timeline-header no-border mb-0">'.$description.'</h5>
                            <div class="timeline-body py-2">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3 small">
                                        <i class="fa fa-user mr-1 text-'.$color.'"></i>
                                        <span>'.$operation['username'].'</span>
                                    </div>';

                if (!empty($operation['options'])) {
                    $options = json_decode($operation['options'], true);
                    if (!empty($options) && is_array($options)) {
                        echo '<div class="text-muted small">';
                        $details = [];
                        foreach ($options as $key => $value) {
                            if (!is_array($value) && !empty($value)) {
                                $details[] = '<span>'.ucfirst($key).': <b>'.$value.'</b></span>';
                            }
                        }
                        echo implode(' | ', $details);
                        echo '</div>';
                    }
                }

                echo '
                                </div>
                            </div>
                        </div>
                    </div>';
            }

            echo '
                <div>
                    <i class="fa fa-clock-o bg-gray small"></i>
                    <div class="timeline-item small shadow-sm">
                        <div class="timeline-body py-2 text-center text-muted small">
                            '.tr('Fine cronologia').'
                        </div>
                    </div>
                </div>';
        } else {
            echo '
                <div class="alert alert-info small text-center">
                    <i class="fa fa-info-circle mr-2"></i> '.tr('Nessun log disponibile per questa scheda').'.
                </div>';
        }

        echo '
                        </div>
                    </div>
                </div>
            </div>';
    }

    // Plugin
    $module_record = $record;
    foreach ($plugins as $plugin) {
        $record = $module_record;

        echo '
            <div id="tab_'.$plugin['id'].'" class="tab-pane">';

        // Eventuale header personalizzato
        if ($module_header_html) {
            echo '<div class="module-header">';
            echo $module_header_html;
            echo '</div>';
        }

        $id_plugin = $plugin['id'];

        include base_dir().'/include/manager.php';

        echo '
            </div>';
    }

    $record = $module_record;

    echo '
        </div>';
}

redirectOperation($id_module, !empty($id_parent) ? $id_parent : $id_record);

// Widget in basso
echo '{( "name": "widgets", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "position": "right", "place": "editor" )}';

if (!empty($record)) {
    echo '
        <hr>
        <a class="btn btn-default" href="'.base_path().'/controller.php?id_module='.$id_module.'">
            <i class="fa fa-chevron-left"></i> '.tr('Indietro').'
        </a>';
}

echo '
        <script>';

// Se l'utente ha i permessi in sola lettura per il modulo, converto tutti i campi di testo in span
if ($read_only || !empty($block_edit)) {
    $not = $read_only ? '' : '.not(".unblockable")';

    echo '
			$(document).ready(function(){
				$("input, textarea, select", "section.content")'.$not.'.attr("readonly", "true");
                $("select, input[type=checkbox]", "section.content")'.$not.'.prop("disabled", true);
                $(".checkbox-buttons badge", "section.content")'.$not.'.addClass("disabled");
                ';

    // Nascondo il plugin Note interne ai clienti
    if ($user->gruppo == 'Clienti') {
        echo '
                $("#link-tab_note").hide();';
    }

    if ($read_only) {
        echo '
				$("a.btn, button, input[type=button], input[type=submit]", "section.content").hide();
                $("a.btn-info, button.btn-info, input[type=button].btn-info, #back", "section.content").show();';
    }

    echo '
			});';
}
?>

            var content_was_modified = false;

            // Controllo se digito qualche valore o cambio qualche select
            $(".content input, .content textarea, .content select").bind("change paste keyup", function(event) {
                if (event.keyCode >= 32) {
                    content_was_modified = true;
                }
            });

            $(".content .superselect, .content .superselectajax").on("change", function (e) {
                content_was_modified = true;
            });

            // Tolgo il controllo se sto salvando
            $(".content .btn-success, .content button[type=submit]").bind("click", function() {
                content_was_modified = false;
            });

			$("form").bind("submit", function() {
				content_was_modified = false;
			});

			// questo controllo blocca il modulo vendita al banco, dopo la lettura con barcode, appare il messaggio di conferma
            window.addEventListener("beforeunload", function(e) {
                if(content_was_modified) {
					var dialogText = "Uscire senza salvare?";
					e.returnValue = dialogText;
					return dialogText;
                }
            });


<?php
if (!empty($advanced_sessions)) {
    ?>

            function getActiveUsers(){
                $.getJSON('<?php echo base_path(); ?>/ajax.php?op=active_users', {
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
