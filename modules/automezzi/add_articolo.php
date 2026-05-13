<?php

include_once __DIR__.'/../../core.php';
use Models\Module;

$idautomezzo = get('idautomezzo');
$id_articolo = get('id_articolo');
$op = 'addrow';
$qta = 1;

if (!empty($id_articolo) && !empty($idautomezzo)) {
    $qta = $dbo->fetchOne('SELECT SUM(mg_movimenti.qta) AS qta FROM mg_movimenti WHERE mg_movimenti.id_articolo='.prepare($id_articolo).' AND mg_movimenti.id_sede='.prepare($idautomezzo))['qta'];
    $op = 'editrow';
}

/*
    Form di inserimento riga documento
*/
echo '
<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Module::where('name', 'Automezzi')->first()->id.'&id_record='.$idautomezzo.'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="'.$idautomezzo.'">';

// Seleziona articolo
echo '
    <div class="col-md-8">
        {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "required": 1, "value": "'.$id_articolo.'", "ajax-source": "articoli", "select-options": '.json_encode(['id_sede_partenza' => 0]).' ]}
    </div>';

// Quantità
echo '
    <div class="col-md-4">
        {[ "type": "number", "label": "'.tr('Q.tà su questo automezzo').'", "name": "qta", "value": "'.$qta.'", "decimals": "qta" ]}
    </div>';

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';

echo '
<script>
    $(document).ready(function(){init();});
</script>';
