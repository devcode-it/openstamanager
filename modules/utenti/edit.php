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

$utenti = $dbo->fetchArray('SELECT *, (SELECT ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=zz_users.idanagrafica ) AS ragione_sociale, (SELECT GROUP_CONCAT(descrizione SEPARATOR ", ") FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica=zz_users.idanagrafica GROUP BY idanagrafica) AS tipo FROM zz_users WHERE idgruppo='.prepare($record['id']));

echo '
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Utenti _GROUP_', [
                '_GROUP_' => $record['nome'],
            ]).'</h3>
		</div>

		<div class="panel-body">';

if (!empty($utenti)) {
    echo '
        <div class="table-responsive">
		<table class="table table-hover table-condensed table-striped">
		<tr>
			<th>'.tr('Nome utente').'</th>
			<th>'.tr('Ragione sociale').'</th>
            <th>'.tr('Tipo di anagrafica').'</th>
            <th>'.tr('Sedi').'</th>
			<th width="120">'.tr('Opzioni').'</th>
		</tr>';

    foreach ($utenti as $utente) {
        echo '
		<tr>
			<td '.(empty($utente['enabled']) ? ' style="text-decoration:line-through;"' : '').'>
			    <i class="fa fa-user"></i> '.$utente['username'].'
            </td>';

        if (!empty($utente['idanagrafica'])) {
            echo '
			<td>'.Modules::link('Anagrafiche', $utente['idanagrafica'], $utente['ragione_sociale']).'</td>
			<td>'.$utente['tipo'].'</td>';
        } else {
            echo '
			<td>-</td>
			<td>-</td>';
        }

        $sedi = $dbo->fetchOne('SELECT GROUP_CONCAT(nomesede SEPARATOR ", "  ) as nomesede FROM zz_user_sedi INNER JOIN ((SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, nomesede FROM an_sedi)) sedi ON zz_user_sedi.idsede=sedi.id WHERE id_user='.prepare($utente['id']).' GROUP BY id_user ')['nomesede'];

        echo '
            <td>'.$sedi.'</td>';

        echo '
            <td>';

        // Disabilitazione utente, se diverso da id_utente #1 (admin)
        if ($utente['id'] == '1') {
            echo '
            <div data-toggle="tooltip"  class="tip" title="'.tr("Non è possibile disabilitare l'utente admin").'" ><span class="btn btn-xs btn-default disabled">
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
                <a href="" data-href="'.$structure->fileurl('user.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_utente='.$utente['id'].'" class="btn btn-xs btn-warning tip" data-toggle="modal" title="'.tr('Aggiorna dati utente').'"  data-msg="" data-backto="record-edit" data-title="'.tr('Aggiorna dati utente').'"><i class="fa fa-unlock-alt"></i></a>';

        // Disabilitazione token API, se diverso da id_utente #1 (admin)
        $token = $dbo->fetchOne('SELECT `enabled` FROM `zz_tokens` WHERE `id_utente` = '.prepare($utente['id']).'')['enabled'];

        if ($utente['id'] == '1') {
            echo '
                <div data-toggle="tooltip" class="tip" title="'.tr("Non è possibile gestire l'accesso API per l'utente admin").'" ><span  class="btn btn-xs btn-default disabled">
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

        // Eliminazione utente, se diverso da id_utente #1 (admin)
        if ($utente['id'] == '1') {
            echo '
            <div data-toggle="tooltip" class="tip"  title="'.tr("Non è possibile eliminare l'utente admin").'" ><span class="btn btn-xs btn-default disabled">
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
            </table>
            </div>';
} else {
    echo '
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> '.tr('Non ci sono utenti in questo gruppo').'.
            </div>';
}

echo '
			<a data-toggle="modal" data-href="'.$structure->fileurl('user.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-msg="" data-backto="record-edit" data-title="'.tr('Aggiungi utente').'" class="pull-right btn btn-primary">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi utente').'
            </a>
		</div>
	</div>';

// Aggiunta nuovo utente
echo '
	<hr>';

echo '
	<div class="panel panel-primary">
		<div class="panel-heading">
            <h3 class="panel-title">'.tr('Permessi _GROUP_', [
                '_GROUP_' => $record['nome'],
            ]).((empty($record['editable'])) ? '<a class=\'clickable btn-xs pull-right ask\'  data-msg="'.tr('Verranno reimpostati i permessi di default per il gruppo \''.$record['nome'].'\' ').'." data-class="btn btn-lg btn-warning" data-button="'.tr('Reimposta permessi').'" data-op="restore_permission"  >'.tr('Reimposta permessi').'</a>' : '').'</h3>

		</div>

		<div class="panel-body">';
if ($record['nome'] != 'Amministratori') {
    echo '
			<div class="table-responsive">
            <table class="table table-hover table-condensed table-striped">
				<tr>
					<th>'.tr('Modulo').'</th>
					<th>'.tr('Permessi').'</th>
                </tr>';

    $moduli = Modules::getHierarchy();

    $permessi_disponibili = [
        '-' => tr('Nessun permesso'),
        'r' => tr('Sola lettura'),
        'rw' => tr('Lettura e scrittura'),
    ];

    for ($m = 0; $m < count($moduli); ++$m) {
        echo menuSelection($moduli[$m], $id_record, -1, $permessi_disponibili);
    }

    echo '
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
	</div>';

// Eliminazione gruppo (se non è tra quelli di default)
if ($record['editable'] == 1) {
    echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
            <a class="btn btn-danger ask" data-backto="record-list" data-msg="'.tr('Eliminando questo gruppo verranno eliminati anche i permessi e gli utenti collegati').'" data-op="deletegroup">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>
		</div>
	</div>';
}

echo '
<script>
$(document).ready(function() {
    $("#save").addClass("hide");

    $("#email-button").remove();
});

$("li.active.header button.btn-primary").attr("data-href", $("a.pull-right").attr("data-href") );

function update_permissions(id, value){
    $.get(
        globals.rootdir + "/actions.php?id_module='.$id_module.'&id_record='.$id_record.'&op=update_permission&idmodulo=" + id + "&permesso=" + value,
        function(data){
            if(data == "ok") {
                toastr["success"]("'.tr('Permessi aggiornati!').'");
            } else {
                swal("'.tr('Errore').'", "'.tr("Errore durante l'aggiornamento dei permessi!").'", "error");
            }
        }
    );
}
</script>';
