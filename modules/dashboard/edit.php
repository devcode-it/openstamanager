<?php

include_once __DIR__.'/../../core.php';

// Impostazione filtri di default a tutte le selezioni la prima volta
if (!isset($_SESSION['dashboard']['idtecnici'])) {
    $rs = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica AS id FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE deleted_at IS NULL AND descrizione='Tecnico'");

    $_SESSION['dashboard']['idtecnici'] = ["'-1'"];

    for ($i = 0; $i < count($rs); ++$i) {
        $_SESSION['dashboard']['idtecnici'][] = "'".$rs[$i]['id']."'";
    }
}

if (!isset($_SESSION['dashboard']['idstatiintervento'])) {
    $rs = $dbo->fetchArray('SELECT idstatointervento AS id, descrizione FROM in_statiintervento WHERE deleted_at IS NULL');

    $_SESSION['dashboard']['idstatiintervento'] = ["'-1'"];

    for ($i = 0; $i < count($rs); ++$i) {
        $_SESSION['dashboard']['idstatiintervento'][] = "'".$rs[$i]['id']."'";
    }
}

if (!isset($_SESSION['dashboard']['idtipiintervento'])) {
    $rs = $dbo->fetchArray('SELECT idtipointervento AS id, descrizione FROM in_tipiintervento');

    $_SESSION['dashboard']['idtipiintervento'] = ["'-1'"];

    for ($i = 0; $i < count($rs); ++$i) {
        $_SESSION['dashboard']['idtipiintervento'][] = "'".$rs[$i]['id']."'";
    }
}

if (!isset($_SESSION['dashboard']['idzone'])) {
    $rs = $dbo->fetchArray('SELECT id, descrizione FROM an_zone');

    $_SESSION['dashboard']['idzone'] = ["'-1'"];

    // "Nessuna zona" di default
    $_SESSION['dashboard']['idzone'][] = "'0'";

    for ($i = 0; $i < count($rs); ++$i) {
        $_SESSION['dashboard']['idzone'][] = "'".$rs[$i]['id']."'";
    }
}

// Stati intervento
$checks = '';
$count = 0;
$total = 0;

$rs = $dbo->fetchArray('SELECT idstatointervento AS id, descrizione, colore FROM in_statiintervento WHERE deleted_at IS NULL ORDER BY descrizione ASC');
$total = count($rs);

$allchecksstati = '';
for ($i = 0; $i < count($rs); ++$i) {
    $attr = '';

    foreach ($_SESSION['dashboard']['idstatiintervento'] as $idx => $val) {
        if ($val == "'".$rs[$i]['id']."'") {
            $attr = 'checked="checked"';
            ++$count;
        }
    }

    $checks .= "<li><input type='checkbox' id='idstato_".$rs[$i]['id']."' value=\"".$rs[$i]['id'].'" '.$attr." onclick=\"$.when ( session_set_array( 'dashboard,idstatiintervento', '".$rs[$i]['id']."' ) ).promise().then(function( ){ $('#calendar').fullCalendar('refetchEvents'); });  update_counter( 'idstati_count', $('#idstati_ul').find('input:checked').length ); \"> <label for='idstato_".$rs[$i]['id']."'> <span class='badge' style=\"color:".color_inverse($rs[$i]['colore']).'; background:'.$rs[$i]['colore'].';">'.$rs[$i]['descrizione']."</span></label></li>\n";

    $allchecksstati .= "session_set_array( 'dashboard,idstatiintervento', '".$rs[$i]['id']."', 0 ); ";
}

if ($count == $total) {
    $class = 'btn-success';
} elseif ($count == 0) {
    $class = 'btn-danger';
} else {
    $class = 'btn-warning';
}

if ($total == 0) {
    $class = 'btn-primary disabled';
}
?>

<!-- Filtri -->
<div class="row">
	<!-- STATI INTERVENTO -->
	<div class="dropdown col-md-3">
		<a class="btn <?php echo $class; ?> btn-block" data-toggle="dropdown" href="javascript:;" id="idstati_count"><i class="fa fa-filter"></i> <?php echo tr('Stati intervento'); ?> (<?php echo $count.'/'.$total; ?>) <i class="caret"></i></a>

		<ul class="dropdown-menu" role="menu" id="idstati_ul">
			<?php echo $checks; ?>
			<div class="btn-group pull-right">
				<button  id="selectallstati" onclick="<?php echo $allchecksstati; ?>" class="btn btn-primary btn-xs" type="button"><?php echo tr('Tutti'); ?></button>
				<button id="deselectallstati" class="btn btn-danger btn-xs" type="button"><i class="fa fa-times"></i></button>
			</div>

		</ul>
	</div>

<?php
// Tipi intervento
$checks = '';
$count = 0;
$total = 0;

$rs = $dbo->fetchArray('SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC');
$total = count($rs);

$allcheckstipi = '';
for ($i = 0; $i < count($rs); ++$i) {
    $attr = '';

    foreach ($_SESSION['dashboard']['idtipiintervento'] as $idx => $val) {
        if ($val == "'".$rs[$i]['id']."'") {
            $attr = 'checked="checked"';
            ++$count;
        }
    }

    $checks .= "<li><input type='checkbox' id='idtipo_".$rs[$i]['id']."' value=\"".$rs[$i]['id'].'" '.$attr." onclick=\"$.when ( session_set_array( 'dashboard,idtipiintervento', '".$rs[$i]['id']."' ) ).promise().then(function( ){ $('#calendar').fullCalendar('refetchEvents');  }); update_counter( 'idtipi_count', $('#idtipi_ul').find('input:checked').length ); \"> <label for='idtipo_".$rs[$i]['id']."'> ".$rs[$i]['descrizione']."</label></li>\n";

    $allcheckstipi .= "session_set_array( 'dashboard,idtipiintervento', '".$rs[$i]['id']."', 0 ); ";
}

if ($count == $total) {
    $class = 'btn-success';
} elseif ($count == 0) {
    $class = 'btn-danger';
} else {
    $class = 'btn-warning';
}

if ($total == 0) {
    $class = 'btn-primary disabled';
}
?>
	<!-- TIPI DI INTERVENTO -->
	<div class="dropdown col-md-3">
		<a class="btn <?php echo $class; ?> btn-block" data-toggle="dropdown" href="javascript:;" id="idtipi_count"><i class="fa fa-filter"></i> <?php echo tr('Tipi intervento'); ?> (<?php echo $count.'/'.$total; ?>) <i class="caret"></i></a>

		<ul class="dropdown-menu" role="menu" id="idtipi_ul">
			<?php echo $checks; ?>
			<div class="btn-group pull-right">
				<button  id="selectalltipi" onclick="<?php echo $allcheckstipi; ?>" class="btn btn-primary btn-xs" type="button"><?php echo tr('Tutti'); ?></button>
				<button id="deselectalltipi" class="btn btn-danger btn-xs" type="button"><i class="fa fa-times"></i></button>
			</div>

		</ul>

	</div>

<?php
// Tecnici
$checks = '';
$count = 0;
$total = 0;
$totale_tecnici = 0; // conteggia tecnici eliminati e non

$rs = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale, colore FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica
LEFT OUTER JOIN in_interventi_tecnici ON  in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica  INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id
WHERE an_anagrafiche.deleted_at IS NULL AND an_tipianagrafiche.descrizione='Tecnico' ".Modules::getAdditionalsQuery('Interventi').' GROUP BY an_anagrafiche.idanagrafica ORDER BY ragione_sociale ASC');
$total = count($rs);

$totale_tecnici += $total;

$allchecktecnici = '';
for ($i = 0; $i < count($rs); ++$i) {
    $attr = '';

    foreach ($_SESSION['dashboard']['idtecnici'] as $idx => $val) {
        if ($val == "'".$rs[$i]['id']."'") {
            $attr = 'checked="checked"';
            ++$count;
        }
    }

    $checks .= "<li><input type='checkbox' id='tech_".$rs[$i]['id']."' value=\"".$rs[$i]['id'].'" '.$attr." onclick=\"$.when ( session_set_array( 'dashboard,idtecnici', '".$rs[$i]['id']."' ) ).promise().then(function( ){ $('#calendar').fullCalendar('refetchEvents'); }); update_counter( 'idtecnici_count', $('#idtecnici_ul').find('input:checked').length );  \"> <label for='tech_".$rs[$i]['id']."'><span class='badge' style=\"color:#000; background:transparent; border: 1px solid ".$rs[$i]['colore'].';">'.$rs[$i]['ragione_sociale']."</span></label></li>\n";

    $allchecktecnici .= "session_set_array( 'dashboard,idtecnici', '".$rs[$i]['id']."', 0 ); ";
}

// TECNICI ELIMINATI CON ALMENO 1 INTERVENTO
$rs = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE deleted_at IS NOT NULL AND descrizione='Tecnico' GROUP BY an_anagrafiche.idanagrafica ORDER BY ragione_sociale ASC");
$total = count($rs);

$totale_tecnici += $total;

if ($total > 0) {
    $checks .= "<li><hr>Tecnici eliminati:</li>\n";
    for ($i = 0; $i < count($rs); ++$i) {
        $attr = '';

        foreach ($_SESSION['dashboard']['idtecnici'] as $idx => $val) {
            if ($val == "'".$rs[$i]['id']."'") {
                $attr = 'checked="checked"';
                ++$count;
            }
        }

        $checks .= "<li><input type='checkbox' id='tech_".$rs[$i]['id']."' value=\"".$rs[$i]['id'].'" '.$attr." onclick=\"$.when ( session_set_array( 'dashboard,idtecnici', '".$rs[$i]['id']."' ) ).promise().then(function( ){ $('#calendar').fullCalendar('refetchEvents');  }); update_counter( 'idtecnici_count', $('#idtecnici_ul').find('input:checked').length ); \"> <label for='tech_".$rs[$i]['id']."'> ".$rs[$i]['ragione_sociale']."</label></li>\n";

        $allchecktecnici .= "session_set_array( 'dashboard,idtecnici', '".$rs[$i]['id']."', 0 ); ";
    } // end for
} // end if

if ($count == $totale_tecnici) {
    $class = 'btn-success';
} elseif ($count == 0) {
    $class = 'btn-danger';
} else {
    $class = 'btn-warning';
}

if ($totale_tecnici == 0) {
    $class = 'btn-primary disabled';
}

?>
	<!-- TECNICI -->
	<div class="dropdown col-md-3">
		<a class="btn <?php echo $class; ?> btn-block" data-toggle="dropdown" href="javascript:;" id="idtecnici_count"><i class="fa fa-filter"></i> <?php echo tr('Tecnici'); ?> (<?php echo $count.'/'.$totale_tecnici; ?>) <i class="caret"></i></a>

		<ul class="dropdown-menu" role="menu" id="idtecnici_ul">
			<?php echo $checks; ?>
			<div class="btn-group pull-right">
				<button id="selectalltecnici" onclick="<?php echo $allchecktecnici; ?>" class="btn btn-primary btn-xs" type="button"><?php echo tr('Tutti'); ?></button>
				<button id="deselectalltecnici" class="btn btn-danger btn-xs" type="button"><i class="fa fa-times"></i></button>
			</div>
		</ul>
	</div>


<?php
// Zone
$allcheckzone = null;

$checks = '';
$count = 0;
$total = 0;

$rs = $dbo->fetchArray('(SELECT 0 AS ordine, \'0\' AS id, \'Nessuna zona\' AS descrizione) UNION (SELECT 1 AS ordine, id, descrizione FROM an_zone) ORDER BY ordine, descrizione ASC');
$total = count($rs);

for ($i = 0; $i < count($rs); ++$i) {
    $attr = '';

    foreach ($_SESSION['dashboard']['idzone'] as $idx => $val) {
        if ($val == "'".$rs[$i]['id']."'") {
            $attr = 'checked="checked"';
            ++$count;
        }
    }

    $checks .= "<li><input type='checkbox' id='idzone_".$rs[$i]['id']."' value=\"".$rs[$i]['id'].'" '.$attr." 	onclick=\"$.when ( session_set_array( 'dashboard,idzone', '".$rs[$i]['id']."' ) ).promise().then(function( ){ $('#calendar').fullCalendar('refetchEvents'); update_counter( 'idzone_count', $('#idzone_ul').find('input:checked').length ); }); \"> <label for='idzone_".$rs[$i]['id']."'> ".$rs[$i]['descrizione']."</label></li>\n";

    $allcheckzone = "session_set_array( 'dashboard,idzone', '".$rs[$i]['id']."', 0 ); ";
}

if ($count == $total) {
    $class = 'btn-success';
} elseif ($count == 0) {
    $class = 'btn-danger';
} else {
    $class = 'btn-warning';
}

if ($total == 0) {
    $class = 'btn-primary disabled';
}
?>
	<!-- ZONE -->
	<div class="dropdown col-md-3">
		<a class="btn <?php echo $class; ?> btn-block" data-toggle="dropdown" href="javascript:;" id="idzone_count"><i class="fa fa-filter"></i> <?php echo tr('Zone'); ?> (<?php echo $count.'/'.$total; ?>) <i class="caret"></i></a>

		<ul class="dropdown-menu" role="menu" id="idzone_ul">
			<?php echo $checks; ?>
			<div class="btn-group pull-right">
				<button id="selectallzone" onclick="<?php echo $allcheckzone; ?>" class="btn btn-primary btn-xs" type="button"><?php echo tr('Tutti'); ?></button>
				<button id="deselectallzone" class="btn btn-danger btn-xs" type="button"><i class="fa fa-times"></i></button>
			</div>
		</ul>
	</div>
</div>
<br>
<?php
$qp = 'SELECT MONTH(data_richiesta) AS mese, YEAR(data_richiesta) AS anno FROM (co_promemoria INNER JOIN co_contratti ON co_promemoria.idcontratto=co_contratti.id) INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) ) AND idintervento IS NULL

UNION SELECT MONTH(data_scadenza) AS mese, YEAR(data_scadenza) AS anno FROM (co_ordiniservizio INNER JOIN co_contratti ON co_ordiniservizio.idcontratto=co_contratti.id) INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) ) AND idintervento IS NULL

UNION SELECT MONTH(data_richiesta) AS mese, YEAR(data_richiesta) AS anno FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE (SELECT COUNT(*) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id) = 0 ORDER BY anno,mese';
$rsp = $dbo->fetchArray($qp);

if (!empty($rsp)) {
    echo '
<div class="row">
    <div class="col-md-10">';
}

echo '
<div id="calendar"></div>';

if (!empty($rsp)) {
    echo '
    </div>

    <div id="external-events" class="hidden-xs hidden-sm col-md-2">
        <h4>'.tr('Promemoria da pianificare').'</h4>';

    // Controllo pianificazioni mesi precedenti
    $qp_old = 'SELECT co_promemoria.id FROM co_promemoria INNER JOIN co_contratti ON co_promemoria.idcontratto=co_contratti.id WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) AND idintervento IS NULL AND DATE_ADD(co_promemoria.data_richiesta, INTERVAL 1 DAY) <= NOW()

    UNION SELECT co_ordiniservizio.id FROM co_ordiniservizio INNER JOIN co_contratti ON co_ordiniservizio.idcontratto=co_contratti.id WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) AND idintervento IS NULL AND DATE_ADD(co_ordiniservizio.data_scadenza, INTERVAL 1 DAY) <= NOW()

    UNION SELECT in_interventi.id FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE (SELECT COUNT(*) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id) = 0 AND DATE_ADD(in_interventi.data_richiesta, INTERVAL 1 DAY) <= NOW()';
    $rsp_old = $dbo->fetchNum($qp_old);

    if ($rsp_old > 0) {
        echo '<div class="alert alert-warning alert-dismissible" role="alert"><i class="fa fa-exclamation-triangle"></i><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> '.tr('Ci sono '.$rsp_old.' interventi scaduti da pianificare.').'</div>';
    }

    $mesi = months();

    // Creo un array con tutti i mesi che contengono interventi
    $mesi_interventi = [];
    for ($i = 0; $i < sizeof($rsp); ++$i) {
        $mese_n = $rsp[$i]['mese'].$rsp[$i]['anno'];
        $mese_t = $mesi[intval($rsp[$i]['mese'])].' '.$rsp[$i]['anno'];
        $mesi_interventi[$mese_n] = $mese_t;
    }

    // Aggiungo anche il mese corrente
    $mesi_interventi[date('m').date('Y')] = $mesi[intval(date('m'))].' '.date('Y');

    // Rimuovo i mesi doppi
    array_unique($mesi_interventi);

    // Ordino l'array per anno
    foreach ($mesi_interventi as $key => &$data) {
        ksort($data);
    }

    echo '<select class="superselect" id="select-intreventi-pianificare">';

    foreach ($mesi_interventi as $key => $mese_intervento) {
        echo '<option value="'.$key.'">'.$mese_intervento.'</option>';
    }

    echo '</select>';

    echo '<div id="interventi-pianificare"></div>';

    echo '
    </div>
</div>';
}

$vista = setting('Vista dashboard');
if ($vista == 'mese') {
    $def = 'month';
} elseif ($vista == 'giorno') {
    $def = 'agendaDay';
} else {
    $def = 'agendaWeek';
}
?>

<script type="text/javascript">
	
	function load_interventi_da_pianificare(mese){
		
		if (mese == undefined){
			// Seleziono il mese corrente per gli interventi da pianificare
			var date = new Date();
			var mese;
			date.setDate(date.getDate());

			//Note: January is 0, February is 1, and so on.
			mese = ('0' + (date.getMonth()+1)).slice(-2) + date.getFullYear();

			$('#select-intreventi-pianificare option[value='+mese+']').attr('selected','selected').trigger('change');
		}
		
		$('#interventi-pianificare').html('<center><br><br><i class=\'fa fa-refresh fa-spin fa-2x fa-fw\'></i></center>');
		$.get( '<?php echo $rootdir; ?>/modules/dashboard/actions.php', { op: 'load_intreventi', mese: mese }, function(data){
			
        })
		.done(function( data ) {
			$('#interventi-pianificare').html(data);
			$('#external-events .fc-event').each(function() {
                $(this).draggable({
                    zIndex: 999,
                    revert: true,
                    revertDuration: 0
                });
            });
			
		});
		
	}
    $('#select-intreventi-pianificare').change(function(){
        var mese = $(this).val();
        load_interventi_da_pianificare(mese);

    });

	$(document).ready(function() {
       
		
		load_interventi_da_pianificare();

        // Comandi seleziona tutti
        $('#selectallstati').click(function(event) {

            $(this).parent().parent().find('li input[type=checkbox]').each(function(i) { // loop through each checkbox
             	this.checked = true;
				$.when (session_set_array( 'dashboard,idstatiintervento', this.value, 0 )).promise().then(function() {
					$('#calendar').fullCalendar('refetchEvents');
				});

				i++;
				update_counter( 'idstati_count',i);

            });

        });

        $('#selectalltipi').click(function(event) {

            $(this).parent().parent().find('li input[type=checkbox]').each(function(i) { // loop through each checkbox
				this.checked = true;
				$.when (session_set_array( 'dashboard,idtipiintervento', this.value, 0 )).promise().then(function() {
					$('#calendar').fullCalendar('refetchEvents');
				});
				i++;
				update_counter( 'idtipi_count', i);

            });

        });

        $('#selectalltecnici').click(function(event) {

            $(this).parent().parent().find('li input[type=checkbox]').each(function(i) { // loop through each checkbox
				this.checked = true;
				$.when (session_set_array( 'dashboard,idtecnici', this.value, 0 )).promise().then(function() {
					$('#calendar').fullCalendar('refetchEvents');
				});
				i++;
				update_counter( 'idtecnici_count', i);
            });

        });

        $('#selectallzone').click(function(event) {

            $(this).parent().parent().find('li input[type=checkbox]').each(function(i) { // loop through each checkbox
				this.checked = true;
				 $.when (session_set_array( 'dashboard,idzone', this.value, 0 )).promise().then(function() {
						$('#calendar').fullCalendar('refetchEvents');
				});

				i++
				update_counter( 'idzone_count', i);

            });

        });

        // Comandi deseleziona tutti
        $('#deselectallstati').click(function(event) {

			$(this).parent().parent().find('li input[type=checkbox]').each(function() { // loop through each checkbox
				this.checked = false;
				 $.when (session_set_array( 'dashboard,idstatiintervento', this.value, 1 )).promise().then(function() {
						$('#calendar').fullCalendar('refetchEvents');
				});

				update_counter( 'idstati_count', 0);

            });

        });

        $('#deselectalltipi').click(function(event) {

			$(this).parent().parent().find('li input[type=checkbox]').each(function() { // loop through each checkbox
				this.checked = false;
				 $.when (session_set_array( 'dashboard,idtipiintervento', this.value, 1 )).promise().then(function() {
						$('#calendar').fullCalendar('refetchEvents');
				});


				update_counter( 'idtipi_count', 0);

            });

        });

        $('#deselectalltecnici').click(function(event) {

			$(this).parent().parent().find('li input[type=checkbox]').each(function() { // loop through each checkbox
				this.checked = false;
				 $.when (session_set_array( 'dashboard,idtecnici', this.value, 1 )).promise().then(function() {
						$('#calendar').fullCalendar('refetchEvents');
				});

				update_counter( 'idtecnici_count', 0);

            });

        });

        $('#deselectallzone').click(function(event) {

			$(this).parent().parent().find('li input[type=checkbox]').each(function() { // loop through each checkbox
				this.checked = false;
				$.when (session_set_array( 'dashboard,idzone', this.value, 1 )).promise().then(function() {
						$('#calendar').fullCalendar('refetchEvents');
				});

				update_counter( 'idzone_count', 0);

            });

        });

        // Creazione del calendario
		create_calendar();

        // Data di default
        $('.fc-prev-button, .fc-next-button, .fc-today-button').click(function(){
            var date_start = $('#calendar').fullCalendar('getView').start.format('YYYY-MM-DD');
            date_start = moment(date_start);

            if('<?php echo $def; ?>'=='month'){
                if(date_start.date()>1){
                    date_start = moment(date_start).add(1, 'M').startOf('month');
                }
            }

            date_start = date_start.format('YYYY-MM-DD');
            setCookie('calendar_date_start', date_start, 365);
        });

        calendar_date_start = getCookie('calendar_date_start');
		if (calendar_date_start!='')
			$('#calendar').fullCalendar( 'gotoDate', calendar_date_start );

	});

	function create_calendar(){
        $('#external-events .fc-event').each(function() {

			// store data so the calendar knows to render an event upon drop
			$(this).data('event', {
				title: $.trim($(this).text()), // use the element's text as the event title
				stick: false // maintain when user navigates (see docs on the renderEvent method)
			});

			// make the event draggable using jQuery UI
			$(this).draggable({
				zIndex: 999,
				revert: true,     // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			});

		});

		var calendar = $('#calendar').fullCalendar({
            locale: globals.locale,
<?php
$domenica = setting('Visualizzare la domenica sul calendario');
if (empty($domenica)) {
    echo '
            hiddenDays: [ 0 ],';
}
?>
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			timeFormat: 'H:mm',
            slotLabelFormat: "H:mm",
			slotDuration: '00:15:00',
            defaultView: '<?php echo $def; ?>',
<?php

echo "
            minTime: '".setting('Inizio orario lavorativo')."',
            maxTime: '".((setting('Fine orario lavorativo') == '00:00') ?: '23:59:59')."',
";

?>
            lazyFetching: true,
			selectHelper: true,
			eventLimit: false, // allow "more" link when too many events
			allDaySlot: false,
            loading: function(isLoading, view) {
                if(isLoading) {
 					$('#tiny-loader').fadeIn();
                } else {
                    $('#tiny-loader').hide();
                }
            },
<?php
if (Modules::getPermission('Interventi') == 'rw') {
    ?>
            droppable: true,
            drop: function(date, jsEvent, ui, resourceId) {
                data = moment(date).format("YYYY-MM-DD");
				ora_dal = moment(date).format("HH:mm");
                ora_al = moment(date).add(1, 'hours').format("HH:mm");

                ref = $(this).data('ref');
                if (ref == 'ordine') {
                    name = 'idordineservizio';
                } else if (ref == 'promemoria') {
                    name = 'idcontratto_riga';
                } else {
                    name = 'id_intervento';
                }

                launch_modal('<?php echo tr('Pianifica intervento'); ?>', globals.rootdir + '/add.php?id_module=<?php echo Modules::get('Interventi')['id']; ?>&data='+data+'&orario_inizio='+ora_dal+'&orario_fine='+ora_al+'&ref=dashboard&idcontratto=' + $(this).data('idcontratto') + '&' + name + '=' + $(this).data('id'), 1);

                $(this).remove();

                $('#bs-popup').on('hidden.bs.modal', function () {
                    $('#calendar').fullCalendar('refetchEvents');
                });
            },

            selectable: true,
			select: function(start, end, allDay) {
				data = moment(start).format("YYYY-MM-DD");
				ora_dal = moment(start).format("HH:mm");
				ora_al = moment(end).format("HH:mm");

                launch_modal('<?php echo tr('Aggiungi intervento'); ?>', globals.rootdir + '/add.php?id_module=<?php echo Modules::get('Interventi')['id']; ?>&ref=dashboard&data='+data+'&orario_inizio='+ora_dal+'&orario_fine='+ora_al, 1 );

				$('#calendar').fullCalendar('unselect');
			},

            editable: true,
            eventDrop: function(event,dayDelta,minuteDelta,revertFunc) {
				$.get(globals.rootdir + "/modules/dashboard/actions.php?op=update_intervento&id="+event.id+"&idintervento="+event.idintervento+"&timeStart="+moment(event.start).format("YYYY-MM-DD HH:mm")+"&timeEnd="+moment(event.end).format("YYYY-MM-DD HH:mm"), function(data,response){
					if( response=="success" ){
						data = $.trim(data);
						if( data!="ok" ){
							alert(data);
							$('#calendar').fullCalendar('refetchEvents');
							revertFunc();
						}
						else{
							return false;
						}
					}
				});
			},
            eventResize: function(event,dayDelta,minuteDelta,revertFunc) {
				$.get(globals.rootdir + "/modules/dashboard/actions.php?op=update_intervento&id="+event.id+"&idintervento="+event.idintervento+"&timeStart="+moment(event.start).format("YYYY-MM-DD HH:mm")+"&timeEnd="+moment(event.end).format("YYYY-MM-DD HH:mm"), function(data,response){
					if( response=="success" ){
						data = $.trim(data);
						if(data != "ok"){
							alert(data);
							$('#calendar').fullCalendar('refetchEvents');
							revertFunc();
						}
						else{
							return false;
						}
					}
				});
			},
<?php
}
?>
			eventAfterRender: function(event, element) {
				element.find('.fc-title').html(event.title);
                element.data('idintervento', event.idintervento);
<?php

if (setting('Utilizzare i tooltip sul calendario') == '1') {
    ?>
				element.mouseover( function(){
				    if( !element.hasClass('tooltipstered') ){
				        $(this).data('idintervento', event.idintervento );
				        
				        $.get(globals.rootdir + "/modules/dashboard/actions.php?op=get_more_info&id="+$(this).data('idintervento'), function(data,response){
							if( response=="success" ){
								data = $.trim(data);
								if( data!="ok" ){
									element.tooltipster({
										content: data,
										animation: 'grow',
										contentAsHTML: true,
										hideOnClick: true,
										onlyOne: true,
										speed: 200,
										delay: 100,
										maxWidth: 400,
										theme: 'tooltipster-shadow',
										touchDevices: true,
										trigger: 'hover',
										position: 'left'
									});
								}
								else{
									return false;
								}

				                $('#calendar').fullCalendar('option', 'contentHeight', 'auto');
				            }
				        });
					}
				});
<?php
}
?>
			},
            events: {
				url: globals.rootdir + "/modules/dashboard/actions.php?op=get_current_month",
                type: 'GET',
				error: function() {
					alert('<?php echo tr('Errore durante la creazione degli eventi'); ?>');
				}
			}
		});
	}
</script>
