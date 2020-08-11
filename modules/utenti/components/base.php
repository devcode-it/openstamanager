<?php

include_once __DIR__.'/../../core.php';

if (!empty(filter('idanagrafica'))) {
    $utente['id_anagrafica'] = filter('idanagrafica');
}

$_SESSION['superselect']['idanagrafica'] = $utente['id_anagrafica'];

echo '
	<div class="row">
		<div class="col-md-12">
		{[ "type": "text", "label": "'.tr('Username').'", "name": "username", "required": 1, "value": "'.$utente['username'].'", "validation": "username||'.($utente['id'] ?: 0).'" ]}
		</div>
    </div>';

echo '

    <div class="row">
		<div class="col-md-12">
		{[ "type": "text", "label": "'.tr('Email').'", "name": "email", "required": 0, "value": "'.$utente['email'].'" ]}
		</div>
    </div>';

    echo '

	<div class="row">
		<div class="col-md-12">
		{[ "type": "select", "label": "'.tr('Collega ad una anagrafica').'", "name": "idanag", "required": 1, "ajax-source": "anagrafiche_utenti", "value": "'.$utente['id_anagrafica'].'", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].(isset($gruppo) ? '|tipoanagrafica='.$gruppo : '').'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
		    {[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede[]", "ajax-source": "sedi_azienda", "multiple": "1", "value":"'.($sedi ?: '').'", "help": "'.tr('Sede Azienda abilitata per la movimentazione degli articoli.').'" ]}
		</div>
	</div>';

echo '
    <script type="text/javascript">
        $(document).ready(function() {
            $("#idanag").change(function() {
                session_set("superselect,idanagrafica", $(this).val(), 0);

                $("#idsede").selectReset();
            })
        });
    </script>';
