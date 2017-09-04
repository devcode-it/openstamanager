<?php

include_once __DIR__.'/../../../core.php';

$id_record = filter('id_record');
$operazione = filter('op');

switch ($operazione) {
    case 'addreferente':
        $nome = filter('nome');
        $mansione = filter('mansione');
        $telefono = filter('telefono');
        $email = filter('email');
        $idsede = filter('idsede');

        if (isset($nome) && isset($idsede)) {
            $query = 'INSERT INTO `an_referenti` (`nome`, `mansione`, `telefono`, `email`, `idanagrafica`, `idsede`) VALUES ('.prepare($nome).', '.prepare($mansione).', '.prepare($telefono).', '.prepare($email).', '.prepare($id_record).', '.prepare($idsede).')';

            $dbo->query($query);
            $_SESSION['infos'][] = tr('Aggiunto nuovo referente!');
        }

        break;

    case 'updatereferenti':
        foreach ($_POST['idreferente'] as $key => $value) {
            $query = 'UPDATE `an_referenti` SET `nome`='.prepare($_POST['nome'][$key]).', `mansione`='.prepare($_POST['mansione'][$key]).', `telefono`='.prepare($_POST['telefono'][$key]).', `email`='.prepare($_POST['email'][$key]).', `idsede`='.prepare($_POST['idsede'][$key]).' WHERE `id`='.prepare($value);

            $dbo->query($query);
        }
        $_SESSION['infos'][] = tr('Salvataggio completato!');

        break;

    case 'deletereferente':
        $idreferente = filter('id');
        $dbo->query("DELETE FROM `an_referenti` WHERE `id`=".prepare($idreferente));
        $_SESSION['infos'][] = tr('Referente eliminato!');

        break;
}

if (filter('add') != null) {
    echo '
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addreferente">

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Mansione').'", "name": "mansione", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-xs-12 col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 col-md-12">
			{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT -1 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_record.'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-xs-12 col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>

<script src="'.$rootdir.'/lib/init.js"></script>';
} else {
    echo '
	<div class="box">
		<div class="box-header with-border">
			<h3 class="box-title">'.tr('Referenti').'</h3>
			<a class="btn btn-primary pull-right" data-toggle="modal" data-target="#bs-popup" data-title="Nuovo referente" data-href="'.$rootdir.'/modules/anagrafiche/plugins/referenti.php?add=1&id_record='.$id_record.'"><i class="fa fa-plus"></i> '.tr('Nuovo referente').'</a>
		</div>
		<div class="box-body">
			<p>'.tr('Qui hai la possibilità di gestire i referenti di questa anagrafica').'.</p>
			<form action="" method="post">
				<input type="hidden" name="backto" value="record-edit">
				<input type="hidden" name="op" value="updatereferenti">';

    $query = 'SELECT * FROM an_referenti WHERE idanagrafica='.prepare($id_record).' ORDER BY id DESC';
    $results = $dbo->fetchArray($query);
    if (count($results) != 0) {
        echo '
				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th>'.tr('Nominativo').'</th>
							<th>'.tr('Mansione').'</th>
							<th>'.tr('Telefono').'</th>
							<th>'.tr('Indirizzo email').'</th>
							<th>'.tr('Sede').'</th>
							<th>'.tr('Opzioni').'</th>
						</tr>
					</thead>
					<tbody>';
        foreach ($results as $result) {
            echo '
						<tr>
							<td>
								<input type="hidden" name="idreferente[]" value="'.$result['id'].'">
								{[ "type": "text", "placeholder": "'.tr('Nominativo').'", "name": "nome[]", "required": 1, "value": "'.$result['nome'].'" ]}
							</td>
							<td>
								{[ "type": "text", "placeholder": "'.tr('Mansione').'", "name": "mansione[]", "required": 1, "value": "'.$result['mansione'].'" ]}
							</td>
							<td>
								{[ "type": "text", "placeholder": "'.tr('Telefono').'", "name": "telefono[]", "value": "'.$result['telefono'].'" ]}
							</td>
							<td>
								{[ "type": "text", "placeholder": "'.tr('Indirizzo email').'", "name": "email[]", "value": "'.$result['email'].'" ]}
							</td>
							<td>
								{[ "type": "select", "placeholder": "'.tr('Sede').'", "name": "idsede[]", "values": "query=SELECT -1 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$id_record.'", "value": "'.$result['idsede'].'" ]}
							</td>
							<td>
								<a class="btn btn-danger pull-right ask" data-op="deletereferente" data-id="'.$result['id'].'">
                                    <i class="fa fa-trash"></i> '.tr('Elimina').'
                                </a>
							</td>
						</tr>';
        }
        echo '
					</tbody>
				</table>

				<div class="pull-right">
					<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>
				</div>
				<div class="clearfix"></div>';
    }
    echo '

			</form>
		</div>
	</div>';
}
