<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

$idarticolo = get('idarticolo');
$idgruppo = get('idgruppo');

$q2 = 'SELECT * FROM dt_righe_ddt INNER JOIN mg_articoli ON dt_righe_ddt.idarticolo=mg_articoli.id WHERE dt_righe_ddt.idddt='.prepare($id_record).' AND dt_righe_ddt.idgruppo='.prepare($idgruppo);
$rs2 = $dbo->fetchArray($q2);

echo '
<p>'._('Articolo').': '.$rs2[0]['codice'].' - '.$rs2[0]['descrizione'].'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="add_serial">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="idgruppo" value="'.$rs2[0]['idgruppo'].'">
    <input type="hidden" name="dir" value="'.$dir.'">';

$serials = [];
$array = array_column($rs2, 'serial');
foreach ($array as $value) {
    if (!empty($value)) {
        $serials[] = $value;
    }
}

if ($dir == 'entrata') {
    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'._('Serial').'", "name": "serial[]", "multiple": 1, "value": "'.implode(',', $serials).'", "values": "query=SELECT serial AS id, serial AS descrizione FROM vw_serials WHERE dir=\'uscita\' AND serial NOT IN (SELECT serial FROM vw_serials WHERE dir=\'entrata\' AND record != \'ddt-'.$id_record.'\')", "extra": "data-maximum=\"'.count($rs2).'\"" ]}
        </div>
    </div>';
} else {
    echo '
    <p>'._('Inserisci i numeri seriali degli articoli aggiunti:').'</p>';

    foreach ($array as $key => $serial) {
        if ($key % 3 == 0) {
            echo '
    <div class="row">';
        }

        $res = $dbo->fetchArray("SELECT record FROM vw_serials WHERE dir='entrata' AND serial = ".prepare($serial));

        echo '
        <div class="col-md-4">
            {[ "type": "text", "name": "serial[]", "value": "'.$serial.'"'.(!empty($res) ? ', "readonly": 1' : '').' ]}';

        if (!empty($res)) {
            $pieces = explode('-', $res[0]['record']);
            switch ($pieces[0]) {
                case 'int':
                    $modulo = 'Interventi';
                    break;

                case 'ddt':
                    $modulo = 'Ddt di vendita';
                    break;

                case 'fat':
                    $modulo = 'Fatture di vendita';
                    break;

                case 'ord':
                    $modulo = 'Ordini cliente';
                    break;
            }

            echo '
        '.Modules::link($modulo, $pieces[1], _('Visualizza vendita').' <i class="fa fa-external-link"></i>', null);
        }
        echo '
        </div>';

        if (($key + 1) % 3 == 0) {
            echo '
    </div>
    <br>';
        }
    }
    if (($key + 1) % 3 != 0) {
        echo '
    </div>';
    }
}

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
