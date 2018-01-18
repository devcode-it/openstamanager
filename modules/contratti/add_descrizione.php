<?php


include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$record = $dbo->fetchArray('SELECT * FROM co_contratti WHERE id='.prepare($id_record));
$numero = $record[0]['numero'];
$idanagrafica = $record[0]['idanagrafica'];

/*
    Form di inserimento riga documento
*/
echo '
<p>'.tr('Contratto numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="adddescrizione">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1 ]}
        </div>
    </div>';
    
echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
    </div>
</form>';