<?php

include_once __DIR__.'/../../core.php';

// Rimuovo session usate sui select combinati (sedi, preventivi, contratti, impianti)
unset($_SESSION['superselect']['idanagrafica']);
unset($_SESSION['superselect']['idsede']);
unset($_SESSION['superselect']['non_fatturato']);

// Calcolo del nuovo codice
$idintervento_template = setting('Formato codice intervento');
$idintervento_template = str_replace('#', '%', $idintervento_template);

$rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice=(SELECT MAX(CAST(codice AS SIGNED)) FROM in_interventi) AND codice LIKE '.prepare(Util\Generator::complete($idintervento_template)).' ORDER BY codice DESC LIMIT 0,1');
if (!empty($rs[0]['codice'])) {
    $new_codice = Util\Generator::generate(setting('Formato codice intervento'), $rs[0]['codice']);
}

if (empty($new_codice)) {
    $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice LIKE '.prepare(Util\Generator::complete($idintervento_template)).' ORDER BY codice DESC LIMIT 0,1');
    $new_codice = Util\Generator::generate(setting('Formato codice intervento'), $rs[0]['codice']);
}

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
    $idstatointervento = 'WIP';
    $richiesta = filter('richiesta');
}

// Calcolo orario di inizio e fine di default
if (null !== filter('orario_inizio') && '00:00:00' != filter('orario_inizio')) {
    $orario_inizio = filter('orario_inizio');
    $orario_fine = filter('orario_fine');
} else {
    $orario_inizio = date('H').':00';
    $orario_fine = date('H', time() + 60 * 60).':00';
}

// Se sto pianificando un contratto, leggo tutti i dati del contratto per predisporre l'aggiunta intervento
//ref (intervento,promemoria,ordine)

$id_intervento = filter('id_intervento');
$idcontratto = filter('idcontratto');
$idcontratto_riga = filter('idcontratto_riga');
$idordineservizio = filter('idordineservizio');

if (!empty($idcontratto) && !empty($idordineservizio)) {
    $rs = $dbo->fetchArray('SELECT *, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS idzona FROM co_contratti WHERE id='.prepare($idcontratto));
    $idanagrafica = $rs[0]['idanagrafica'];
    $idzona = $rs[0]['idzona'];

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
    $rs = $dbo->fetchArray("SELECT * FROM in_statiintervento WHERE idstatointervento='WIP'");
    $idstatointervento = $rs[0]['idstatointervento'];
}

// Intervento senza sessioni
elseif (!empty($id_intervento)) {
    // Info riga pianificata
    $rs = $dbo->fetchArray('SELECT *, (SELECT idcontratto FROM co_promemoria WHERE idintervento=in_interventi.id LIMIT 0,1) AS idcontratto, in_interventi.id_preventivo as idpreventivo, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = in_interventi.idtipointervento) AS tempo_standard  FROM in_interventi WHERE id='.prepare($id_intervento));
    $idtipointervento = $rs[0]['idtipointervento'];
    $data = (null !== filter('data')) ? filter('data') : $rs[0]['data_richiesta'];
    $data_richiesta = $rs[0]['data_richiesta'];
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

if (empty($data)) {
    if (null !== filter('data')) {
        $data = filter('data');
    } else {
        $data = date(formatter()->getDatePattern());
    }
}

$_SESSION['superselect']['idanagrafica'] = $idanagrafica;

$orario_inizio = $data.' '.$orario_inizio;
$orario_fine = $data.' '.$orario_fine;

?>

<form action="" method="post" id="add-form" onsubmit="if($(this).parsley().validate()) { return add_intervento(); }">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="ref" value="<?php echo get('ref'); ?>">
	<input type="hidden" name="backto" value="record-edit">
<?php
if (!empty($idcontratto_riga)) {
    echo '<input type="hidden" name="idcontratto_riga" value="'.$idcontratto_riga.'">';
}

if (!empty($idordineservizio)) {
    echo '<input type="hidden" name="idordineservizio" value="'.$idordineservizio.'">';
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
					{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "<?php echo $idanagrafica; ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=Cliente||<?php echo (empty($idanagrafica)) ? '' : 'disabled'; ?>", "data-heavy": 0 ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "value": "<?php echo $idsede; ?>", "placheholder": "<?php echo tr('Seleziona prima un cliente'); ?>...", "ajax-source": "sedi" ]}
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
                    {[ "type": "select", "label": "<?php echo tr('Preventivo'); ?>", "name": "idpreventivo", "value": "<?php echo $idpreventivo; ?>"<?php echo !empty($idanagrafica) ? '' : ', "placeholder": "'.tr('Seleziona prima un cliente').'..."'; ?>, "ajax-source": "preventivi" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Contratto'); ?>", "name": "idcontratto", "value": "<?php echo $idcontratto; ?>"<?php echo !empty($idanagrafica) ? '' : ', "placeholder": "'.tr('Seleziona prima un cliente').'..."'; ?>, "ajax-source": "contratti" ]}
				</div>
			</div>

			<div class="row">
                <div class="col-md-6" id='impianti'>
                    {[ "type": "select", "label": "<?php echo tr('Impianto'); ?>", "multiple": 1, "name": "idimpianti[]", "value": "<?php echo $idimpianto; ?>"<?php echo !empty($idanagrafica) ? '' : ', "placeholder": "'.tr('Seleziona prima un cliente').'..."'; ?>, "ajax-source": "impianti-cliente", "icon-after": "add|<?php echo Modules::get('MyImpianti')['id']; ?>|source=Attività|<?php echo (empty($idimpianto)) ? '' : 'disabled'; ?>", "data-heavy": 0 ]}
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
                <div class="col-md-4">
                    {[ "type": "timestamp", "label": "<?php echo tr('Data e ora richiesta'); ?>", "name": "data_richiesta", "required": 1, "value": "<?php echo $data_richiesta ?: '-now-'; ?>" ]}
                </div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Tipo attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "<?php echo $idtipointervento; ?>", "ajax-source": "tipiintervento" ]}
				</div>

				<div class="col-md-4">
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
					{[ "type": "select", "label": "<?php echo tr('Tecnici'); ?>", "multiple": "1", "name": "idtecnico[]", "required": <?php echo get('ref') ? 1 : 0; ?>, "ajax-source": "tecnici", "value": "<?php echo $idtecnico; ?>" ]}
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

<script src="<?php echo $rootdir; ?>/lib/init.js"></script>

<script type="text/javascript">
	$(document).ready(function(){
        if(!$("#bs-popup #idanagrafica").val()){
            $("#bs-popup #idsede").prop("disabled", true);
            $("#bs-popup #idpreventivo").prop("disabled", true);
            $("#bs-popup #idcontratto").prop("disabled", true);
            $("#bs-popup #idimpianti").prop("disabled", true);
            $("#bs-popup #componenti").prop("disabled", true);

        <?php
        if (!empty($idcontratto) && (!empty($idordineservizio) || !empty($idcontratto_riga))) {
            // Disabilito i campi che non devono essere modificati per poter collegare l'intervento all'ordine di servizio

            echo '
            $("#bs-popup #idanagrafica").prop("disabled", true);
            $("#bs-popup #idclientefinale").prop("disabled", true);
            $("#bs-popup #idzona").prop("disabled", true);
            $("#bs-popup #idtipointervento").prop("disabled", true);
            $("#bs-popup #impianti").find("button").prop("disabled", true);';
        }
?>
        }

<?php

if (!empty($id_intervento)) {
    echo '
        $("#bs-popup #idsede").prop("disabled", true);
        $("#bs-popup #idpreventivo").prop("disabled", true);
        $("#bs-popup #idcontratto").prop("disabled", true);
        $("#bs-popup #idimpianti").prop("disabled", true);
        $("#bs-popup #componenti").prop("disabled", true);
        $("#bs-popup #idanagrafica").prop("disabled", true);
        $("#bs-popup #idanagrafica").find("button").prop("disabled", true);
        $("#bs-popup #idclientefinale").prop("disabled", true);
        $("#bs-popup #idzona").prop("disabled", true);
        $("#bs-popup #idtipointervento").prop("disabled", true);
        $("#bs-popup #idstatointervento").prop("disabled", true);
        $("#bs-popup #richiesta").prop("disabled", true);
        $("#bs-popup #data_richiesta").prop("disabled", true);
        $("#bs-popup #impianti").find("button").prop("disabled", true);
    ';
}
?>

		// Quando modifico orario inizio, allineo anche l'orario fine
        $("#bs-popup #orario_inizio").on("dp.change", function (e) {
			$("#bs-popup #orario_fine").data("DateTimePicker").minDate(e.date).format(globals.timestampFormat);
        });

        // Refresh modulo dopo la chiusura di una pianificazione attività derivante dalle attività
        // da pianificare, altrimenti il promemoria non si vede più nella lista a destra
		// TODO: da gestire via ajax
        if( $('input[name=idcontratto_riga]').val() != undefined ){
            $('#bs-popup button.close').on('click', function(){
                location.reload();
            });
        }
    });

	$('#bs-popup #idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val() ? true : false;
        var placeholder = !$(this).val() ? '<?php echo tr('Seleziona prima un cliente...'); ?>' : '<?php echo tr("-Seleziona un\'opzione-"); ?>';

		$("#bs-popup #idsede").prop("disabled", value);
		$("#bs-popup #idsede").selectReset(placeholder);

		$("#bs-popup #idpreventivo").prop("disabled", value);
		$("#bs-popup #idpreventivo").selectReset(placeholder);

		$("#bs-popup #idcontratto").prop("disabled", value);
		$("#bs-popup #idcontratto").selectReset(placeholder);

		$("#bs-popup #idimpianti").prop("disabled", value);
        $("#bs-popup #impianti").find("button").prop("disabled", value);
		$("#bs-popup #idimpianti").selectReset(placeholder);

		if (($(this).val())) {
			if (($(this).selectData().idzona)){
				$('#bs-popup #idzona').val($(this).selectData().idzona).change();

			}else{
				$('#bs-popup #idzona').val('').change();
			}
			// session_set('superselect,idzona', $(this).selectData().idzona, 0);
		}
	});

	$('#bs-popup #idsede').change( function(){
		session_set('superselect,idsede', $(this).val(), 0);
		$("#bs-popup #idimpianti").selectReset();

		if (($(this).val())) {
			if (($(this).selectData().idzona)){
				$('#bs-popup #idzona').val($(this).selectData().idzona).change();
			}else{
				$('#bs-popup #idzona').val('').change();
			}
			// session_set('superselect,idzona', $(this).selectData().idzona, 0);
		}
	});

	$('#bs-popup #idpreventivo').change( function(){
		if($('#bs-popup #idcontratto').val() && $(this).val()){
            $("#bs-popup #idcontratto").selectReset();
        }

        if($(this).val()){
            //TODO: disattivato perché genera problemi con il change successivo di iditpointervento per il tempo standard*
			$('#bs-popup #idtipointervento').selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
        }
	});

	$('#bs-popup #idcontratto').change( function(){
		if($('#bs-popup #idpreventivo').val() && $(this).val()){
            $("#bs-popup #idpreventivo").selectReset();
			$('input[name=idcontratto_riga]').val('');
		}
	});

	$('#bs-popup #idimpianti').change( function(){
		session_set('superselect,marticola', $(this).val(), 0);

        $("#bs-popup #componenti").prop("disabled", !$(this).val() ? true : false);
        $("#bs-popup #componenti").selectReset();
	});

	// tempo standard
    // TODO: tempo_standard da preventivo e contratto attraverso selectData() relativi
	$('#bs-popup #idtipointervento').change( function(){

		if ($(this).selectData() && (($(this).selectData().tempo_standard)>0) && ('<?php echo filter('orario_fine'); ?>' == '')){
			tempo_standard = $(this).selectData().tempo_standard;

			data = moment($('#bs-popup #orario_inizio').val(), globals.timestampFormat);
			orario_fine = data.add(tempo_standard, 'hours');
			$('#bs-popup #orario_fine').val(orario_fine.format(globals.timestampFormat));
		}

	});

	$('#bs-popup #idtecnico').change( function(){
		<?php if (!get('ref')) {
    ?>
	   var value = ($(this).val()>0) ? true : false;
		$('#bs-popup #orario_inizio').prop("required", value);
		$('#bs-popup #orario_fine').prop("required", value);
		$('#bs-popup #data').prop("required", value);
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
                        $("#bs-popup").modal('hide');

                        // Aggiornamento elenco interventi da pianificare
                        $('#calendar').fullCalendar('refetchEvents');
                        $('#calendar').fullCalendar('render');
                    }

                    // Se l'aggiunta intervento proviene dai contratti, faccio il submit via ajax e ricarico la tabella dei contratti
                    else if(ref == "interventi_contratti"){

						$("#bs-popup").modal('hide');
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
