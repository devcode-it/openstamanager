<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

$record = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
$numero = ($record[0]['numero_esterno'] != '') ? $record[0]['numero_esterno'] : $record[0]['numero'];
$idconto = $record[0]['idconto'];
$idanagrafica = $record[0]['idanagrafica'];

// Preventivo
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Ddt').'", "name": "id_ddt", "required": 1, "values": "query=SELECT dt_ddt.id, CONCAT(\'nr. \', IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\')) AS descrizione FROM dt_ddt WHERE idanagrafica='.prepare($idanagrafica).' AND idstatoddt IN (SELECT id FROM dt_statiddt WHERE descrizione IN(\'Bozza\', \'Parzialmente fatturato\')) AND idtipoddt=(SELECT id FROM dt_tipiddt WHERE dir='.prepare($dir).') AND dt_ddt.id IN (SELECT idddt FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id AND (qta - qta_evasa) > 0) ORDER BY data DESC, numero DESC" ]}
        </div>
    </div>';

echo '
    <div class="row">
        <div id="righeddt" class="col-md-12"></div>
    </div>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script>
	$('#id_ddt').change( function(){
        $('#righeddt').html('<i>Caricamento in corso...</i>');

        $('#righeddt').load(globals.rootdir + '/modules/fatture/crea_documento.php?id_module=' + <?php echo Modules::get('Ddt di vendita')['id'] ?> + '&id_record=' + $(this).find('option:selected').val() + '&documento=fattura&op=add_ddt&iddocumento=' + globals.id_record);
    });
</script>
