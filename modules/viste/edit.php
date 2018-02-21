<?php

include_once __DIR__.'/../../core.php';

$enable_readonly = !get_var('Modifica Viste di default');
$record = $records[0];

echo '
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!--div class="pull-right">
		<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>
	</div>
	<div class="clearfix"></div><br-->

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Opzioni di visualizzazione').'</h3>
		</div>

		<div class="panel-body">';
$options = ($record['options2'] == '') ? $record['options'] : $record['options2'];
if ($options == 'menu') {
    echo '
			<p><strong>'.tr('Il modulo che stai analizzando è un semplice menu').'.</strong></p>';
} elseif ($options == 'custom') {
    echo '
			<p><strong>'.tr("Il modulo che stai analizzando possiede una struttura complessa, che prevede l'utilizzo di file personalizzati per la gestione delle viste").'.</strong></p>';
}

echo '
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Codice del modulo').'", "name": "name", "value": "'.$record['name'].'", "readonly": "1" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Nome del modulo').'", "name": "title", "value": "'.$record['title'].'", "help": "'.tr('Il nome che identifica il modulo').'" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query di default').'", "name": "options", "value": '.json_encode(str_replace(']}', '] }', $record['options'])).', "readonly": "1", "class": "autosize" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query personalizzata').'", "name": "options2", "value": '.json_encode(str_replace(']}', '] }', $record['options2'])).', "class": "autosize", "help": "'.tr('La query in sostituzione a quella di default: custom, menu oppure SQL').'" ]}
				</div>
            </div>';

if ($options != '' && $options != 'menu' && $options != 'custom') {
    $total = App::readQuery(Modules::get($id_record));
    $module_query = $total['query'];

    echo '
			<div class="row">
				<div class="col-md-12">
					<p><strong>'.tr('Query risultante').':</strong></p>
                    <p>'.htmlentities($module_query).'</p>

                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-warning pull-righ" onclick="testQuery()"><i class="fa fa-file-text-o "></i> '.tr('Testa la query').'</button>
							<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>
                        </div>
                    </div>
				</div>
			</div>';
}

echo '
		</div>
	</div>
</form>';

if (!empty($options) && $options != 'custom') {
    echo '

<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="fields">

	<div class="row">
		<div class="col-md-9">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title">'.tr('Campi disponibili').'</h3>
				</div>

				<div class="panel-body">
                    <!--div class="row">
                        <div class="text-right">
                            <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
                        </div>
                    </div>
                    <hr-->

					<div class="data">';

    $key = 0;
    $fields = $dbo->fetchArray('SELECT * FROM zz_views WHERE id_module='.prepare($record['id']).' ORDER BY `order` ASC');
    foreach ($fields as $key => $field) {
        $editable = !($field['default'] && $enable_readonly);

        echo '
					<div class="box ';
        if ($field['enabled']) {
            echo 'box-success';
        } else {
            echo 'box-danger';
        }
        echo '">
							<div class="box-header with-border">
								<h3 class="box-title">
									<a data-toggle="collapse" href="#field-'.$field['id'].'">'.tr('Campo in posizione _POSITION_', [
                                        '_POSITION_' => $field['order'],
                                    ]).' ('.$field['name'].')</a>
								</h3>';
        if ($editable) {
            echo '
                                <a class="btn btn-danger ask pull-right" data-backto="record-edit" data-id="'.$field['id'].'">
                                    <i class="fa fa-trash"></i> '.tr('Elimina').'
                                </a>';
        }
        echo '
							</div>
							<div id="field-'.$field['id'].'" class="box-body collapse">
								<div class="row">
									<input type="hidden" value="'.$field['id'].'" name="id['.$key.']">

									<div class="col-md-12">
										{[ "type": "text", "label": "'.tr('Nome').'", "name": "name['.$key.']", "value": "'.$field['name'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Nome con cui il campo viene identificato e visualizzato nella tabella').'" ]}
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										{[ "type": "textarea", "label": "'.tr('Query prevista').'", "name": "query['.$key.']", "value": "'.$field['query'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "required": "1", "help": "'.tr('Nome effettivo del campo sulla tabella oppure subquery che permette di ottenere il valore del campo').'.<br>'.tr('ATTENZIONE: utilizza sempre i caratteri < o > seguiti da spazio!').'" ]}
									</div>
								</div>

								<div class="row">
									<div class="col-md-6">
										{[ "type": "select", "label": "'.tr('Gruppi con accesso').'", "name": "gruppi['.$key.'][]", "multiple": "1",  "values": "query=SELECT id, nome AS descrizione FROM zz_groups ORDER BY id ASC", "value": "';
        $results = $dbo->fetchArray('SELECT GROUP_CONCAT(DISTINCT id_gruppo SEPARATOR \',\') AS gruppi FROM zz_group_view WHERE id_vista='.prepare($field['id']));

        echo $results[0]['gruppi'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Gruppi di utenti in grado di visualizzare questo campo').'" ]}
									</div>

									<div class="col-md-6">
										{[ "type": "select", "label": "'.tr('Visibilità').'", "name": "enabled['.$key.']", "values": "list=\"0\":\"'.tr('Nascosto (variabili di stato)').'\",\"1\": \"'.tr('Visibile nella sezione').'\"", "value": "'.$field['enabled'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Stato del campo: visibile nella tabella oppure nascosto').'" ]}
									</div>
								</div>

								<div class="row">
									<div class="col-md-3">
										{[ "type": "checkbox", "label": "'.tr('Ricercabile').'", "name": "search['.$key.']", "value": "'.$field['search'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Indica se il campo è ricercabile').'" ]}
									</div>

									<div class="col-md-3">
										{[ "type": "checkbox", "label": "'.tr('Ricerca lenta').'", "name": "slow['.$key.']", "value": "'.$field['slow'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr("Indica se la ricerca per questo campo è lenta (da utilizzare nel caso di evidenti rallentamenti, mostra solo un avviso all'utente").'" ]}
									</div>

									<div class="col-md-3">
										{[ "type": "checkbox", "label": "'.tr('Sommabile').'", "name": "sum['.$key.']", "value": "'.$field['summable'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Indica se il campo è da sommare').'" ]}
									</div>

                                    <div class="col-md-3">
										{[ "type": "checkbox", "label": "'.tr('Formattabile').'", "name": "format['.$key.']", "value": "'.$field['format'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Indica se il campo è formattabile in modo automatico').'" ]}
									</div>
								</div>

								<div class="row">
									<div class="col-md-6">
										{[ "type": "text", "label": "'.tr('Ricerca tramite').'", "name": "search_inside['.$key.']", "value": "'.$field['search_inside'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr('Query personalizzata per la ricerca (consigliata per colori e icone)').'.<br>'.tr('ATTENZIONE: utilizza sempre i caratteri < o > seguiti da spazio!').'" ]}
									</div>

									<div class="col-md-6">
										{[ "type": "text", "label": "'.tr('Ordina tramite').'", "name": "order_by['.$key.']", "value": "'.$field['order_by'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ', "help": "'.tr("Query personalizzata per l'ordinamento (date e numeri formattati tramite query)").'.<br>'.tr('ATTENZIONE: utilizza sempre i caratteri < o > seguiti da spazio!').'" ]}
									</div>
								</div>
							</div>
						</div>';
    }
    echo '
				</div>

                <div class="row">
                    <div class="text-right">
                        <button type="button" class="btn btn-info" id="add"><i class="fa fa-plus"></i> '.tr('Aggiungi nuovo campo').'</button>
                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
                    </div>
                </div>

				</div>
			</div>
		</div>

		<div class="col-md-3">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title">'.tr('Ordine di visualizzazione').'</h3>
				</div>

				<div class="panel-body sortable">';

    foreach ($fields as $field) {
        echo '
            <p data-id="'.$field['id'].'">
                <i class="fa fa-sort"></i>
                ';
        if ($field['enabled']) {
            echo '<strong class="text-success">'.$field['name'].'</strong>';
        } else {
            echo '<span class="text-danger">'.$field['name'].'</span>';
        }
        echo '
            </p>';
    }

    echo '
			</div>
		</div>
	</div>
</form>';

    echo '
<form class="hide" id="template">
	<div class="box">
		<div class="box-header with-border">
			<h3 class="box-title">'.tr('Nuovo campo').'</h3>
		</div>
		<div class="box-body">
			<div class="row">
				<input type="hidden" value="" name="id[-id-]">
				<div class="col-md-12">
					{[ "type": "text", "label": "'.tr('Nome').'", "name": "name[-id-]" ]}
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "'.tr('Query prevista').'", "name": "query[-id-]" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Gruppi con accesso').'", "name": "gruppi[-id-][]", "multiple": "1",  "values": "query=SELECT id, nome AS descrizione FROM zz_groups ORDER BY id ASC" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Visibilità').'", "name": "enabled[-id-]", "values": "list=\"0\":\"'.tr('Nascosto (variabili di stato)').'\",\"1\": \"'.tr('Visibile nella sezione').'\"" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "checkbox", "label": "'.tr('Ricercabile').'", "name": "search[-id-]" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "checkbox", "label": "'.tr('Ricerca lenta').'", "name": "slow[-id-]" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "checkbox", "label": "'.tr('Sommabile').'", "name": "sum[-id-]" ]}
				</div>

                <div class="col-md-3">
					{[ "type": "checkbox", "label": "'.tr('Formattabile').'", "name": "format[-id-]" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Ricerca tramite').'", "name": "search_inside[-id-]" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Ordina tramite').'", "name": "order_by[-id-]" ]}
				</div>
			</div>
		</div>
	</div>
</form>';

    echo '
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="filters">

    <div class="col-md-12">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Filtri per gruppo di utenti').'</h3>
            </div>

            <div class="panel-body">
                <!--div class="row">
                    <div class="text-right">
                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
                    </div>
                </div>
                <hr-->

                <div class="data">';

    $num = 0;
    $additionals = $dbo->fetchArray('SELECT * FROM zz_group_module WHERE idmodule='.prepare($record['id']).' ORDER BY `id` ASC');
    foreach ($additionals as $num => $additional) {
        $editable = !($additional['default'] && $enable_readonly);

        echo '
                    <div class="box ';
        if ($additional['enabled']) {
            echo 'box-success';
        } else {
            echo 'box-danger';
        }
        echo '">
                            <div class="box-header with-border">
                                <h3 class="box-title">
                                    <a data-toggle="collapse" href="#additional-'.$additional['id'].'">'.tr('Filtro _NUM_', [
                                        '_NUM_' => $num,
                                    ]).'</a>
                                </h3>';
        if ($editable) {
            echo '
                                <a class="btn btn-danger ask pull-right" data-backto="record-edit" data-op="delete_filter" data-id="'.$additional['id'].'">
                                    <i class="fa fa-trash"></i> '.tr('Elimina').'
                                </a>';
        }
        echo '
                                <a class="btn btn-warning ask pull-right" data-backto="record-edit" data-msg="'.($additional['enabled'] ? tr('Disabilitare questo elemento?') : tr('Abilitare questo elemento?')).'" data-op="change" data-id="'.$additional['id'].'" data-class="btn btn-lg btn-warning" data-button="'.($additional['enabled'] ? tr('Disabilita') : tr('Abilita')).'">
                                    <i class="fa fa-eye-slash"></i> '.($additional['enabled'] ? tr('Disabilita') : tr('Abilita')).'
                                </a>';
        echo '
                            </div>
                            <div id="additional-'.$additional['id'].'" class="box-body collapse">
                                <div class="row">
                                    <input type="hidden" value="'.$additional['id'].'" name="id['.$num.']">

                                    <div class="col-md-6">
                                        {[ "type": "textarea", "label": "'.tr('Query').'", "name": "query['.$num.']", "value": "'.$additional['clause'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ' ]}
                                    </div>

                                    <div class="col-md-3">
                                        {[ "type": "select", "label": "'.tr('Gruppo').'", "name": "gruppo['.$num.']",  "values": "query=SELECT id, nome AS descrizione FROM zz_groups ORDER BY id ASC", "value": "'.$additional['idgruppo'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ' ]}
                                    </div>

                                    <div class="col-md-3">
                                        {[ "type": "select", "label": "'.tr('Posizione').'", "name": "position['.$num.']", "values": "list=\"0\":\"'.tr('WHERE').'\",\"1\": \"'.tr('HAVING').'\"", "value": "'.$additional['position'].'"';
        if (!$editable) {
            echo ', "readonly": "1"';
        }
        echo ' ]}
                                    </div>
                                </div>
                            </div>
                        </div>';
    }
    echo '
                </div>

                <div class="row">
                    <div class="text-right">
                        <button type="button" class="btn btn-info" id="add"><i class="fa fa-plus"></i> '.tr('Aggiungi nuovo filtro').'</button>
                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>';

    echo '
<form class="hide" id="template_filter">
	<div class="box">
		<div class="box-header with-border">
			<h3 class="box-title">'.tr('Nuovo filtro').'</h3>
		</div>
		<div class="box-body">
			<div class="row">
				<input type="hidden" value="" name="id[-id-]">

				<div class="col-md-6">
					{[ "type": "textarea", "label": "'.tr('Query').'", "name": "query[-id-]" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Gruppo').'", "name": "gruppo[-id-]", "values": "query=SELECT id, nome AS descrizione FROM zz_groups ORDER BY id ASC" ]}
				</div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Posizione').'", "name": "position[-id-]",  "list=\"0\":\"'.tr('WHERE').'\",\"1\": \"'.tr('HAVING').'\"" ]}
			</div>
		</div>
	</div>
</form>';

    echo '
<script>
function testQuery(){
    $("#main_loading").fadeIn();

    $.ajax({
        url: "'.ROOTDIR.'/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=test",
        cache: false,
        type: "post",
        processData: false,
        contentType: false,
        dataType : "html",
        success: function(data) {
            $("#main_loading").fadeOut();

            swal("'.tr('Query funzionante').'", "'.tr('La query attuale funziona correttamente!').'", "success");
        },
        error: function(data) {
            $("#main_loading").fadeOut();

            swal("'.tr('Errore').'", "'.tr('Errore durante il test della query!').'", "error");
			session_set ("errors,0", 0, 1);
        }
    })
}

function replaceAll(str, find, replace) {
  return str.replace(new RegExp(find, "g"), replace);
}

$(document).ready(function(){
	var n = '.$key.';
	$(document).on("click", "#add", function(){
		$("#template .superselect, #template .superselectajax").select2().select2("destroy");
		n++;
		var text = replaceAll($("#template").html(), "-id-", "" + n);
		$(this).parent().parent().parent().find(".data").append(text);
		start_superselect();
	});

    var i = '.$num.';
	$(document).on("click", "#add_filter", function(){
		$("#template_filter .superselect, #template_filter .superselectajax").select2().select2("destroy");
		i++;
		var text = replaceAll($("#template_filter").html(), "-id-", "" + i);
		$(this).parent().parent().parent().find(".data").append(text);
		start_superselect();
	});

	$( ".sortable" ).disableSelection();
	$(".sortable").each(function() {
		$(this).sortable({
            axis: "y",
			cursor: "move",
			dropOnEmpty: true,
			scroll: true,
			start: function(event, ui) {
				ui.item.data("start", ui.item.index());
			},
			update: function(event, ui) {
				$.get("'.$rootdir.'/actions.php", {
					id: ui.item.data("id"),
					id_module: '.$id_module.',
					id_record: '.$id_record.',
					op: "update_position",
					start: ui.item.data("start"),
					end: ui.item.index()
				});
			}
		});
	});
});
</script>';
}
