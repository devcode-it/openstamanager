<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';

    $listino = 'idlistino_vendite';
} else {
    $dir = 'uscita';

    $listino = 'idlistino_acquisti';
}
$_SESSION['superselect']['dir'] = $dir;

// Info documento
$q = 'SELECT *, (SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT '.$listino.' FROM an_anagrafiche WHERE idanagrafica=dt_ddt.idanagrafica)) AS prc_guadagno FROM dt_ddt WHERE id='.prepare($id_record);
$rs = $dbo->fetchArray($q);
$numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];

if (!empty($rs[0]['prc_guadagno'])) {
    $sconto = $rs[0]['prc_guadagno'];
    $tipo_sconto = 'PRC';
}

/*
    Form di inserimento riga documento
*/
echo '
<p>'.tr('Ddt numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="addarticolo">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

// Articolo
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$idarticolo.'", "ajax-source": "articoli" ]}
        </div>
    </div>';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1 ]}
        </div>
    </div>';

// Nelle fatture di acquisto leggo l'iva di acquisto dal fornitore
if ($dir == 'uscita') {
    $rsf = $dbo->fetchArray('SELECT idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idiva = $rsf[0]['idiva'];
}

// Leggo l'iva predefinita dall'articolo e se non c'è leggo quella predefinita generica
$idiva = $idiva ?: get_var('Iva predefinita');

// Iva
echo '
    <div class="row">
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>';

// Quantità
echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "1", "decimals": "qta" ]}
        </div>';

// Costo unitario
echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-3">
        {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$sconto.'", "icon-after": "choice|untprc|  '.$tipo_sconto.'" ]}
        </div>
    </div>';

// Informazioni aggiuntive
echo '
    <div class="row" id="prezzi_articolo">
        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezzi\').toggleClass(\'hide\'); $(\'#prezzi\').load(\''.$rootdir."/ajax_autocomplete.php?module=Articoli&op=getprezzi&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$idanagrafica.'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (cliente)').'
            </button>
            <div id="prezzi" class="hide"></div>
        </div>

        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezziacquisto\').toggleClass(\'hide\'); $(\'#prezziacquisto\').load(\''.$rootdir."/ajax_autocomplete.php?module=Articoli&op=getprezziacquisto&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$idanagrafica.'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (acquisto)').'
            </button>
            <div id="prezziacquisto" class="hide"></div>
        </div>

        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezzivendita\').toggleClass(\'hide\'); $(\'#prezzivendita\').load(\''.$rootdir."/ajax_autocomplete.php?module=Articoli&op=getprezzivendita&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$idanagrafica.'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (vendita)').'
            </button>
            <div id="prezzivendita" class="hide"></div>
        </div>
    </div>
    <br>';

echo '
    <script>
    $(document).ready(function () {
        $("#idarticolo").on("change", function(){
            $("#prezzi_articolo button").attr("disabled", !$(this).val());
            if($(this).val()){
                $("#prezzi_articolo button").removeClass("disabled");

                session_set("superselect,idarticolo", $(this).val(), 0);
                $data = $(this).selectData();
                $("#prezzo").val($data.prezzo_'.($dir == 'entrata' ? 'vendita' : 'acquisto').');
                $("#descrizione").val($data.descrizione);
                $("#um").selectSetNew($data.um, $data.um);
            }else{
                $("#prezzi_articolo button").addClass("disabled");
            }

            $("#prezzi").html("");
            $("#prezzivendita").html("");
            $("#prezziacquisto").html("");
        });
    });
    </script>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script type="text/javascript">
    dir = '<?php echo $dir ?>';

    // Se sono stati selezionati un serial number e/o altro codice, la quantità deve rimanere 1 (solo per la vendita)
    function check_qta(){
        if( ($('select[name=serial] option:selected').val()!='' || $('select[name=altro] option:selected').val()!='') && $('#idarticolo option:selected').attr('qta_magazzino')!=undefined )
            $('#qta').val('1');
    }

    $(document).ready( function(){
        if( dir=='entrata' )
            setInterval( "check_qta()", 1000 );
    });
</script>
