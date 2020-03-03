<?php

include_once __DIR__.'/../../core.php';

// Imposto come azienda l'azienda predefinita per selezionare le sedi a cui ho accesso
$_SESSION['superselect']['idanagrafica'] = setting('Azienda predefinita');

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
            {["type": "select", "label": "<?php echo tr('Articolo'); ?>", "name": "idarticolo", "ajax-source": "articoli", "value": "", "required": 1 ]}
        </div>

        <div class="col-md-2">
            {["type": "number", "label": "<?php echo tr('Quantità'); ?>", "name": "qta", "decimals": "2", "value": "1", "required": 1 ]}
        </div>

        <div class="col-md-2">
            {["type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "value": "-now-", "required": 1 ]}
        </div>

        <div class="col-md-4">
            {["type": "select", "label": "<?php echo tr('Causale'); ?>", "name": "causale", "values": "query=SELECT id, nome as text, descrizione, movimento_carico FROM mg_causali_movimenti", "value": 1, "required": 1 ]}

            <input type="hidden" name="direzione" id="direzione">
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {["type": "textarea", "label": "<?php echo tr('Descrizione movimento'); ?>", "name": "movimento", "required": 1 ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede_destinazione", "ajax-source": "sedi_azienda",  "value": "0", "required": 1 ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Partenza merce'); ?>", "name": "idsede_partenza", "ajax-source": "sedi_azienda",  "value": "0", "required": 1 ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row" id="buttons">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-default"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi e chiudi'); ?></button>
            <a type="button" class="btn btn-primary" onclick="ajax_submit( $('#idarticolo').selectData() );"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></a>
		</div>
	</div>
</form>

<div id="messages"></div>

<script>
    $('#modals > div').on('shown.bs.modal', function(){
        $('#causale').on('change', function() {
            var data = $(this).selectData();
            if (data) {
                $('#movimento').val(data.descrizione);
                $('#direzione').val(data.movimento_carico);
            }
        });

        $('#causale').trigger('change');

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
                            ajax_submit( record );
                        }

                        // Articolo non trovato
                        else {
                            $('#messages').html( '<hr><div class="alert alert-danger text-center"><big>Articolo <b>' + search + '</b> non trovato!</big></div>' );
                        }
                    }
                );
                keys = '';
            } else {
                keys += String.fromCharCode( evt.keyCode );
            }
        });
    });

    // Reload pagina appena chiudo il modal
    $('#modals > div').on('hidden.bs.modal', function(){
        location.reload();
    });

    function ajax_submit( articolo ) {
        //Controllo che siano presenti tutti i dati richiesti
        if( $("#add-form").parsley().validate() ){
            submitAjax(
                $('#add-form'),
                {},
                function() {},
                function(){}
            );

            $('#messages').html('');

            var prezzo_acquisto = parseFloat(articolo.prezzo_acquisto);
            var prezzo_vendita = parseFloat(articolo.prezzo_vendita);

            var qta_movimento = parseFloat($('#qta').val());

            var alert = '';
            var icon = '';
            var text = '';
            var qta_rimanente = 0;

            if($('#direzione').val()=='Carico manuale'){
                alert = 'alert-success';
                icon = '<i class="fa fa-arrow-up"></i>';
                text = 'Carico';
                qta_rimanente = parseFloat(articolo.qta)+parseFloat(qta_movimento);
            }else{
                alert = 'alert-danger';
                icon = '<i class="fa fa-arrow-down"></i>';
                text = 'Scarico';
                qta_rimanente = parseFloat(articolo.qta)-parseFloat(qta_movimento);
            }

            if( articolo.descrizione != '' ){
                $('#messages').html(
                    '<hr>'+
                    '<div class="row">'+
                        '<div class="col-md-6">'+
                            '<div class="alert alert-info text-center" style="line-height: 1.6;">'+
                                '<b style="font-size:14pt;"><i class="fa fa-barcode"></i> ' + articolo.barcode + ' - ' + articolo.descrizione + '</b><br>'+
                                '<b>Prezzo acquisto:</b> ' + prezzo_acquisto.toLocale() + " " + globals.currency + '<br><b>Prezzo vendita:</b> ' + prezzo_vendita.toLocale() + " " + globals.currency +
                            '</div>'+
                        '</div>'+
                        '<div class="col-md-6">'+
                            '<div class="alert '+alert+' text-center">'+
                                '<p style="font-size:14pt;">'+icon+' '+text+' '+qta_movimento.toLocale()+' '+articolo.um+' <i class="fa fa-arrow-circle-right"></i> '+qta_rimanente.toLocale()+' '+articolo.um+' rimanenti</p>'+
                            '</div>'+
                        '</div>'+
                    '</div>'
                );
            }

            $("#qta").val(1);
        }
	}
</script>
<?php
if (setting('Attiva scorciatoie da tastiera')) {
    echo '
<script>
hotkeys(\'f8\', \'carico\', function(event, handler){
    $("#modals > div #direzione").val(1).change();
});
hotkeys.setScope(\'carico\');

hotkeys(\'f9\', \'carico\', function(event, handler){
    $("#modals > div #direzione").val(2).change();
});
hotkeys.setScope(\'carico\');
</script>';
}
