<?php

include_once __DIR__.'/../../../core.php';

$idcontratto = $get['idcontratto'];
$idpianificazione = $get['idpianificazione'];
$importo = $get['importo'];
$n_rata = $get['n_rata'];

// Lettura numero contratto e nome zona
$rs = $dbo->fetchArray('SELECT numero, (SELECT descrizione FROM an_zone WHERE id=(SELECT idzona FROM co_ordiniservizio_pianificazionefatture WHERE id='.prepare($idpianificazione).')) AS zona FROM co_contratti WHERE id='.prepare($idcontratto));
$numero = $rs[0]['numero'];
$zona = $rs[0]['zona'];

echo '
<form id="add_form" action="'.$rootdir.'/editor.php?id_module='.Modules::get('Contratti')['id'].'&id_record='.$idcontratto.'&op=addfattura&idpianificazione='.$idpianificazione.'&importo='.$importo.'" method="post">
    <input type="hidden" name="backto" value="record-edit">';

// Data
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "class": "text-center", "value": "-now-" ]}
        </div>';

// Tipo di documento
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tipo di fattura').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT * FROM co_tipidocumento WHERE dir=\'entrata\'" ]}
        </div>';


// Sezionale
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.Modules::get('Fatture di vendita')['id'].' ORDER BY name",  "value":"'.$_SESSION['m'.Modules::get('Fatture di vendita')['id']]['id_segment'].'" ]}
        </div>

    </div>';

// Note
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "note", "value": "Rata '.$n_rata.' del contratto numero '.$numero.', zona '.$zona.'" ]}
        </div>
    </div>';

echo '
<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
