<?php

include_once __DIR__.'/../../core.php';

$idarticolo = get('idarticolo');
$idgruppo = get('idgruppo');

$q2 = 'SELECT * FROM mg_articoli_interventi INNER JOIN in_interventi ON mg_articoli_interventi.idintervento=in_interventi.id WHERE mg_articoli_interventi.idintervento='.prepare($id_record).' AND mg_articoli_interventi.idgruppo='.prepare($idgruppo);
$rs2 = $dbo->fetchArray($q2);

echo '
<p>'._('Articolo').': '.$rs2[0]['codice'].' - '.$rs2[0]['descrizione'].'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="add_serial">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="idddt" value="'.$id_record.'">
    <input type="hidden" name="idgruppo" value="'.$rs2[0]['idgruppo'].'">
    <input type="hidden" name="dir" value="'.$dir.'">';

$serials = [];
$array = array_column($rs2, 'serial');
foreach ($array as $value) {
    if (!empty($value)) {
        $serials[] = $value;
    }
}

    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'._('Serial').'", "name": "serial[]", "multiple": 1, "value": "'.implode(',', $serials).'", "values": "query=SELECT serial AS id, serial AS descrizione FROM vw_serials WHERE dir=\'uscita\' AND serial NOT IN (SELECT serial FROM vw_serials WHERE dir=\'entrata\' AND record != \'int-'.$id_record.'\')", "extra": "data-maximum=\"'.count($rs2).'\"" ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-barcode"></i> '._('Aggiorna').'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
