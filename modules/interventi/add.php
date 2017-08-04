<?php

include_once __DIR__.'/../../core.php';

$disabled_fields = [];

$idstatointervento = 'CALL';

// rimuovo session usate sui select combinati (sedi, preventivi, contratti, impianti)
unset($_SESSION['superselect']['idanagrafica']);
unset($_SESSION['superselect']['idsede']);

// Se ho passato l'idanagrafica, carico il tipo di intervento di default
$idanagrafica = filter('idanagrafica');
$idsede = filter('idsede');

if (!empty($idanagrafica)) {
    $rs = $dbo->fetchArray('SELECT idtipointervento_default FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idtipointervento = $rs[0]['idtipointervento_default'];
    $idstatointervento = 'WIP';
    $richiesta = filter('richiesta');
}

// Calcolo orario di inizio e fine di default
if (filter('orario_inizio') !== null && filter('orario_inizio') != '00:00:00') {
    $orario_inizio = filter('orario_inizio');
    $orario_fine = filter('orario_fine');
} else {
    $orario_inizio = date('H').':00';
    $orario_fine = date('H', time() + 60 * 60).':00';
}

// Se sto pianificando un contratto, leggo tutti i dati del contratto per predisporre l'aggiunta intervento
$idcontratto = filter('idcontratto');
$idordineservizio = filter('idordineservizio');
$idcontratto_riga = filter('idcontratto_riga');

if (!empty($idcontratto) && !empty($idordineservizio)) {
    $rs = $dbo->fetchArray('SELECT * FROM co_contratti WHERE id='.prepare($idcontratto));
    $idanagrafica = $rs[0]['idanagrafica'];

    // Info riga pianificata
    $rs = $dbo->fetchArray('SELECT * FROM co_ordiniservizio WHERE idcontratto='.prepare($idcontratto).' AND id='.prepare($idordineservizio));
    $data = $rs[0]['data_scadenza'];
    $idimpianto = $rs[0]['id'];

    // Seleziono "Ordine di servizio" come tipo intervento
    $rs = $dbo->fetchArray("SELECT idtipointervento FROM in_tipiintervento WHERE descrizione='Ordine di servizio'");
    $idtipointervento = $rs[0]['idtipointervento'];

    // Spunto il tecnico di default assegnato all'impianto
    $rs = $dbo->fetchArray('SELECT idtecnico FROM my_impianti WHERE id='.prepare($idimpianto));
    $idtecnico = $rs[0]['idtecnico'] ?: '';
}

// Se sto pianificando un contratto, leggo tutti i dati del contratto per predisporre l'aggiunta intervento
elseif (!empty($idcontratto) && !empty($idcontratto_riga)) {
    $rs = $dbo->fetchArray('SELECT * FROM co_contratti WHERE id='.prepare($idcontratto));
    $idanagrafica = $rs[0]['idanagrafica'];

    // Info riga pianificata
    $rs = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($idcontratto).' AND id='.prepare($idcontratto_riga));
    $idtipointervento = $rs[0]['idtipointervento'];
    $data = (filter('data') !== null) ? filter('data') : $rs[0]['data_richiesta'];
    $richiesta = $rs[0]['richiesta'];
    $idsede = $rs[0]['idsede'];

    // Seleziono "In programmazione" come stato
    $rs = $dbo->fetchArray("SELECT * FROM in_statiintervento WHERE descrizione='In programmazione'");
    $idstatointervento = $rs[0]['idstatointervento'];
}

if (empty($data)) {
    if (filter('data') !== null) {
        $data = filter('data');
    } else {
        $data = date(Translator::getLocaleFormatter()->getDatePattern());
    }
}

$_SESSION['superselect']['idanagrafica'] = $idanagrafica;

// Calcolo del nuovo codice
$idintervento_template = get_var('Formato codice intervento');
$idintervento_template = str_replace('#', '%', $idintervento_template);

// Calcolo codice intervento successivo
$rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice=(SELECT MAX(CAST(codice AS SIGNED)) FROM in_interventi) AND codice LIKE '.prepare($idintervento_template).' ORDER BY codice DESC LIMIT 0,1');
$new_codice = get_next_code($rs[0]['codice'], 1, get_var('Formato codice intervento'));

if (empty($new_codice)) {
    $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice LIKE '.prepare($idintervento_template).' ORDER BY codice DESC LIMIT 0,1');
    $new_codice = get_next_code($rs[0]['codice'], 1, get_var('Formato codice intervento'));
}

?>

<form action="editor.php?id_module=$id_module$" method="post" id="add-form" onsubmit="if($(this).parsley().validate()) { return add_intervento(); }">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="id_module" value="<?php echo $id_module ?>">
	<input type="hidden" name="ref" value="<?php echo $_GET['ref']; ?>">
	<input type="hidden" name="backto" value="record-edit">

	<!-- DATI CLIENTE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Dati cliente'); ?></h3>
		</div>

		<div class="panel-body">

			<!-- RIGA 1 -->
			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "<?php echo $idanagrafica ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo Modules::getModule('Anagrafiche')['id']; ?>|tipoanagrafica=Cliente", "data-heavy": 0 ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo _('Sede'); ?>", "name": "idsede",  "value": "<?php echo $idsede ?>", "placheholder": "<?php echo _('Seleziona prima un cliente'); ?>...", "ajax-source": "sedi" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Per conto di'); ?>", "name": "idclientefinale", "value": "", "ajax-source": "clienti" ]}
				</div>
			</div>

			<!-- RIGA 2 -->
			<div class="row">
				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo _('Preventivo'); ?>", "name": "idpreventivo", "value": "<?php echo $idpreventivo ?>", "placeholder": "<?php echo _('Seleziona prima un cliente'); ?>...", "ajax-source": "preventivi" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo _('Contratto'); ?>", "name": "idcontratto", "value": "<?php echo $idcontratto ?>", "placeholder": "<?php echo _('Seleziona prima un cliente'); ?>...", "ajax-source": "contratti" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo _('Impianto'); ?>", "multiple": 1, "name": "idimpianti[]", "value": "<?php echo $idimpianto ?>", "placeholder": "<?php echo _('Seleziona prima un cliente'); ?>...", "ajax-source": "impianti" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-xs-12">
                    {[ "type": "select", "label": "<?php echo _('Componenti'); ?>", "multiple": 1, "name": "componenti[]", "value": "<?php echo $componenti ?>", "placeholder": "<?php echo _('Seleziona prima un impianto'); ?>...", "ajax-source": "componenti" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- DATI INTERVENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Dati intervento'); ?></h3>
		</div>

		<div class="panel-body">
			<!-- RIGA 3 -->
			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Codice intervento'); ?>", "name": "codice", "required": 1, "class": "text-center", "value": "<?php echo $new_codice ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data richiesta'); ?>", "name": "data_richiesta", "required": 1, "value": "-now-" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data intervento'); ?>", "name": "data", "required": 1, "value": "<?php echo $data ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Zona'); ?>", "name": "idzona", "values": "query=SELECT id, nome AS descrizione FROM an_zone ORDER BY nome", "value": "<?php echo $idzona ?>" ]}
				</div>
			</div>

			<!-- RIGA 4 -->
			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Tipo intervento'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento", "value": "<?php echo $idtipointervento ?>", "ajax-source": "tipiintervento" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Stato intervento'); ?>", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento", "value": "<?php echo $idstatointervento ?>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "time", "label": "<?php echo _('Orario inizio'); ?>", "name": "orario_inizio", "required": 1, "value": "<?php echo $orario_inizio ?>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "time", "label": "<?php echo _('Orario fine'); ?>", "name": "orario_fine", "required": 1, "value": "<?php echo $orario_fine ?>" ]}
				</div>
			</div>

			<!-- RIGA 5 -->
			<div class="row">
				<div class="col-md-12">
					{[ "type": "select", "label": "<?php echo _('Tecnici'); ?>", "multiple": "1", "name": "idtecnico[]", "required": 1, "ajax-source": "tecnici", "value": "<?php echo $idtecnico ?>" ]}
				</div>

				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo _('Richiesta'); ?>", "name": "richiesta", "required": 1, "value": "<?php echo $richiesta ?>", "extra": "style='max-height:80px; ' " ]}
				</div>

				<?php
                if (!empty($idcontratto_riga)) {
                    echo '<input type="hidden" name="idcontratto_riga" value="'.$idcontratto_riga.'">';
                }

                if (!empty($idordineservizio)) {
                    echo '<input type="hidden" name="idordineservizio" value="'.$idordineservizio.'">';
                }
                ?>
			</div>
		</div>
	</div>


	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script src="<?php echo $rootdir ?>/lib/init.js"></script>

<script type="text/javascript">
	$(document).ready(function(){
		$("#idsede").prop("disabled", true);
		$("#idpreventivo").prop("disabled", true);
		$("#idcontratto").prop("disabled", true);
		$("#idimpianti").prop("disabled", true);
		$("#componenti").prop("disabled", true);

        <?php
        if (!empty($idcontratto) && (!empty($idordineservizio) || !empty($idcontratto_riga))) {
            // Disabilito i campi che non devono essere modificati per poter collegare l'intervento all'ordine di servizio

            echo '
        $("#idcliente").prop("disabled", true);
        $("#idclientefinale").prop("disabled", true);
        $("#idzona").prop("disabled", true);
		$("#idtipointervento").prop("disabled", true);';
        }
?>
        $("#orario_inizio").on("dp.change", function (e) {
            $("#orario_fine").data("DateTimePicker").minDate(e.date);
        });
    });

	$('#idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val() ? true : false;

		$("#idsede").prop("disabled", value);
		$("#idsede").selectReset();

		$("#idpreventivo").prop("disabled", value);
		$("#idpreventivo").selectReset();

		$("#idcontratto").prop("disabled", value);
		$("#idcontratto").selectReset();

		$("#idimpianti").prop("disabled", value);
		$("#idimpianti").selectReset();
	});

	$('#idsede').change( function(){
		session_set('superselect,idsede', $(this).val(), 0);
		$("#idimpianti").selectReset();
	});

	$('#idpreventivo').change( function(){
		if($('#idcontratto').val() && $(this).val()){
            $("#idcontratto").selectReset();
        }

        if($(this).val()){
            $('#idtipointervento').selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
        }
	});

	$('#idcontratto').change( function(){
		if($('#idpreventivo').val() && $(this).val()){
            $("#idpreventivo").selectReset();
			$('input[name=idcontratto_riga]').val('');
		}
	});

	$('#idimpianti').change( function(){
		session_set('superselect,marticola', $(this).val(), 0);

        $("#componenti").prop("disabled", !$(this).val() ? true : false);
        $("#componenti").selectReset();
	});

	var ref = "<?php echo $get['ref'] ?>";

	function add_intervento(){
        // Se l'aggiunta intervento proviene dal calendario, faccio il submit via ajax e ricarico gli eventi...
        if(ref){
            $('#add-form').find('[type=submit]').prop("disabled", true).addClass("disabled");
            $('#add-form').find('input:disabled, select:disabled, textarea:disabled').removeAttr('disabled');

            $.post(globals.rootdir + '/actions.php', $('#add-form').serialize(), function(data,response){
                if(response=="success"){
                    // Se l'aggiunta intervento proviene dalla scheda di pianificazione ordini di servizio della dashboard, la ricarico
                    if(ref == "dashboard"){
                        $("#bs-popup").modal('hide');

                        // Aggiornamento elenco interventi da pianificare
                        $('#calendar').fullCalendar('refetchEvents');
                        $('#calendar').fullCalendar('render');
                    }

                    // Se l'aggiunta intervento proviene dai contratti, faccio il submit via ajax e ricarico la tabella dei contratti
                    else if(ref == "interventi_contratti"){
                        $('#elenco_interventi > tbody').load(globals.rootdir + '/modules/contratti/plugins/contratti.pianificazioneinterventi.php?op=get_interventi_pianificati&idcontratto=<?php echo $idcontratto ?>');
                        $("#bs-popup").modal('hide');
                    }
                }
            });

            return false;
        }
	}
</script>
