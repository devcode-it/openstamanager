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

use Models\Group;
use Models\Module;
use Models\Upload;
use Models\User;

Permissions::check('rw');

$id_utente = filter('id_utente');
$user = User::find($id_utente);
$utente = $user ? $user->toArray() : [];

// Gruppo della selezione
if (!empty($id_record)) {
    $gruppo_utente = $user ? $user->group->getTranslation('title') : 0;

    $gruppi = [
        'Clienti' => 'Cliente',
        'Tecnici' => 'Tecnico',
        'Agenti' => 'Agente',
    ];
    $gruppo = $gruppi[$gruppo_utente];
}

// Preseleziona il gruppo quando si aggiunge un nuovo utente dal contesto di un gruppo
if (!empty(filter('idanagrafica'))) {
    $utente['id_anagrafica'] = filter('idanagrafica');
} else {
    $current_idgruppo = !empty($id_record) ? Group::find($id_record)->id : null;
}

// Lettura sedi dell'utente già impostate
if (!empty($user)) {
    $sedi = $dbo->fetchOne('SELECT GROUP_CONCAT(idsede) as sedi FROM zz_user_sedi WHERE id_user='.prepare($id_utente).' GROUP BY id_user')['sedi'];
}

// Verifica se si sta creando un utente per il gruppo Tecnici
$is_new_user = empty($user);
$is_tecnici_group = false;
if (!empty($id_record)) {
    $current_group = Group::find($id_record);
    $is_tecnici_group = $current_group && $current_group->getTranslation('title') == 'Tecnici';
}

echo '
<form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'"  method="post" enctype="multipart/form-data" id="user_update">
	<input type="hidden" name="op" value="update_user">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_utente" value="'.$utente['id'].'">

	<!-- Contenitore per avviso dinamico -->
	<div id="anagrafica-warning" style="display: none;">
	    <div class="alert alert-info">
	        <i class="fa fa-exclamation-triangle"></i> <span id="warning-message"> '.tr('Attenzione: per poter utilizzare l\'applicazione mobile, questo utente deve essere associato ad un\'anagrafica di tipo <strong>Tecnico</strong>').'.</span>
	    </div>
	</div>


	<div class="row">
		<div class="col-md-3">';

// Photo component
$user_photo = null;
if (!empty($user) && !empty($user->image_file_id)) {
    $upload = Upload::find($user->image_file_id);
    if ($upload) {
        $user_photo = $rootdir.'/files/utenti/'.$upload->filename;
    }
}

if ($user_photo) {
    echo '
			<div class="text-center mb-2">
				<img src="'.$user_photo.'" class="img-responsive" style="max-width:100px; max-height:100px; margin:0 auto;" alt="'.$user['username'].'" />
			</div>';
}

echo '
			{[ "type": "file", "label": "'.tr('Foto utente').'", "name": "photo", "help": "'.tr('Dimensione consigliata 100x100 pixel').'" ]}
		</div>

		<div class="col-md-9">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Gruppo di appartenenza').'", "name": "idgruppo", "required": 1, "ajax-source": "gruppi", "value": "'.(!empty($utente['idgruppo']) ? $utente['idgruppo'] : $current_idgruppo).'", "readonly": "'.(($utente['id'] == '1') || (!empty($id_record) && empty($utente['id'])) ? 1 : 0).'" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "class": "email-mask", "label": "'.tr('Email').'", "name": "email", "required": 0, "value": "'.$utente['email'].'", "validation": "email" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Username').'", "name": "username", "required": 1, "value": "'.$utente['username'].'", "validation": "username|'.$id_module.'|'.($utente['id'] ?: 0).'" ]}
				</div>';

if (!empty($user)) {
    echo '
				<div class="col-md-6">
					{[ "type": "password", "label": "'.tr('Password').'", "name": "password", "strength": "#submit-button", "disabled": "1" ]}
					<div class="row">
						<div class="col-md-12 text-right">
							<div class="form-check">
								<input type="checkbox" class="form-check-input" id="change_password" name="change_password" value="1">
								<label class="form-check-label" for="change_password">'.tr('Cambia password').'</label>
							</div>
						</div>
					</div>
				</div>';
} else {
    echo '
				<div class="col-md-6">
					{[ "type": "password", "label": "'.tr('Password').'", "name": "password", "strength": "#submit-button" ]}
				</div>';
}

echo '
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Collega ad una anagrafica').'", "name": "idanag", "required": 1, "ajax-source": "anagrafiche_utenti", "value": "'.$utente['id_anagrafica'].'", "icon-after": "add|'.Module::where('name', 'Anagrafiche')->first()->id.(isset($gruppo) ? '|tipoanagrafica='.$gruppo : '').'" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede[]", "ajax-source": "sedi_azienda", "multiple": "1", "value":"'.$sedi.'", "help": "'.tr('Sede Azienda abilitata per la movimentazione degli articoli. L\'impostazione non viene considerata per gli utenti del gruppo \'Amministratori\'.').'" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" onclick="submitCheck()" class="btn btn-primary" id="submit-button">';

if (empty($user)) {
    echo '
				<i class="fa fa-plus"></i> '.tr('Aggiungi');
} else {
    echo '
				<i class="fa fa-edit"></i> '.tr('Modifica');
}

echo '
			</button>
		</div>
	</div>
</form>

<script>
function submitCheck() {
    var username = parseInt($("#username").attr("valid"));

    if(username) {
        $("#user_update").submit();
    }else{
        $("input[name=username]").focus();
        swal("'.tr('Impossibile procedere').'", "'.tr('Username già esistente o troppo corto').'", "error");
    }
}

// Script per gestire il cambio dell\'anagrafica
$(document).ready(function() {
    $("#idanag").change(function() {
        session_set("superselect,idanagrafica", $(this).val(), 0);
        $("#idsede").selectReset();

        // Verifica tipo anagrafica per utenti tecnici
        var isNewUser = '.($is_new_user ? 'true' : 'false').';
        var isTecniciGroup = '.($is_tecnici_group ? 'true' : 'false').';

        if (isNewUser && isTecniciGroup) {
            if ($(this).val()) {
                // Anagrafica selezionata - verifica il tipo
                $.ajax({
                    url: "'.base_path().'/ajax_complete.php",
                    type: "GET",
                    data: {
                        module: "Utenti",
                        op: "check_anagrafica_tipo",
                        idanagrafica: $(this).val()
                    },
                    success: function(data) {
                        try {
                            var result = JSON.parse(data);
                            if (!result.is_tecnico) {
                                // Aggiorna il messaggio con il tipo effettivo
                                var tipoEffettivo = result.tipi.join(", ");
                                var messaggio = "'.tr('Attenzione: per poter utilizzare l\'applicazione mobile, questo utente deve essere associato ad un\'anagrafica di tipo <strong>Tecnico</strong>').'. '.tr('L\'anagrafica selezionata è di tipo').': <strong>" + tipoEffettivo + "</strong>.";
                                $("#warning-message").html(messaggio);
                                // Mostra warning se non è tecnico
                                $("#anagrafica-warning").show();
                            } else {
                                // Nascondi warning se è tecnico
                                $("#anagrafica-warning").hide();
                            }
                        } catch (e) {
                            console.error("Errore parsing JSON:", e);
                        }
                    },
                    error: function() {
                        console.error("Errore nella verifica del tipo anagrafica");
                    }
                });
            } else {
                // Nessuna anagrafica selezionata - nascondi warning
                $("#anagrafica-warning").hide();
            }
        }
    });';

if (!empty($user)) {
    echo '
    function no_check_pwd(){
        $("#password").attr("disabled", true);
        $("#submit-button").attr("disabled", false).removeClass("disabled");
    }

    $("#modals > div").on("shown.bs.modal", function () {
        no_check_pwd();
    });

    $("#change_password").change(function() {
        if (this.checked) {
            $("#password").attr("disabled", false);
            $("#password").change();
        } else {
            no_check_pwd();
        }
    });

    no_check_pwd();';
}

echo '
    init();
});
</script>';
