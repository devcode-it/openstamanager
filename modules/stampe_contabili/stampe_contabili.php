<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Models\Module;
use Models\PrintTemplate;

$id_record = filter('id_record');
$dir = filter('dir');
$nome_stampa = filter('nome_stampa');
$id_print = (new PrintTemplate ())->getByField('name', prepare($nome_stampa));
$id_module = (new Module())->getByField('name', 'Stampe contabili');

$year = (new Carbon($_SESSION['period_end']))->format('Y');
$periodi[] = [
    'id' => 'manuale',
    'text' => tr('Manuale'),
];

$month_start = 1;
$month_end = 3;

if (setting('Liquidazione iva') == 'Trimestrale') {
    for ($i = 1; $i <= 4; ++$i) {
        $periodi[] = [
            'id' => ''.$i.'_trimestre',
            'text' => tr('_NUM_° Trimestre _YEAR_', ['_NUM_' => $i, '_YEAR_' => $year]),
            'date_start' => $year.','.$month_start.',01',
            'date_end' => $year.','.$month_end.','.(new Carbon($year.'-'.$month_end.'-01'))->endOfMonth()->format('d'),
        ];
        $month_start += 3;
        $month_end += 3;
    }
}

if (setting('Liquidazione iva') == 'Mensile') {
    for ($i = 1; $i <= 12; ++$i) {
        $month = (new Carbon($year.'-'.$i.'-01'))->locale('it')->getTranslatedMonthName('IT MMMM');
        $periodi[] = [
            'id' => ''.$i.'_mese',
            'text' => tr('_MONTH_ _YEAR_', ['_MONTH_' => $month, '_YEAR_' => $year]),
            'date_start' => $year.','.$i.',01',
            'date_end' => $year.','.$i.','.(new Carbon($year.'-'.$i.'-01'))->endOfMonth()->format('d'),
        ];
    }
}

// Trovo id_print della stampa
$link = Prints::getHref($nome_stampa, $id_record);

echo '
<div class="alert alert-info hidden" id="period">
    <i class="fa fa-exclamation-circle"></i> '.tr('Non è possibile creare la stampa definitiva nel periodo selezionato, è necessario prima impostare un trimestre o un singolo mese!').'
</div>

<div class="alert alert-warning hidden" id="is_definitiva">
    <i class="fa fa-warning"></i> '.tr('È già presente la stampa definitiva per il periodo selezionato!').'
</div>

<form action="" method="post" id="form" >
	<div class="row">';
echo '
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Periodo').'", "name": "periodo", "required": "1", "values": '.json_encode($periodi).', "value": "manuale" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "required": "1", "name": "date_start", "value": "'.$_SESSION['period_start'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data fine').'", "required": "1", "name": "date_end", "value": "'.$_SESSION['period_end'].'" ]}
		</div>
	</div>';

echo '
	<div class="row">';
if ($nome_stampa != 'Liquidazione IVA') {
    echo '
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_sezionale", "required": "1", "values": "query=SELECT `zz_segments`.`id`, `zz_segments_lang`.`name` AS descrizione FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(\App::getLang()).') WHERE `id_module` = (SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = \''.(($dir == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto').'\') AND `is_fiscale` = 1 UNION SELECT  -1 AS id, \'Tutti i sezionali\' AS descrizione" ]}
		</div>';
}
echo '
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['stampe_contabili']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['stampe_contabili']['orientation'].'" ]}
		</div>';

if ($nome_stampa != 'Liquidazione IVA') {
    echo '
		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Definitiva').'", "disabled": "1", "name": "definitiva", "help": "'.tr('Per abilitare il pulsante è necessario impostare nei campi Data inizio e Data fine uno dei 4 trimestri o un singolo mese e non deve essere già stata creata la stampa definitiva del periodo selezionato').'" ]}
		</div>';
}

echo '
		<div class="col-md-4 pull-right">
			<p style="line-height:14px;">&nbsp;</p>
			<button type="button" class="btn btn-primary btn-block" onclick="if($(\'#form\').parsley().validate()) { return avvia_stampa(); }">
				<i class="fa fa-print"></i> '.tr('Stampa').'
			</button>
		</div>
	</div>
</form>
<br>';

if ($nome_stampa != 'Liquidazione IVA') {
    $elementi = $dbo->fetchArray('SELECT * FROM co_stampecontabili WHERE date_end BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']).' AND id_print='.prepare($id_print).' AND dir='.prepare($dir));
    echo '
	<div class="box box-primary collapsable collapsed-box">
		<div class="box-header with-border">
			<h3 class="box-title"><i class="fa fa-print"></i> '.tr('Stampe definitive registro iva _DIR_ dal _START_ al _END_', [
                '_DIR_' => $dir == 'entrata' ? 'vendite' : 'acquisti',
                '_START_' => dateFormat($_SESSION['period_start']),
                '_END_' => dateFormat($_SESSION['period_end']),
            ]).'</h3>
			<div class="box-tools pull-right">
				<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
			</div>
		</div>
		<div class="box-body">
			<ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('Stampa definitiva dal _START_ al _END_ (_FIRST_-_LAST_)', [
                '_START_' => dateFormat($elemento['date_start']),
                '_END_' => dateFormat($elemento['date_end']),
                '_FIRST_' => $elemento['first_page'],
                '_LAST_' => $elemento['last_page'],
            ]);

        $file = $dbo->selectOne('zz_files', '*', ['id_module' => $id_module, 'id_record' => $elemento['id']]);

        echo '
				<li>
					<a class="btn btn-xs btn-primary" href="'.base_path().'/actions.php?id_module='.$id_module.'&op=download-allegato&id='.$file['id'].'&filename='.$file['filename'].'" target="_blank"><i class="fa fa-download"></i>
					</a>
					'.$descrizione.'
				</li>';
    }

    if (empty($elementi)) {
        echo '<p class="text-center">'.tr('Nessuna stampa presente').'</p>';
    }

    echo '
			</ul>
		</div>
	</div>';

    echo '
	<script>
		$("#modals > div").on("shown.bs.modal", function () {
			eseguiControlli();
		});

		$("#date_start").on("blur", function(){
			eseguiControlli();
		});

		$("#date_end").on("blur", function(){
			eseguiControlli();
		});

		function eseguiControlli() {
			let date_start = $("#date_start").data("DateTimePicker").date().format("YYYY-MM-DD");
			let date_end = $("#date_end").data("DateTimePicker").date().format("YYYY-MM-DD");
			controllaDate(date_start, date_end);
		}

		// Controllo se le date inserite corrispondono ad uno dei 4 trimestri o ad un mese
		function controllaDate(date_start, date_end) {
			let intervallo_corretto = 0;
			let date = new Date(date_start);
			let year = date.getFullYear();
			let m_start = 0;
			let m_end = 3;
			
			for (i=0; i<=3; i++) {
				let start = new Date(year, m_start, 1);
				let end = new Date(year, m_end, 0);

				int_start = start.getFullYear() +  "-" + ("0" + (start.getMonth() + 1)).slice(-2) + "-" + ("0" + start.getDate()).slice(-2);
				int_end = end.getFullYear() +  "-" + ("0" + (end.getMonth() + 1)).slice(-2) + "-" + ("0" + end.getDate()).slice(-2);

				if (date_start == int_start && date_end == int_end) {
					intervallo_corretto = 1;	
				}
				m_start += 3;
				m_end += 3;
			}

			m_start = 0;
			m_end = 1;
			for (i=0; i<=11; i++) {
				let start = new Date(year, m_start, 1);
				let end = new Date(year, m_end, 0);

				int_start = start.getFullYear() +  "-" + ("0" + (start.getMonth() + 1)).slice(-2) + "-" + ("0" + start.getDate()).slice(-2);
				int_end = end.getFullYear() +  "-" + ("0" + (end.getMonth() + 1)).slice(-2) + "-" + ("0" + end.getDate()).slice(-2);

				if (date_start == int_start && date_end == int_end) {
					intervallo_corretto = 1;	
				}
				m_start += 1;
				m_end += 1;
			}
			$("#is_definitiva").addClass("hidden");

			if (intervallo_corretto) {
				$("#period").addClass("hidden");
				controllaStampa(date_start, date_end);
			} else {
				$("#period").removeClass("hidden");
				input("definitiva").disable();
				$("#definitiva").prop("checked", false);
			}
		}

		// Controllo se è già stata creata una stampa definitiva nel periodo selezionato
		function controllaStampa(date_start, date_end) {
			$(document).load(globals.rootdir + "/ajax_complete.php?module=stampe_contabili&op=controlla_stampa&dir='.$dir.'&id_print='.$id_print.'&date_start=" + date_start + "&date_end=" + date_end, function(response) {
				let stampa_definitiva = response;

				if (stampa_definitiva==0) {
					$("#is_definitiva").addClass("hidden");
					input("definitiva").enable();
				} else {
					$("#is_definitiva").removeClass("hidden");
					input("definitiva").disable();
					$("#definitiva").prop("checked", false);
				}
			});
		}
	</script>';
}

echo '
<script>
	$(document).ready(init);

	function avvia_stampa (){
		if ($("#definitiva").is(":checked")) {
			let date_start = $("#date_start").data("DateTimePicker").date().format("YYYY-MM-DD");
			let date_end = $("#date_end").data("DateTimePicker").date().format("YYYY-MM-DD");

			$.ajax({
				url: globals.rootdir + "/actions.php",
				type: "POST",
				data: {
					id_module: globals.id_module,
					op: "crea_definitiva",
					date_start: date_start,
					date_end: date_end,
					id_print: '.$id_print.',
					id_sezionale: $("#id_sezionale").val(),
					dir: "'.$dir.'",
				},
				success: function(result) {
					window.open("'.$link.'&dir='.$dir.'&id_sezionale="+$("#id_sezionale").val()+"&date_start="+$("#date_start").val()+"&date_end="+$("#date_end").val()+"");
					$("#modals > div").modal("hide");
				}
			});
		} else {
			window.open("'.$link.'&dir='.$dir.'&notdefinitiva=1&id_sezionale="+$("#id_sezionale").val()+"&date_start="+$("#date_start").val()+"&date_end="+$("#date_end").val()+"");
			$("#modals > div").modal("hide");
		}
		
	}

	$("#format").change(function() {
		session_set("stampe_contabili,format", $(this).val(), 0, 0);
	});

	$("#orientation").change(function() {
		session_set("stampe_contabili,orientation", $(this).val(), 0, 0);
	});

	$("#periodo").change(function() {
		if ($(this).val()=="manuale") {
			input("date_start").enable();
			input("date_end").enable();
		} else {
			$("#date_start").data("DateTimePicker").date(new Date(input("periodo").getData().date_start));
			$("#date_end").data("DateTimePicker").date(new Date(input("periodo").getData().date_end));
			input("date_start").disable();
			input("date_end").disable();
		}';
if ($nome_stampa != 'Liquidazione IVA') {
    echo 'eseguiControlli();';
}
echo '
	});
</script>';
