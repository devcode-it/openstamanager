<?php

$idautomezzo = get('idautomezzo');

/*
    Form di inserimento riga documento
*/
echo '
<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Modules::get('Automezzi')['id'].'&id_record='.$idautomezzo.'" method="post">
    <input type="hidden" name="op" value="addrow">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="'.$idautomezzo.'">';

// Seleziona articolo
echo '
    <div class="col-md-8">
        {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$idarticolo.'", "ajax-source": "articoli" ]}
    </div>';

// Quantità
echo '
    <div class="col-md-4">
        {[ "type": "number", "label": "'.tr('Q.tà su questo automezzo').'", "name": "qta", "value": "1", "decimals": "qta" ]}
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
	<script src="'.$rootdir.'/assets/js/init.js"></script>';
