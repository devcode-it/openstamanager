<?php

include_once __DIR__.'/../../core.php';

// Imposto come azienda l'azienda predefinita per selezionare le sedi a cui ho accesso
$_SESSION['superselect']['idanagrafica'] = get_var('Azienda predefinita');

// Azzero le sedi selezionate
unset($_SESSION['superselect']['idsede_partenza']);
unset($_SESSION['superselect']['idsede_destinazione']);
$_SESSION['superselect']['idsede_partenza'] = 0;
$_SESSION['superselect']['idsede_destinazione'] = 0;

?>

<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-4">
            {["type":"select", "label":"<?php echo tr('Articolo');?>", "name":"idarticolo", "ajax-source":"articoli", "value":"", "required":1]}
        </div>

        <div class="col-md-2">
            {["type":"number", "label":"<?php echo tr('Quantità');?>", "name":"qta", "decimals":"2", "value":"1", "required":1]}
        </div>

        <div class="col-md-2">
            {["type":"date", "label":"<?php echo tr('Data');?>", "name":"data", "value":"-now-", "required":1]}
        </div>

        <div class="col-md-4">
            {["type":"select", "label":"<?php echo tr('Causale');?>", "name":"direzione", "values":"list=\"Carico manuale\":\"Carico\", \"Scarico manuale\":\"Scarico\" ", "value":"Carico manuale", "required":1]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {["type":"textarea", "label":"<?php echo tr('Descrizione movimento');?>", "name":"movimento", "required":1]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede_destinazione", "ajax-source": "sedi_azienda",  "value": "0", "required":1 ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Partenza merce'); ?>", "name": "idsede_partenza", "ajax-source": "sedi_azienda",  "value": "0", "required":1 ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row" id="buttons">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-default"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi e chiudi'); ?></button>
            <a type="button" class="btn btn-primary" onclick="ajax_submit( '', '' );"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></a>
		</div>
	</div>
</form>

<script>
    $('#bs-popup').on('shown.bs.modal', function(){
        $('#direzione').on('change', function(){
            $('#movimento').val( $(this).val() );
        });

        $('#direzione').trigger('change');

        // Lettura codici da lettore barcode
        var keys = '';

        $(document).unbind('keyup');

        $(document).on('keyup', function (evt) {
            if(window.event) { // IE
                keynum = evt.keyCode;
            } else if(evt.which){ // Netscape/Firefox/Opera
                keynum = evt.which;
            }

            if (evt.which === 13) {
                var search = keys;

                // Ricerca via ajax del barcode negli articoli
                $.get(
                    globals.rootdir + '/ajax_select.php?op=articoli&search='+search,
                    function(data){
                        data = $.parseJSON(data);

                        // Articolo trovato
                        if( data.results.length == 1 ){
                            var record = data.results[0].children[0];
                            $('#idarticolo').selectSetNew( record.id, record.text );
                            ajax_submit( search, record.text );
                        }
                        
                        // Articolo non trovato
                        else {
                            $('#buttons').next('hr').remove();
                            $('#buttons').next('div.alert').remove();
                            $('#buttons').after( '<hr><div class="alert alert-danger text-center"><big>Articolo <b>' + barcode + '</b> non trovato!</big></div>' );
                        }
                    }
                );
                keys = '';
            } else {
                keys += String.fromCharCode( evt.keyCode );
            }
        });
    });

    function ajax_submit( barcode, articolo ) {
        //Controllo che siano presenti tutti i dati richiesti
        if( $("#add-form").parsley().validate() ){
            submitAjax(
                $('#add-form'),
                {},
                function() {},
                function(){}
            );

            $('#buttons').next('hr').remove();
            $('#buttons').next('div.alert').remove();
            
            if( barcode != '' || articolo != '' ){
                $('#buttons').after( '<hr><div class="alert alert-success text-center"><big>Inserito movimento articolo<br><b>' + barcode +'</b><br><b>' + articolo + '</b>!</big></div>' );
            }
            
            $("#qta").val(1);
        }
	}
</script>
