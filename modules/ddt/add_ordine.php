<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
    $module_origin = 'Ordini cliente';
} else {
    $dir = 'uscita';
    $module_origin = 'Ordini fornitore';
}

$record = $dbo->fetchArray('SELECT * FROM dt_ddt WHERE id='.prepare($id_record));
$numero = ($record[0]['numero_esterno'] != '') ? $record[0]['numero_esterno'] : $record[0]['numero'];
$idconto = $record[0]['idconto'];
$idanagrafica = $record[0]['idanagrafica'];

// Preventivo
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Ordine').'", "name": "id_ordine", "required": 1, "values": "query=SELECT or_ordini.id, CONCAT(IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\')) AS descrizione FROM or_ordini WHERE idanagrafica='.prepare($idanagrafica).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Bozza\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).' LIMIT 0,1) AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0) ORDER BY data DESC, numero DESC" ]}
        </div>
    </div>';

echo '
    <div class="row">
        <div id="righeordine" class="col-md-12"></div>
    </div>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script>
	$('#id_ordine').change( function(){
        $('#righeordine').html('<i>Caricamento in corso...</i>');

        $('#righeordine').load(globals.rootdir + '/modules/fatture/crea_documento.php?id_module=' + <?php echo Modules::get($module_origin)['id']; ?> + '&id_record=' + $(this).find('option:selected').val() + '&documento=ddt&op=add_ordine&iddocumento=' + globals.id_record);
    });
</script>
