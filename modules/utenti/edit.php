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

$group = Group::find($id_record);

// Se il gruppo non è trovato, mostra un errore e termina
if (!$group || !$record) {
    echo '
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle"></i> '.tr('Gruppo non trovato o non valido').'.
    </div>';

    return;
}
$record = $group->toArray();

// Lettura gruppi
$gruppi = $dbo->fetchArray('SELECT `id`, `nome` FROM `zz_groups`');

// Lettura utenti
$utenti = $dbo->fetchArray('
    SELECT
        `zz_users`.*,
        `an_anagrafiche`.`ragione_sociale`,
        GROUP_CONCAT(`an_tipianagrafiche_lang`.`title` SEPARATOR ", ") AS tipo
    FROM
        `zz_users`
        LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica`
        LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica`
        LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
        LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        `zz_users`.`idgruppo` = '.prepare($record['id']).'
        AND `zz_users`.`deleted_at` IS NULL
    GROUP BY
        `zz_users`.`id`');

echo '
	<div class="card card-primary card-outline">
		<div class="card-header">
			<h3 class="card-title">
                <i class="fa fa-users mr-2"></i>'.tr('Utenti del gruppo: _GROUP_', [
    '_GROUP_' => '<span class="text-primary">'.$group->getTranslation('title').'</span>',
]).'</h3>
            <div class="card-tools">
                <a data-card-widget="modal" data-href="'.$structure->fileurl('user.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-msg="" data-backto="record-edit" data-title="'.tr('Aggiungi utente').'" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi utente').'
                </a>
            </div>
		</div>

		<div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fa fa-home mr-1"></i> '.tr('Modulo iniziale').'</label>
                        {["type":"select", "name":"id_module_start", "ajax-source":"moduli_gruppo", "select-options": '.json_encode(['idgruppo' => $group->id]).', "placeholder":"'.tr('Modulo iniziale').'", "value":"'.($group->id_module_start ?: 0).'" ]}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fa fa-palette mr-1"></i> '.tr('Tema').'</label>
                        {["type":"select", "name":"theme", "values":"list=\"\": \"'.tr('Predefinito').'\",\"black-light\": \"'.tr('Bianco').'\",\"red-light\": \"'.tr('Rosso chiaro').'\",\"red\": \"'.tr('Rosso').'\",\"blue-light\": \"'.tr('Blu chiaro').'\",\"blue\": \"'.tr('Blu').'\",\"info-light\": \"'.tr('Azzurro chiaro').'\",\"info\": \"'.tr('Azzurro').'\",\"green-light\": \"'.tr('Verde chiaro').'\",\"green\": \"'.tr('Verde').'\",\"yellow-light\": \"'.tr('Giallo chiaro').'\",\"yellow\": \"'.tr('Giallo').'\",\"purple-light\": \"'.tr('Viola chiaro').'\",\"purple\": \"'.tr('Viola').'\" ", "value":"'.$group->theme.'" ]}
                    </div>
                </div>
            </div>';

if (!empty($utenti)) {
    echo '
        <div class="table-responsive">
			<table class="table table-hover table-sm table-striped">
            <thead>
			<tr>
				<th><i class="fa fa-user mr-1"></i>'.tr('Nome utente').'</th>
                <th><i class="fa fa-envelope mr-1"></i>'.tr('Email').'</th>
				<th><i class="fa fa-building mr-1"></i>'.tr('Ragione sociale').'</th>
                <th><i class="fa fa-tag mr-1"></i>'.tr('Tipo di anagrafica').'</th>
                <th><i class="fa fa-map-marker-alt mr-1"></i>'.tr('Sedi').'</th>
				<th width="140" class="text-center"><i class="fa fa-cog mr-1"></i>'.tr('Opzioni').'</th>
			</tr>
            </thead>
            <tbody>';

    foreach ($utenti as $utente) {
        $status_class = empty($utente['enabled']) ? 'text-muted' : '';
        echo '
			<tr>
				<td class="'.$status_class.'">
				    <i class="fa fa-user '.($status_class ? '' : 'text-primary').'"></i> '.$utente['username'].'
                    '.(!empty($status_class) ? '<span class="badge badge-danger">'.tr('Disabilitato').'</span>' : '').'
	            </td>';

        if (!empty($utente['email'])) {
            echo '
            <td>'.$utente['email'].'</td>';
        } else {
            echo '
            <td><span class="text-muted">-</span></td>';
        }

        if (!empty($utente['idanagrafica'])) {
            echo '
				<td>'.Modules::link('Anagrafiche', $utente['idanagrafica'], $utente['ragione_sociale']).'</td>
				<td>'.$utente['tipo'].'</td>';
        } else {
            echo '
				<td><span class="text-muted">-</span></td>
				<td><span class="text-muted">-</span></td>';
        }

        $sedi = $dbo->fetchOne('SELECT GROUP_CONCAT(nomesede SEPARATOR ", "  ) as nomesede FROM zz_user_sedi INNER JOIN ((SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, nomesede FROM an_sedi)) sedi ON zz_user_sedi.idsede=sedi.id WHERE id_user='.prepare($utente['id']).' GROUP BY id_user')['nomesede'];

        echo '
            <td>'.(!empty($sedi) ? $sedi : '<span class="text-muted">-</span>').'</td>';

        echo '
            <td class="text-center">';

        // Disabilitazione utente, se diverso da id_utente #1 (admin)
        if ($utente['id'] == '1') {
            echo '
            <div data-card-widget="tooltip" class="tip d-inline-block" title="'.tr("Non è possibile disabilitare l'utente admin").'" ><span class="btn btn-xs btn-danger disabled">
                    <i class="fa fa-eye-slash"></i>
                </span></div>';
        } elseif ($utente['enabled'] == 1) {
            echo '
                <a title="'.tr('Disabilita utente').'" class="btn btn-xs btn-danger tip ask" data-msg="" data-backto="record-edit" data-title="'.tr('Disabilitare questo utente?').'" data-op="disable_user" data-id_utente="'.$utente['id'].'" data-button="'.tr('Disabilita').'">
                    <i class="fa fa-eye-slash"></i>
                </a>';
        } else {
            echo '
                <a title="'.tr('Abilita utente').'" class="btn btn-xs btn-success tip ask" data-msg="" data-backto="record-edit" data-title="'.tr('Abiltare questo utente?').'" data-op="enable_user" data-id_utente="'.$utente['id'].'" data-button="'.tr('Abilita').'" data-class="btn btn-lg btn-warning">
                    <i class="fa fa-eye"></i>
                </a>';
        }

        // Cambio password e nome utente
        echo '
                <a title="'.tr('Aggiorna dati utente').'" class="btn btn-xs btn-warning tip" data-msg=""data-backto="record-edit" data-title="'.tr('Aggiorna dati utente').'" data-href="'.$structure->fileurl('user.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_utente='.$utente['id'].'" data-card-widget="modal"><i class="fa fa-unlock-alt"></i></a>';

        // Disabilitazione token API, se diverso da id_utente #1 (admin)
        $token = $dbo->fetchOne('SELECT `enabled` FROM `zz_tokens` WHERE `id_utente` = '.prepare($utente['id']).'')['enabled'];

        if ($utente['id'] == '1') {
            echo '
                <div data-card-widget="tooltip" class="tip d-inline-block" title="'.tr("Non è possibile gestire l'accesso API per l'utente admin").'" ><span  class="btn btn-xs btn-danger disabled">
                    <i class="fa fa-key "></i>
                </span></div>';
        } elseif (!empty($token)) {
            echo '
                <a title="'.tr('Disabilita API').'" class="btn btn-xs btn-danger tip ask" data-msg="" data-backto="record-edit" data-title="'.tr("Disabilitare l'accesso API per questo utente?").'" data-op="token_disable" data-id_utente="'.$utente['id'].'" data-button="'.tr('Disabilita').'">
                    <i class="fa fa-key"></i>
                </a>';
        } else {
            echo '
                <a title="'.tr('Abilitare API').'" class="btn btn-xs btn-success tip ask" data-msg="" data-backto="record-edit" data-title="'.tr("Abilitare l'accesso API per questo utente?").'" data-op="token_enable" data-id_utente="'.$utente['id'].'" data-button="'.tr('Abilita').'" data-class="btn btn-lg btn-warning">
                    <i class="fa fa-key"></i>
                </a>';
        }

        $token_otp_data = $dbo->fetchOne('SELECT `enabled`, `valido_dal`, `valido_al` FROM `zz_otp_tokens` WHERE `id_utente` = '.prepare($utente['id']));

        $is_not_active = false;
        if (!empty($token_otp_data['valido_dal']) && !empty($token_otp_data['valido_al'])) {
            $is_not_active = strtotime((string) $token_otp_data['valido_dal']) > time() || strtotime((string) $token_otp_data['valido_al']) < time();
        }
        if (!empty($token_otp_data['valido_dal']) && empty($token_otp_data['valido_al'])) {
            $is_not_active = strtotime((string) $token_otp_data['valido_dal']) > time();
        }
        if (empty($token_otp_data['valido_dal']) && !empty($token_otp_data['valido_al'])) {
            $is_not_active = strtotime((string) $token_otp_data['valido_al']) < time();
        }

        $is_otp_enabled = !empty($token_otp_data['enabled']) && !$is_not_active;

        if ($is_otp_enabled) {
            echo '
                <a title="'.tr('Gestione login tramite OTP').'" class="btn btn-xs btn-success tip" onclick="launch_modal(\''.tr('Gestione login tramite OTP').'\', \''.base_path_osm().'/modules/utenti/components/gestione_otp.php?id_module='.$id_module.'&id_record='.$id_record.'&id_utente='.$utente['id'].'\')">
                    <i class="fa fa-link"></i>
                </a>';
        } else {
            echo '
                <a title="'.tr('Abilita login tramite OTP').'" class="btn btn-xs btn-primary tip" onclick="launch_modal(\''.tr('Gestione login tramite OTP').'\', \''.base_path_osm().'/modules/utenti/components/gestione_otp.php?id_module='.$id_module.'&id_record='.$id_record.'&id_utente='.$utente['id'].'\')">
                    <i class="fa fa-link"></i>
                </a>';
        }

        // Eliminazione utente, se diverso da id_utente #1 (admin)
        if ($utente['id'] == '1') {
            echo '
            <div data-card-widget="tooltip" class="tip d-inline-block"  title="'.tr("Non è possibile eliminare l'utente admin").'" ><span class="btn btn-xs btn-danger disabled">
                    <i class="fa fa-trash"></i>
                </span></div>';
        } else {
            echo '
                <a title="Elimina utente" class="btn btn-xs btn-danger tip ask" data-msg="" data-backto="record-edit" data-title="'.tr('Eliminare questo utente?').'" data-op="delete_user" data-id_utente="'.$utente['id'].'">
                    <i class="fa fa-trash"></i>
                </a>';
        }

        echo '
					</td>
				</tr>';
    }

    echo '
            </tbody>
            </table>
            </div>';
} else {
    echo '
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> '.tr('Non ci sono utenti in questo gruppo').'.
            </div>';
}

echo '
		</div>
	</div>';

// Aggiunta nuovo utente
echo '
	<hr>

<div class="row">
	<div class="col-md-6 mx-auto">
		<div class="card card-primary card-outline">
			<div class="card-header">
				<h3 class="card-title">
					<i class="fa fa-lock mr-2"></i>'.tr('Permessi del gruppo: _GROUP_', [
    '_GROUP_' => '<span class="text-primary">'.$record['nome'].'</span>',
]).'</h3>'.((empty($record['editable']) && ($record['nome'] != 'Amministratori')) ? '
				<div class="card-tools">
					<btn type="button" class="btn clickable btn-sm btn-warning float-right ask" data-msg="<small>'.tr('Verranno reimpostati i permessi di default per il gruppo '.$record['nome']).'.</small>" data-class="btn btn-warning" data-button="'.tr('Reimposta permessi').'" data-op="restore_permission">'.tr('Reimposta permessi').'</btn>
				</div>' : '').'
			</div>

			<div class="card-body">';
if ($record['nome'] != 'Amministratori') {
    echo '
					<div class="table-responsive">
					<table class="table table-hover table-sm table-striped">
						<thead>
						<tr>
							<th style="padding: 4px 8px;"><i class="fa fa-cube"></i> '.tr('Modulo').'</th>
							<th style="padding: 4px 8px;"><i class="fa fa-shield-alt"></i> '.tr('Permessi').'</th>
						</tr>
						</thead>
						<tbody>';

    $moduli = Modules::getHierarchy(true);

    $permessi_disponibili = [
        '-' => tr('Nessun permesso'),
        'r' => tr('Sola lettura'),
        'rw' => tr('Lettura e scrittura'),
    ];

    for ($m = 0; $m < count($moduli); ++$m) {
        echo menuSelection($moduli[$m], $id_record, -1, $permessi_disponibili);
    }

    echo '
                </tbody>
					</table>
					</div>';
} else {
    echo '
					<div class="alert alert-info">
						<i class="fa fa-info-circle"></i> '.tr('Gli amministratori hanno accesso a qualsiasi modulo').'.
					</div>';
}
echo '
				</div>
			</div>
		</div>
	</div>';

// Eliminazione gruppo (se non è tra quelli di default)

echo '
<!-- PULSANTI -->
<div class="row">
    <div class="col-md-12 text-right">
        <a class="btn btn-danger ask '.(!$record['editable'] ? 'disabled' : '').'" '.(!$record['editable'] ? 'disabled' : '').' data-backto="record-list" data-msg="'.tr('Eliminando questo gruppo verranno eliminati anche i permessi e gli utenti collegati').'" data-op="deletegroup">
            <i class="fa fa-trash"></i> '.tr('Elimina').'
        </a>
    </div>
</div>';

echo '
<script>
$(document).ready(function() {
    $("#save-buttons").hide();

    $("#email-button").remove();

    setTimeout(function() { colorize_select2(); }, 500);

});

function colorize_select2(){
    // Colora i valori selezionati nei select
    $(".select2-selection__rendered").each(function() {
        var title = $(this).attr("title");
        var select = $(this).closest(".select2-container").prev("select");
        var value = select.val();

        $(this).removeClass("text-green text-orange text-red");

        if (title == "Lettura e scrittura" || value == "rw"){
            $(this).addClass("text-green");
        }
        else if (title == "Sola lettura" || value == "r"){
            $(this).addClass("text-orange");
        }
        else if (title == "Nessun permesso" || value == "-"){
            $(this).addClass("text-red");
        }
    });
}


$("li.active.header button.btn-primary").attr("data-href", $("a.pull-right").attr("data-href") );

function update_permissions(id, value, color){

    $.get(
        globals.rootdir + "/actions.php?id_module='.$id_module.'&id_record='.$id_record.'&op=update_permission&idmodulo=" + id + "&permesso=" + value,
        function(data){
            if(data == "ok") {

                toastr["success"]("'.tr('Permessi aggiornati!').'");
                content_was_modified = false;

                $("#select2-permesso_"+id+"-container").removeClass("text-red");
                $("#select2-permesso_"+id+"-container").removeClass("text-orange");
                $("#select2-permesso_"+id+"-container").removeClass("text-green");
                $("#select2-permesso_"+id+"-container").addClass(color);


                if( id==$("#id_module_start").val() && value=="-" ){
                    $("#id_module_start").selectReset();
                    update_id_module_start($("#id_module_start").val());
                }

            } else {
                swal("'.tr('Errore').'", "'.tr("Errore durante l'aggiornamento dei permessi!").'", "error");
            }
        }
    );
}
function applySidebarTheme(theme){
    var sidebar = $(".main-sidebar");
    if (!sidebar.length) {
        return;
    }

    var classes = (sidebar.attr("class") || "").split(/\s+/);
    var filtered = $.grep(classes, function(cls) {
        return cls && cls !== "sidebar-dark-secondary" && cls.indexOf("bg-") !== 0;
    });

    if (theme) {
        filtered.push("bg-" + theme);
    } else {
        filtered.push("sidebar-dark-secondary");
    }

    sidebar.attr("class", filtered.join(" "));
}

$("#id_module_start").change(function(){
    update_id_module_start($(this).val());
});

$("#theme").change(function(){
    var themeValue = $(this).val();
    applySidebarTheme(themeValue);
    update_theme(themeValue);
});

function update_id_module_start(value){
    $.get(
        globals.rootdir + "/actions.php?id_module='.$id_module.'&id_record='.$id_record.'&op=update_id_module_start&id_module_start=" + value,
        function(data){
            if(data == "ok") {
                toastr["success"]("'.tr('Modulo iniziale aggiornato!').'");
                content_was_modified = false;
            } else {
                swal("'.tr('Errore').'", data, "error");
            }
        }
    );
}

function update_theme(value){
    $.get(
        globals.rootdir + "/actions.php?id_module='.$id_module.'&id_record='.$id_record.'&op=update_theme&theme=" + encodeURIComponent(value || ""),
        function(data){
            if(data == "ok") {
                toastr["success"]("'.tr('Tema aggiornato!').'");
                content_was_modified = false;
            } else {
                swal("'.tr('Errore').'", data, "error");
            }
        }
    );
}
</script>';
