<?php

include_once __DIR__.'/../../core.php';

// Rimuovo session usate sui select combinati (sedi, preventivi, contratti, impianti)
unset($_SESSION['superselect']['idanagrafica']);
unset($_SESSION['superselect']['idsede']);

// Calcolo del nuovo codice
$new_codice = \Modules\Interventi\Intervento::getNextCodice($data);

// Se ho passato l'idanagrafica, carico il tipo di intervento di default
$idanagrafica = filter('idanagrafica');
$idsede = filter('idsede');

$idimpianto = null;
$idzona = null;
$idtipointervento = null;
$idstatointervento = null;
$richiesta = null;
$impianti = [];

// Come tecnico posso aprire attività solo a mio nome
if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
    $idtecnico = $user['idanagrafica'];
} else {
    $idtecnico = null;
}

if (!empty($idanagrafica)) {
    $rs = $dbo->fetchArray('SELECT idtipointervento_default, idzona FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idtipointervento = $rs[0]['idtipointervento_default'];
    $idzona = $rs[0]['idzona'];

    $stato = $dbo->fetchArray("SELECT * FROM in_statiintervento WHERE descrizione = 'In programmazione'");
    $idstatointervento = $stato['idstatointervento'];

    $richiesta = filter('richiesta');
}

// Calcolo orario di inizio e fine di default
if (null !== filter('orario_inizio') && '00:00:00' != filter('orario_inizio')) {
    $orario_inizio = filter('orario_inizio');
    $orario_fine = filter('orario_fine');
} else {
    $orario_inizio = date('H').':00:00';
    $orario_fine = date('H', time() + 60 * 60).':00:00';
}

$id_intervento = filter('id_intervento');
$idcontratto = filter('idcontratto');
$idcontratto_riga = filter('idcontratto_riga');

// Se sto pianificando un contratto, leggo tutti i dati del contratto per predisporre l'aggiunta intervento
if (!empty($idcontratto) && !empty($idcontratto_riga)) {
    $rs = $dbo->fetchArray('SELECT *, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS idzona FROM co_contratti WHERE id='.prepare($idcontratto));
    $idanagrafica = $rs[0]['idanagrafica'];
    $idzona = $rs[0]['idzona'];

    // Info riga pianificata
    $rs = $dbo->fetchArray('SELECT *, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = co_promemoria.idtipointervento) AS tempo_standard  FROM co_promemoria WHERE idcontratto='.prepare($idcontratto).' AND id='.prepare($idcontratto_riga));
    $idtipointervento = $rs[0]['idtipointervento'];
    $data = (null !== filter('data')) ? filter('data') : $rs[0]['data_richiesta'];
    $richiesta = $rs[0]['richiesta'];
    $idsede = $rs[0]['idsede'];
    $idimpianti = $rs[0]['idimpianti'];

    // aumento orario inizio del tempo standard definito dalla tipologia dell'intervento (PRESO DAL PROMEMORIA)
    if (!empty($rs[0]['tempo_standard'])) {
        $orario_fine = date('H:i:s', strtotime($orario_inizio) + ((60 * 60) * $rs[0]['tempo_standard']));
    }

    // se gli impianti non sono stati definiti nel promemoria, carico tutti gli impianti a contratto
    if (empty($idimpianti)) {
        $rs = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto='.prepare($idcontratto));
        $idimpianto = implode(',', array_column($rs, 'idimpianto'));
    } else {
        $idimpianto = $idimpianti;
        // Spunto il tecnico di default assegnato all'impianto
        $rs = $dbo->fetchArray('SELECT idtecnico FROM my_impianti WHERE id='.prepare($idimpianto));
        $idtecnico = $rs[0]['idtecnico'] ?: '';
    }

    // Seleziono "In programmazione" come stato
    $rs = $dbo->fetchArray("SELECT * FROM in_statiintervento WHERE descrizione = 'In programmazione'");
    $idstatointervento = $rs[0]['idstatointervento'];
}

// Intervento senza sessioni
elseif (!empty($id_intervento)) {
    // Info riga pianificata
    $rs = $dbo->fetchArray('SELECT *, (SELECT idcontratto FROM co_promemoria WHERE idintervento=in_interventi.id LIMIT 0,1) AS idcontratto, in_interventi.id_preventivo as idpreventivo, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = in_interventi.idtipointervento) AS tempo_standard  FROM in_interventi WHERE id='.prepare($id_intervento));
    $idtipointervento = $rs[0]['idtipointervento'];
    $data = (null !== filter('data')) ? filter('data') : $rs[0]['data_richiesta'];
    $data_richiesta = $rs[0]['data_richiesta'];
    $data_scadenza = $rs[0]['data_scadenza'];
    $richiesta = $rs[0]['richiesta'];
    $idsede = $rs[0]['idsede'];
    $idanagrafica = $rs[0]['idanagrafica'];
    $idclientefinale = $rs[0]['idclientefinale'];
    $idstatointervento = $rs[0]['idstatointervento'];
    $idcontratto = $rs[0]['idcontratto'];
    $idpreventivo = $rs[0]['idpreventivo'];
    $idzona = $rs[0]['idzona'];

    // Aumento orario inizio del tempo standard definito dalla tipologia dell'intervento (PRESO DAL PROMEMORIA)
    if (!empty($rs[0]['tempo_standard'])) {
        $orario_fine = date('H:i:s', strtotime($orario_inizio) + ((60 * 60) * $rs[0]['tempo_standard']));
    }

    $rs = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_interventi WHERE idintervento='.prepare($id_intervento));
    $idimpianto = implode(',', array_column($rs, 'idimpianto'));
}

if (empty($data_fine)) {
    if (null !== filter('data_fine')) {
        $data_fine = filter('data_fine');
    } else {
        $data_fine = date('Y-m-d');
    }
}

if (empty($data)) {
    if (null !== filter('data')) {
        $data = filter('data');
    } else {
        $data = date('Y-m-d');
    }
}

$data_fine = $data_fine ?: $data;

$_SESSION['superselect']['idanagrafica'] = $idanagrafica;

$orario_inizio = $data.' '.$orario_inizio;
$orario_fine = $data_fine.' '.$orario_fine;

?>

<form action="" method="post" id="add-form" onsubmit="if($(this).parsley().validate()) { return add_intervento(); }">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="ref" value="<?php echo get('ref'); ?>">
	<input type="hidden" name="backto" value="record-edit">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

<?php
if (!empty($idcontratto_riga)) {
    echo '<input type="hidden" name="idcontratto_riga" value="'.$idcontratto_riga.'">';
}

if (!empty($id_intervento)) {
    echo '<input type="hidden" name="id_intervento" value="'.$id_intervento.'">';
}
?>

	<!-- DATI CLIENTE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati cliente'); ?></h3>
		</div>

		<div class="panel-body">

			<!-- RIGA 1 -->
			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "<?php echo $idanagrafica; ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Cliente&readonly_tipo=1||<?php echo (empty($idanagrafica)) ? '' : 'disabled'; ?>", "data-heavy": 0, "readonly": "<?php echo (empty($idanagrafica)) ? 0 : 1; ?>" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Sede destinazione'); ?>", "name": "idsede_destinazione", "value": "<?php echo $idsede; ?>", "placheholder": "<?php echo tr('Seleziona prima un cliente'); ?>...", "ajax-source": "sedi" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Per conto di'); ?>", "name": "idclientefinale", "value": "<?php echo $idclientefinale; ?>", "ajax-source": "clienti" ]}
				</div>
			</div>

			<!-- RIGA 2 -->
			<div class="row">
                <div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Zona'); ?>", "name": "idzona", "values": "query=SELECT id, CONCAT_WS( ' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY nome", "value": "<?php echo $idzona; ?>", "placeholder": "<?php echo tr('Nessuna zona'); ?>", "help":"<?php echo 'La zona viene definita automaticamente in base al cliente selezionato'; ?>.", "extra": "readonly", "value": "<?php echo $idzona; ?>" ]}
                </div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Preventivo'); ?>", "name": "idpreventivo", "value": "<?php echo $idpreventivo; ?>"<?php echo !empty($idanagrafica) ? '' : ', "placeholder": "'.tr('Seleziona prima un cliente').'..."'; ?>, "ajax-source": "preventivi", "readonly": "<?php echo (empty($idcontratto)) ? 0 : 1; ?>" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Contratto'); ?>", "name": "idcontratto", "value": "<?php echo $idcontratto; ?>"<?php echo !empty($idanagrafica) ? '' : ', "placeholder": "'.tr('Seleziona prima un cliente').'..."'; ?>, "ajax-source": "contratti", "readonly": "<?php echo (empty($idcontratto)) ? 0 : 1; ?>" ]}
				</div>
			</div>

			<div class="row">
                <div class="col-md-6" id='impianti'>
                    {[ "type": "select", "label": "<?php echo tr('Impianto'); ?>", "multiple": 1, "name": "idimpianti[]", "value": "<?php echo $idimpianto; ?>"<?php echo !empty($idanagrafica) ? '' : ', "placeholder": "'.tr('Seleziona prima un cliente').'..."'; ?>, "ajax-source": "impianti-cliente", "icon-after": "add|<?php echo Modules::get('MyImpianti')['id']; ?>|source=Attività||<?php echo (intval($idimpianto)) ? '' : 'disabled'; ?>", "data-heavy": 0 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Componenti'); ?>", "multiple": 1, "name": "componenti[]", "placeholder": "<?php echo tr('Seleziona prima un impianto'); ?>...", "ajax-source": "componenti" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- DATI INTERVENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati intervento'); ?></h3>
		</div>

		<div class="panel-body">
			<!-- RIGA 3 -->
			<div class="row">
                <div class="col-md-3">
                    {[ "type": "timestamp", "label": "<?php echo tr('Data/ora richiesta'); ?>", "name": "data_richiesta", "required": 1, "value": "<?php echo $data_richiesta ?: '-now-'; ?>" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "timestamp", "label": "<?php echo tr('Data/ora scadenza'); ?>", "name": "data_scadenza", "required": 0, "value": "<?php echo $data_scadenza; ?>" ]}
                </div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Tipo attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "<?php echo $idtipointervento; ?>", "ajax-source": "tipiintervento" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL", "value": "<?php echo $idstatointervento; ?>" ]}
				</div>
			</div>

			<!-- RIGA 5 -->
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Richiesta'); ?>", "name": "richiesta", "required": 1, "value": "<?php echo $richiesta; ?>", "extra": "style='max-height:80px; ' " ]}
				</div>
			</div>
		</div>
	</div>

	<!-- DATI INTERVENTO -->
    <div class="box box-primary collapsable <?php echo get('ref') ? '' : 'collapsed-box'; ?>">
        <div class="box-header with-border">
			<h3 class="box-title"><?php echo tr('Ore di lavoro'); ?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-<?php echo get('ref') ? 'minus' : 'plus'; ?>"></i>
                </button>
            </div>
		</div>

		<div class="box-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "timestamp", "label": "<?php echo tr('Inizio attività'); ?>", "name": "orario_inizio", "required": <?php echo get('ref') ? 1 : 0; ?>, "value": "<?php echo $orario_inizio; ?>" ]}
				</div>

                <div class="col-md-6">
					{[ "type": "timestamp", "label": "<?php echo tr('Fine attività'); ?>", "name": "orario_fine", "required": <?php echo get('ref') ? 1 : 0; ?>, "value": "<?php echo $orario_fine; ?>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "select", "label": "<?php echo tr('Tecnici'); ?>", "multiple": "1", "name": "idtecnico[]", "required": <?php echo get('ref') ? 1 : 0; ?>, "ajax-source": "tecnici", "value": "<?php echo $idtecnico; ?>", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Tecnico||<?php echo (empty($idtecnico)) ? '' : 'disabled'; ?>" ]}
				</div>
			</div>
		</div>
	</div>


	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script>$(document).ready(init)</script>

<script type="text/javascript">
	$(document).ready(function(){
        if(!$("#modals > div #idanagrafica").val()){
            $("#modals > div #idsede_destinazione").prop("disabled", true);
            $("#modals > div #idpreventivo").prop("disabled", true);
            $("#modals > div #idcontratto").prop("disabled", true);
            $("#modals > div #idimpianti").prop("disabled", true);
            $("#modals > div #componenti").prop("disabled", true);

        <?php
        if (!empty($idcontratto) && (!empty($idordineservizio) || !empty($idcontratto_riga))) {
            // Disabilito i campi che non devono essere modificati per poter collegare l'intervento all'ordine di servizio

            echo '
            $("#modals > div #idanagrafica").prop("disabled", true);
            $("#modals > div #idclientefinale").prop("disabled", true);
            $("#modals > div #idzona").prop("disabled", true);
            $("#modals > div #idtipointervento").prop("disabled", true);
            $("#modals > div #impianti").find("button").prop("disabled", true);';
        }
?>
        }

<?php

if (!empty($id_intervento)) {
    echo '
        $("#modals > div #idsede_destinazione").prop("disabled", true);
        $("#modals > div #idpreventivo").prop("disabled", true);
        $("#modals > div #idcontratto").prop("disabled", true);
        $("#modals > div #idimpianti").prop("disabled", true);
        $("#modals > div #componenti").prop("disabled", true);
        $("#modals > div #idanagrafica").prop("disabled", true);
        $("#modals > div #idanagrafica").find("button").prop("disabled", true);
        $("#modals > div #idclientefinale").prop("disabled", true);
        $("#modals > div #idzona").prop("disabled", true);
        $("#modals > div #idtipointervento").prop("disabled", true);
        $("#modals > div #idstatointervento").prop("disabled", true);
        $("#modals > div #richiesta").prop("disabled", true);
        $("#modals > div #data_richiesta").prop("disabled", true);
        $("#modals > div #impianti").find("button").prop("disabled", true);
    ';
}
?>

		// Quando modifico orario inizio, allineo anche l'orario fine
        $("#modals > div #orario_inizio").on("dp.change", function (e) {
            $("#modals > div #orario_fine").data("DateTimePicker").minDate(e.date);
            $("#modals > div #orario_fine").change();
        });

        // Refresh modulo dopo la chiusura di una pianificazione attività derivante dalle attività
        // da pianificare, altrimenti il promemoria non si vede più nella lista a destra
		// TODO: da gestire via ajax
        if( $('input[name=idcontratto_riga]').val() != undefined ){
            $('#modals > div button.close').on('click', function(){
                location.reload();
            });
        }
    });

	$('#modals > div #idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val() ? true : false;
        var placeholder = !$(this).val() ? "<?php echo tr('Seleziona prima un cliente...'); ?>" : "<?php echo tr("Seleziona un'opzione"); ?>";

		$("#modals > div #idsede_destinazione").prop("disabled", value);
		$("#modals > div #idsede_destinazione").selectReset(placeholder);

		$("#modals > div #idpreventivo").prop("disabled", value);
		$("#modals > div #idpreventivo").selectReset(placeholder);

		$("#modals > div #idcontratto").prop("disabled", value);
		$("#modals > div #idcontratto").selectReset(placeholder);

		$("#modals > div #idimpianti").prop("disabled", value);
        $("#modals > div #impianti").find("button").prop("disabled", value);
		$("#modals > div #idimpianti").selectReset(placeholder);

		if (($(this).val())) {
			if (($(this).selectData().idzona)){
				$('#modals > div #idzona').val($(this).selectData().idzona).change();

			}else{
				$('#modals > div #idzona').val('').change();
			}
			// session_set('superselect,idzona', $(this).selectData().idzona, 0);
		}

        // Settaggio tipo intervento da anagrafica
        $('#modals > div #idtipointervento').selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
	});

	$('#modals > div #idsede_destinazione').change( function(){
		session_set('superselect,idsede_destinazione', $(this).val(), 0);
		$("#modals > div #idimpianti").selectReset();

		if (($(this).val())) {
			if (($(this).selectData().idzona)){
				$('#modals > div #idzona').val($(this).selectData().idzona).change();
			}else{
				$('#modals > div #idzona').val('').change();
			}
			// session_set('superselect,idzona', $(this).selectData().idzona, 0);
		}
	});

	$('#modals > div #idpreventivo').change( function(){
		if($('#modals > div #idcontratto').val() && $(this).val()){
            $("#modals > div #idcontratto").selectReset();
        }

        if($(this).val()){
			$('#modals > div #idtipointervento').selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
        }
	});

	$('#modals > div #idcontratto').change( function(){
		if($('#modals > div #idpreventivo').val() && $(this).val()){
            $("#modals > div #idpreventivo").selectReset();
			$('input[name=idcontratto_riga]').val('');
		}
	});

	$('#modals > div #idimpianti').change( function(){
		session_set('superselect,marticola', $(this).val(), 0);

        $("#modals > div #componenti").prop("disabled", !$(this).val() ? true : false);
        $("#modals > div #componenti").selectReset();
	});

	// tempo standard
    // TODO: tempo_standard da preventivo e contratto attraverso selectData() relativi
	$('#modals > div #idtipointervento').change( function(){

		if ($(this).selectData() && (($(this).selectData().tempo_standard)>0) && ('<?php echo filter('orario_fine'); ?>' == '')){

            orario_inizio = moment($('#modals > div #orario_inizio').val(), globals.timestamp_format, globals.locale).isValid() ? $('#modals > div #orario_inizio').val() : false;

            //da sistemare
            if (orario_inizio){
                tempo_standard = ($(this).selectData().tempo_standard)*60;
                orario_fine = moment(orario_inizio).add(tempo_standard, 'm');
                $('#modals > div #orario_fine').val(moment(orario_fine).format(globals.timestamp_format));
            }
		}

	});

	$('#modals > div #idtecnico').change( function(){
		<?php if (!get('ref')) {
    ?>
	   var value = ($(this).val()>0) ? true : false;
		$('#modals > div #orario_inizio').prop("required", value);
		$('#modals > div #orario_fine').prop("required", value);
		$('#modals > div #data').prop("required", value);
		<?php
} ?>
	});

	var ref = "<?php echo get('ref'); ?>";

	function add_intervento(){
        // Se l'aggiunta intervento proviene dal calendario, faccio il submit via ajax e ricarico gli eventi...
        if(ref){
            $('#add-form').find('[type=submit]').prop("disabled", true).addClass("disabled");
            $('#add-form').find('input:disabled, select:disabled, textarea:disabled').removeAttr('disabled');

            $.post(globals.rootdir + '/actions.php?id_module=<?php echo Modules::get('Interventi')['id']; ?>', $('#add-form').serialize(), function(data,response){
                if(response=="success"){
                    // Se l'aggiunta intervento proviene dalla scheda di pianificazione ordini di servizio della dashboard, la ricarico
                    if(ref == "dashboard"){
                        $("#modals > div").modal('hide');

                        // Aggiornamento elenco interventi da pianificare
                        $('#calendar').fullCalendar('refetchEvents');
                        $('#calendar').fullCalendar('render');
                    }

                    // Se l'aggiunta intervento proviene dai contratti, faccio il submit via ajax e ricarico la tabella dei contratti
                    else if(ref == "interventi_contratti"){

						$("#modals > div").modal('hide');
						parent.window.location.reload();
						//TODO: da gestire via ajax
						//$('#elenco_interventi > tbody').load(globals.rootdir + '/modules/contratti/plugins/contratti.pianificazioneinterventi.php?op=get_interventi_pianificati&idcontratto=<?php echo $idcontratto; ?>');

                    }
                }
            });

            return false;
        }
	}
</script>
