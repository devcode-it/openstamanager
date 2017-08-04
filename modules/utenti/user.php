<?php

include_once __DIR__.'/../../core.php';

// Decido la lunghezza minima della password, e la lunghezza minima del nome utente
$min_length = 8;
$min_length_username = 4;
$idgruppo = intval(filter('idgruppo'));
$id_utente = filter('idutente');
if (!empty($id_utente)) {
    $value = 'change_pwd';

    $rs = $dbo->fetchArray('SELECT username FROM zz_users WHERE idutente='.prepare($id_utente));
    $username = $rs[0]['username'];
    $message = _('Modifica');
} else {
    $value = 'adduser';
    $username = '';
    $message = _('Aggiungi');
}

echo '
<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Modules::getModule('Utenti e permessi')['id'].'&id_record='.$idgruppo.'" method="post">
	<input type="hidden" name="op" value="'.$value.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="min_length" value="'.$min_length.'">
	<input type="hidden" name="min_length_username" value="'.$min_length_username.'">';
if (!empty($id_utente)) {
    echo '
    <input type="hidden" name="idutente" value="'.$id_utente.'">';
}
echo '

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "text", "label": "'._('Username').'", "name": "username", "required": 1, "value": "'.$rs[0]['username'].'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "password", "label": "'._('Password').'", "name": "password1", "required": 1, "value": "" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "password", "label": "'._('Ripeti la password').'", "name": "password2", "value": "" ]}
		</div>
	</div>';

if (empty($id_utente)) {
    echo '

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "select", "label": "'._('Collega ad una anagrafica').'", "name": "idanag", "values": "query=SELECT CONCAT(`an_tipianagrafiche`.`idtipoanagrafica`, \'-\', `an_anagrafiche`.`idanagrafica`) AS \'id\', `ragione_sociale` AS \'descrizione\', `descrizione` AS \'optgroup\' FROM `an_tipianagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` ORDER BY `descrizione` ASC", "value": "" ]}
		</div>
	</div>';
}

echo '

	<button type="button" onclick="do_submit()" class="btn btn-primary"><i class="fa fa-plus"></i> '.$message.'</button>
</form>
<script type="text/javascript">
	var min_length = '.$min_length.';
	var min_length_username = '.$min_length_username.';
	function do_submit(){
		if( $("#password1").val() == "" || $("#password2").val() == "" )
			alert("'._('Inserire una password valida').'.");
		else if( $("#password1").val() != $("#password2").val() )
			alert("'._('Le password non coincidono').'.");
		else if( $("#password1").val().length < min_length )
			alert("'.str_replace('_MIN_', $min_length, _('La password deve essere lunga minimo _MIN_ caratteri!')).'");
		else if( $("#username").val().length < min_length_username )
			alert("'.str_replace('_MIN_', $min_length_username, _("L'username deve essere lungo minimo _MIN_ caratteri!")).'");
		else
			$("#link_form").submit();
	}
</script>

<script src="'.$rootdir.'/lib/init.js"></script>';
