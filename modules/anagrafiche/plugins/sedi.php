<?php

include_once __DIR__.'/../../../core.php';

$id_record = filter('id_record');
$operazione = filter('op');

$foreign_keys = ['id_nazione'];
foreach ($foreign_keys as $fk) {
    if (isset($post[$fk])) {
        $post[$fk] = !empty($post[$fk]) ? $post[$fk] : 'NULL';
    }
}

switch ($operazione) {
    case 'addsede':
        $names = [];
        $values = [];

        $exclude = ['op', 'backto'];
        foreach ($post as $name => $value) {
            if (!in_array($name, $exclude)) {
                $names[] = '`'.$name.'`';
                $values[] = ($value != 'NULL') ? prepare($value) : $value;
            }
        }

        // Inserisco la nuova sede
        $query = 'INSERT INTO `an_sedi` (`idanagrafica`, '.implode($names, ',').') VALUES ('.prepare($id_record).', '.implode($values, ',').')';
        $dbo->query($query);

        $_SESSION['infos'][] = _('Aggiunta una nuova sede!');
        break;

    case 'updatesede':
        $values = [];

        $exclude = ['op', 'backto', 'id'];
        foreach ($post as $name => $value) {
            if (!in_array($name, $exclude)) {
                $value = ($value != 'NULL') ? prepare($value) : $value;
                $values[] = '`'.$name.'`='.$value;
            }
        }

        // Inserisco la nuova sede
        $query = 'UPDATE `an_sedi` SET '.implode($values, ',').' WHERE id='.prepare($post['id']);
        $dbo->query($query);

        $_SESSION['infos'][] = _('Salvataggio completato!');
        break;

    case 'deletesede':
        $idsede = filter('id');
        $dbo->query("DELETE FROM `an_sedi` WHERE `id`=".prepare($idsede));

        $_SESSION['infos'][] = _('Sede eliminata!');

        break;
}

if (filter('add') != null) {
    echo '
<form action="#tab_4" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addsede">

	<div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "text", "label": "'._('Nome sede').'", "name": "nomesede", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Indirizzo').'", "name": "indirizzo", "required": 1 ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Secondo indirizzo').'", "name": "indirizzo2" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('P.Iva').'", "name": "piva" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Codice Fiscale').'", "name": "codice_fiscale" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('Città').'", "name": "citta" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('C.A.P.').'", "name": "cap" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('Provincia').'", "name": "provincia" ]}
		</div>

		<div class="col-xs-12 col-md-3">
			{[ "type": "text", "label": "'._('Km').'", "name": "km" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "select", "label": "'._('Nazione').'", "name": "id_nazione", "values": "query=SELECT `id`, `nome` AS `descrizione` FROM `an_nazioni` ORDER BY `descrizione` ASC" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Telefono').'", "name": "telefono" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Fax').'", "name": "fax" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Cellulare').'", "name": "cellulare" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'._('Indirizzo email').'", "name": "email" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "select", "label": "'._('Zona').'", "name": "idzona", "values": "query=SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM `an_zone` ORDER BY `descrizione` ASC" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '._('Aggiungi').'</button>
		</div>
	</div>
</form>

<script src="'.$rootdir.'/lib/init.js"></script>';
} else {
    echo '
	<div class="box">
		<div class="box-header with-border">
			<h3 class="box-title">'._('Sedi').'</h3>
			<a class="btn btn-primary pull-right" data-toggle="modal" data-target="#bs-popup" data-title="'._('Nuovo referente').'" data-href="'.$rootdir.'/modules/anagrafiche/plugins/sedi.php?add=1&id_record='.$id_record.'"><i class="fa fa-plus"></i> '._('Nuova sede').'</a>
		</div>
		<div class="box-body">
			<p>'._('Qui hai la possibilità di gestire le sedi di questa anagrafica').'.</p>';

    // Aggiorna sede
    $rsp = $dbo->fetchArray('SELECT * FROM an_sedi WHERE idanagrafica='.prepare($id_record).' ORDER BY id DESC');

    for ($j = 0; $j < count($rsp); ++$j) {
        echo '
			<div class="well">
				<p class="clickable" onclick="$(this).next().slideToggle();">
					<i class="fa fa-building"></i>
					<strong>'.$rsp[$j]['nomesede'].'</strong>
					'.$rsp[$j]['indirizzo'].', '.$rsp[$j]['citta'].' ('.$rsp[$j]['provincia'].')
				</p>
				<form action="" method="post" style="display:none">
					<input type="hidden" name="backto" value="record-edit">
					<input type="hidden" name="op" value="updatesede">
					<input type="hidden" name="id" value="'.$rsp[$j]['id'].'"/>

					<div class="row">
						<div class="col-xs-12 col-md-12">
							{[ "type": "text", "label": "'._('Nome sede').'", "name": "nomesede", "required": 1, "value": "'.$rsp[$j]['nomesede'].'" ]}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Indirizzo').'", "name": "indirizzo", "required": 1, "value": "'.$rsp[$j]['indirizzo'].'" ]}
						</div>

						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Secondo indirizzo').'", "name": "indirizzo2", "value": "'.$rsp[$j]['indirizzo2'].'" ]}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('P.Iva').'", "name": "piva", "value": "'.$rsp[$j]['piva'].'" ]}
						</div>

						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Codice Fiscale').'", "name": "codice_fiscale", "value": "'.$rsp[$j]['codice_fiscale'].'" ]}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-3">
							{[ "type": "text", "label": "'._('Città').'", "name": "citta", "value": "'.$rsp[$j]['citta'].'" ]}
						</div>

						<div class="col-xs-12 col-md-3">
							{[ "type": "text", "label": "'._('C.A.P.').'", "name": "cap", "value": "'.$rsp[$j]['cap'].'" ]}
						</div>

						<div class="col-xs-12 col-md-3">
							{[ "type": "text", "label": "'._('Provincia').'", "name": "provincia", "value": "'.$rsp[$j]['provincia'].'" ]}
						</div>

						<div class="col-xs-12 col-md-3">
							{[ "type": "text", "label": "'._('Km').'", "name": "km", "value": "'.$rsp[$j]['km'].'" ]}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-6">
							{[ "type": "select", "label": "'._('Nazione').'", "name": "id_nazione", "values": "query=SELECT `id`, `nome` AS `descrizione` FROM `an_nazioni` ORDER BY `descrizione` ASC", "value": "'.$rsp[$j]['id_nazione'].'" ]}
						</div>

						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Telefono').'", "name": "telefono", "value": "'.$rsp[$j]['telefono'].'" ]}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Fax').'", "name": "fax", "value": "'.$rsp[$j]['fax'].'" ]}
						</div>

						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Cellulare').'", "name": "cellulare", "value": "'.$rsp[$j]['cellulare'].'" ]}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-md-6">
							{[ "type": "text", "label": "'._('Indirizzo email').'", "name": "email", "value": "'.$rsp[$j]['email'].'" ]}
						</div>

						<div class="col-xs-12 col-md-6">
							{[ "type": "select", "label": "'._('Zona').'", "name": "idzona", "values": "query=SELECT `id`, CONCAT(`nome`, \' - \', `descrizione`) AS `descrizione` FROM `an_zone` ORDER BY `descrizione` ASC", "value": "'.$rsp[$j]['idzona'].'" ]}
						</div>
					</div>

					<div class="pull-right">
						<a class="btn btn-danger ask" data-op="deletesede" data-id="'.$rsp[$j]['id'].'">
                            <i class="fa fa-trash"></i> '._('Elimina').'
                        </a>
						<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '._('Salva modifiche').'</button>
					</div>
					<div class="clearfix"></div>
				</form>
			</div>';
    }
    echo '
		</div>
	</div>';
}
