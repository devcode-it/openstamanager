<?php

include_once __DIR__.'/../../core.php';

$record = $records[0];

$moduli = $dbo->fetchArray('SELECT * FROM zz_modules WHERE parent IS NULL ORDER BY `order` ASC');

$utenti = $dbo->fetchArray('SELECT *, (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE an_anagrafiche.idanagrafica=zz_users.idanagrafica AND an_tipianagrafiche_anagrafiche.idtipoanagrafica=zz_users.idtipoanagrafica) AS ragione_sociale, (SELECT descrizione FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica=zz_users.idanagrafica AND an_tipianagrafiche.idtipoanagrafica=zz_users.idtipoanagrafica) AS tipo FROM zz_users WHERE idgruppo='.prepare($record['id']));

echo '
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Utenti _GROUP_', [
                '_GROUP_' => $records[0]['nome'],
            ]).'</h3>
		</div>

		<div class="panel-body">';
if (!empty($utenti)) {
    echo '
		<table class="table table-hover table-condensed table-striped">
		<tr>
			<th>'.tr('Nome utente').'</th>
			<th>'.tr('Ragione sociale').'</th>
			<th>'.tr('Tipo di anagrafica').'</th>
			<th>'.tr('Opzioni').'</th>
		</tr>';

    foreach ($utenti as $utente) {
        echo '
		<tr>
			<td';
        if ($utente['enabled'] == 0) {
            echo ' style="text-decoration:line-through;"';
        }
        echo '><i class="fa fa-user"></i> '.$utente['username'].'</td>';
        if ($utente['idanagrafica'] != 0) {
            echo '
			<td>'.Modules::link('Anagrafiche', $utente['idanagrafica'], $utente['ragione_sociale']).'</td>
			<td>'.$utente['tipo'].'</td>';
        } else {
            echo '
			<td>-</td>
			<td>-</td>';
        }
        /*
         * Funzioni per gli utenti
         */
        echo '
			<td>';
        // Disabilitazione utente, se diverso da id_utente #1 (admin)
        if ($utente['id'] != '1') {
            if ($utente['enabled'] == 1) {
                echo '
				<a href="javascript:;" onclick="if( confirm(\''.tr('Disabilitare questo utente?').'\') ){ location.href=\''.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=disable&id_utente='.$utente['id'].'&idgruppo='.$record['id'].'\'; }" title="Disabilita utente" class="text-danger tip"><i class="fa fa-2x fa-eye-slash"></i></a>';
            } else {
                echo '
				<a href="javascript:;" onclick="if( confirm(\'Abilitare questo utente?\') ){ location.href=\''.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=enable&id_utente='.$utente['id'].'&idgruppo='.$record['id'].'\'; }" title="Abilita utente" class="text-success tip"><i class="fa fa-2x fa-eye"></i></a>';
            }
        } else {
            echo '
				<a href="javascript:;" onclick="alert(\"'.tr("Non è possibile disabilitare l'utente admin").'\")" class="text-muted tip"><i class="fa fa-2x fa-eye-slash"></i></<>';
        }

        // Cambio password e nome utente
        echo '
				<a href="" data-href="'.$rootdir.'/modules/'.Modules::getModule($id_module)['directory'].'/user.php?id_utente='.$utente['id'].'&idgruppo='.$record['id'].'" class="text-warning tip" data-toggle="modal" data-target="#bs-popup" title="Aggiorna dati utente""  data-title="Aggiorna dati utente"><i class="fa fa-2x fa-unlock-alt"></i></a>';

        // Eliminazione utente, se diverso da id_utente #1 (admin)
        if ($utente['id'] != '1') {
            echo '
			<a href="javascript:;" onclick="if( confirm(\'Sei sicuro di voler eliminare questo utente?\') ){ location.href=\''.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=delete&id_utente='.$utente['id'].'&idgruppo='.$record['id'].'\'; }" title="Elimina utente" class="text-danger tip"><i class="fa fa-2x fa-trash"></i></a>';
        } else {
            echo '
			<span onclick="alert(\"'.tr("Non è possibile eliminare l'utente admin").'\")" class="text-muted tip"><i class="fa fa-2x fa-trash"></i></span>';
        }

        echo '
				</td>
			</tr>';
    }

    echo '
			</table>';
} else {
    echo '
			<p>'.tr('Non ci sono utenti in questo gruppo').'...</p>';
}
echo '
			<a data-toggle="modal" data-target="#bs-popup" data-href="'.$rootdir.'/modules/utenti/user.php?idgruppo='.$record['id'].'" data-title="'.tr('Aggiungi utente').'" class="pull-right btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi utente').'</a>
		</div>
	</div>';

// Aggiunta nuovo utente
echo '
	<hr>';

echo '
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Permessi').'</h3>
		</div>

		<div class="panel-body">';
if ($record['nome'] != 'Amministratori') {
    echo '
			<table class="table table-hover table-condensed table-striped">
				<tr>
					<th>'.tr('Modulo').'</th>
					<th>'.tr('Permessi').'</th>
				</tr>';
    for ($m = 0; $m < count($moduli); ++$m) {
        $perms_values = ['-', 'r', 'rw'];
        $perms_names = [tr('Nessun permesso'), tr('Sola lettura'), tr('Lettura e scrittura')];
        echo menuSelection($moduli[$m], -1, $perms_values, $perms_names);
    }

    echo '
			</table>';
} else {
    echo '
			<p>'.tr('Gli amministratori hanno accesso a qualsiasi modulo').'.</p>';
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
    function update_permissions(id, value){
        $.get(
            globals.rootdir + "/actions.php?id_module='.$id_module.'&id_record='.$id_record.'&op=update_permission&idmodulo=" + id + "&permesso=" + value,
            function(data){
                if(data == "ok"){
                    swal("'.tr('Salvataggio completato').'", "'.tr('Permessi aggiornati!').'", "success");
                }
                else{
                    swal("'.tr('Errore').'", "'.tr("Errore durante l'aggiornamento dei permessi!").'", "error");
                }
            }
        );
    }
</script>';
