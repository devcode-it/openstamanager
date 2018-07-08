<?php

include_once __DIR__.'/../../../core.php';

// Prezzo modificabile solo se l'utente loggato è un tecnico (+ può vedere i prezzi) o se è amministratore
$gruppi = Auth::user()['gruppo'];
//$can_edit_prezzi = (in_array('Amministratori', $gruppi)) || (setting('Mostra i prezzi al tecnico') == 1 && (in_array('Tecnici', $gruppi)));

$idriga = get('idriga');
//$idautomezzo = (get('idautomezzo') == 'undefined') ? '' : get('idautomezzo');

// Lettura idanagrafica cliente e percentuale di sconto/rincaro in base al listino
$rs = $dbo->fetchArray('SELECT idanagrafica FROM co_contratti WHERE id='.prepare($id_record));

$idanagrafica = $rs[0]['idanagrafica'];

if (empty($idriga)) {
    $op = 'addarticolo';
    $button = '<i class="fa fa-plus"></i> '.tr('Aggiungi');

    // valori default
    $idarticolo = '';
    $descrizione = '';
    $qta = 1;
    $um = '';

    $prezzo_vendita = '0';
    $sconto_unitario = 0;

    $idimpianto = 0;

    $listino = $dbo->fetchArray('SELECT prc_guadagno FROM mg_listini WHERE id = (SELECT idlistino_vendite FROM an_anagrafiche WHERE idanagrafica = '.prepare($idanagrafica).')');
    if (!empty($listino[0]['prc_guadagno'])) {
        $sconto_unitario = $listino[0]['prc_guadagno'];
        $tipo_sconto = 'PRC';
    }

    (empty($idcontratto_riga)) ? $idcontratto_riga = $dbo->fetchArray('SELECT MAX(id) AS max_idcontratto_riga  FROM `co_contratti_promemoria`')[0]['max_idcontratto_riga'] : '';
} else {
    $op = 'editarticolo';
    $button = '<i class="fa fa-edit"></i> '.tr('Modifica');

    // carico record da modificare
    $q = "SELECT *, (SELECT codice FROM mg_articoli WHERE id=co_righe_contratti_articoli.idarticolo) AS codice_articolo, (SELECT CONCAT(codice, ' - ', descrizione) FROM mg_articoli WHERE id=co_righe_contratti_articoli.idarticolo) AS descrizione_articolo FROM co_righe_contratti_articoli WHERE id=".prepare($idriga);
    $rsr = $dbo->fetchArray($q);

    $idarticolo = $rsr[0]['idarticolo'];
    $codice_articolo = $rsr[0]['codice_articolo'];
    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $idiva = $rsr[0]['idiva'];

    $prezzo_vendita = $rsr[0]['prezzo_vendita'];

    $sconto_unitario = $rsr[0]['sconto_unitario'];
    $tipo_sconto = $rsr[0]['tipo_sconto'];

    $idautomezzo = $rsr[0]['idautomezzo'];

    $idimpianto = $rsr[0]['idimpianto'];
    $idcontratto_riga = $rsr[0]['id_riga_contratto'];
}

/*
    Form di inserimento
*/
echo '

<form id="add-articoli" action="'.$rootdir.'/modules/contratti/plugins/actions.php" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="idriga" value="'.$idriga.'">

	<input type="hidden" name="idcontratto_riga" value="'.$idcontratto_riga.'">';

if (!empty($idarticolo)) {
    echo '
    <input type="hidden" id="idarticolo_originale" name="idarticolo_originale" value="'.$idarticolo.'">';
}

// Articolo
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$idarticolo.'", "ajax-source": "articoli" ]}
        </div>
    </div>';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "id": "descrizione_articolo", "required": 1, "value": '.json_encode($descrizione).' ]}
        </div>
    </div>
    <br>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$qta.'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "name": "um", "value": "'.$um.'", "ajax-source": "misure" ]}
        </div>';

// Impianto
echo '
		<div class="col-md-4">
			{[ "type": "select", "multiple": "0", "label": "'.tr('Impianto').'", "name": "idimpianto", "values": "query=SELECT my_impianti.id AS id, my_impianti.nome AS descrizione FROM my_impianti_contratti INNER JOIN my_impianti ON my_impianti_contratti.idimpianto = my_impianti.id  WHERE my_impianti_contratti.idcontratto = '.$id_record.' ORDER BY descrizione", "value": "'.$matricoleimpianti.'", "extra":"'.$readonly.'" ]}
		</div>
	</div>';

// Iva
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>';

// Prezzo di vendita
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo_vendita", "required": 1, "value": "'.$prezzo_vendita.'", "icon-after": "&euro;" ]}
        </div>';

// Sconto
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "icon-after": "choice|untprc|'.$tipo_sconto.'", "value": "'.$sconto_unitario.'" ]}
        </div>
    </div>';

// Informazioni aggiuntive
/*echo '
    <div class="row" id="prezzi_articolo">
        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezzi\').toggleClass(\'hide\'); $(\'#prezzi\').load(\''.$rootdir."/ajax_complete.php?module=Articoli&op=getprezzi&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$idanagrafica.'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (cliente)').'
            </button>
            <div id="prezzi" class="hide"></div>
        </div>

        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezziacquisto\').toggleClass(\'hide\'); $(\'#prezziacquisto\').load(\''.$rootdir."/ajax_complete.php?module=Articoli&op=getprezziacquisto&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$idanagrafica.'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (acquisto)').'
            </button>
            <div id="prezziacquisto" class="hide"></div>
        </div>

        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezzivendita\').toggleClass(\'hide\'); $(\'#prezzivendita\').load(\''.$rootdir."/ajax_complete.php?module=Articoli&op=getprezzivendita&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$idanagrafica.'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (vendita)').'
            </button>
            <div id="prezzivendita" class="hide"></div>
        </div>
    </div>
    <br>';*/

echo '
    <script>
    $(document).ready(function () {
        $("#idarticolo").on("change", function(){
            $("#prezzi_articolo button").attr("disabled", !$(this).val());
            if($(this).val()){
                $("#prezzi_articolo button").removeClass("disabled");

                session_set("superselect,idarticolo", $(this).val(), 0);
                $data = $(this).selectData();

                $("#prezzo_vendita").val($data.prezzo_vendita);
                $("#descrizione_articolo").val($data.descrizione);
                $("#idiva").selectSet($data.idiva_vendita, $data.iva_vendita);
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
			<button type="submit" class="btn btn-primary pull-right">'.$button.'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#add-articoli').ajaxForm({
            success: function(){
                $('#bs-popup2').modal('hide');

                // Ricarico gli articoli
                $('#articoli').load(globals.rootdir + '/modules/contratti/plugins/ajax_articoli.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&idcontratto_riga=<?php echo $idcontratto_riga; ?>');


            }
        });
    });
</script>
