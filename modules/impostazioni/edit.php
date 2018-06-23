<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
            <h3 class="panel-title">'.tr('Impostazioni _SEZIONE_', [
                '_SEZIONE_' => $records[0]['sezione'],
            ]).'</h3>
		</div>

		<div class="panel-body">';

foreach ($records as $record) {
    // Scelta fra più valori
    echo '
			<div class="col-md-6">';
    if (preg_match("/list\[(.+?)\]/", $record['tipo'], $m)) {
        $m = explode(',', $m[1]);
        $list = '';
        for ($j = 0; $j < count($m); ++$j) {
            if ($j != 0) {
                $list .= ',';
            }
            $list .= '\\"'.$m[$j].'\\": \\"'.$m[$j].'\\"';
        }
        echo '
				{[ "type": "select", "label": "'.$record['nome'].'", "name": "'.$record['idimpostazione'].'", "values": "list='.$list.'", "value": "'.$record['valore'].'" ]}';
    }

    // query
    elseif (preg_match('/^query=(.+?)$/', $record['tipo'], $m)) {
        echo '
				{[ "type": "select", "label": "'.$record['nome'].'", "name": "'.$record['idimpostazione'].'", "values": "'.$record['tipo'].'", "value": "'.$record['valore'].'" ]}';
    }

    // Boolean (checkbox)
    elseif ($record['tipo'] == 'boolean') {
        echo '
				{[ "type": "checkbox", "label": "'.$record['nome'].'", "name": "'.$record['idimpostazione'].'", "placeholder": "'.tr('Attivo').'", "value": "'.$record['valore'].'" ]}';
    } elseif ($record['tipo'] == 'textarea') {
        echo '
				{[ "type": "textarea", "label": "'.$record['nome'].'", "name": "'.$record['idimpostazione'].'", "value": '.json_encode($record['valore']).' ]}';
    }
    // Campo di testo normale
    else {
        $numerico = in_array($record['tipo'], ['integer', 'decimal']);

        $tipo = preg_match('/password/i', $record['nome'], $m) ? 'password' : $record['tipo'];
        $tipo = $numerico ? 'number' : 'text';

        echo '
				{[ "type": "'.$tipo.'", "label": "'.$record['nome'].'", "name": "'.$record['idimpostazione'].'", "value": "'.$record['valore'].'"'.($numerico && $record['tipo'] == 'integer' ? ', "decimals": 0' : '').' ]}';
    }
    echo '
			</div>';
}

echo '
			<div class="clearfix"></div><hr>
            <div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>
			</div>
		</div>
	</div>

</form>';
