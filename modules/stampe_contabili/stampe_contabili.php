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
$id_print = PrintTemplate::where('name', $nome_stampa)->first()->id;
$id_module = Module::where('name', 'Stampe contabili')->first()->id;

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
    <div class="row">
        <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
            <i class="fa fa-exclamation-circle fa-2x"></i>
        </div>
        <div class="col-md-11">
            '.tr('Non è possibile creare la stampa definitiva nel periodo selezionato, è necessario prima impostare un trimestre o un singolo mese!').'
        </div>
    </div>
</div>

<div class="alert alert-warning hidden" id="is_definitiva">
    <div class="row">
        <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
            <i class="fa fa-warning fa-2x"></i>
        </div>
        <div class="col-md-11">
            '.tr('È già presente la stampa definitiva per il periodo selezionato!').'
        </div>
    </div>
</div>';

if ($nome_stampa == 'Libro giornale') {
    echo '
<div class="alert alert-danger hidden" id="sbilanci_libro_giornale">
    <div class="row">
        <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
            <i class="fa fa-exclamation-triangle fa-2x"></i>
        </div>
        <div class="col-md-11">
            <strong>'.tr('Attenzione: Sono presenti sbilanci nel libro giornale!').'</strong><br>
            <span id="dettagli_sbilanci"></span><br>
            <small>'.tr('Prima di stampare definitivamente il libro giornale è necessario risolvere tutti gli sbilanciamenti nei movimenti di prima nota.').'</small>
        </div>
    </div>
</div>';
}

echo '
<form action="" method="post" id="form" class="mb-3">
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa fa-calendar mr-2"></i>'.tr('Periodo di riferimento').'
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
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
            <hr>
            <div class="row">';
if ($nome_stampa != 'Liquidazione IVA' && $nome_stampa != 'Libro giornale') {
    echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_sezionale", "required": "1", "values": "query=SELECT `zz_segments`.`id`, `zz_segments_lang`.`title` AS descrizione FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = \"'.(($dir == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto').'\") AND `is_fiscale` = 1 UNION SELECT  -1 AS id, \"Tutti i sezionali\" AS descrizione" ]}
                </div>';
}
echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['stampe_contabili']['format'].'" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['stampe_contabili']['orientation'].'" ]}
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Definitiva').'", "disabled": "1", "name": "definitiva", "help": "'.tr('Per abilitare il pulsante è necessario impostare nei campi Data inizio e Data fine uno dei 4 trimestri o un singolo mese e non deve essere già stata creata la stampa definitiva del periodo selezionato').'" ]}
                </div>

                <div class="col-md-4 offset-md-4 text-right">
                    <button type="button" class="btn btn-primary btn-lg" onclick="if($(\'#form\').parsley().validate()) { return avvia_stampa(); }">
                        <i class="fa fa-print mr-2"></i> '.tr('Stampa').'
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>';

$where_conditions = [
    'date_end BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']),
    'id_print='.prepare($id_print),
];

if (!empty($dir)) {
    $where_conditions[] = 'dir='.prepare($dir);
} else {
    $where_conditions[] = '(dir IS NULL OR dir = "")';
}

$where_clause = implode(' AND ', $where_conditions);
$elementi = $dbo->fetchArray('SELECT * FROM co_stampecontabili WHERE '.$where_clause);
echo '
<div class="card card-info card-outline mt-3 collapsed-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa fa-history mr-2"></i>'.tr('Stampe definitive _NOME_ _DIR_ dal _START_ al _END_', [
    '_NOME_' => $nome_stampa,
    '_DIR_' => ($dir ? ($dir == 'entrata' ? 'vendite' : 'acquisti') : ''),
    '_START_' => dateFormat($_SESSION['period_start']),
    '_END_' => dateFormat($_SESSION['period_end']),
]).'
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">';

if (!empty($elementi)) {
    echo '
                <thead>
                    <tr>
                        <th>'.tr('Periodo').'</th>
                        <th>'.tr('Sezionale').'</th>
                        <th>'.tr('Pagine').'</th>
                        <th class="text-center">'.tr('Azioni').'</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($elementi as $elemento) {
        $sezionale_stampa = $dbo->fetchOne('SELECT `zz_segments_lang`.`title` FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_segments`.`id` = '.$elemento['id_sezionale'])['title'];

        $file = $dbo->selectOne('zz_files', ['*'], [
            'id_module' => $id_module,
            'id_record' => $elemento['id'],
        ]);

        // Verifica se esiste un movimento di prima nota collegato
        $movimento_button = '';
        if (!empty($elemento['idmastrino']) && $nome_stampa === 'Liquidazione IVA') {
            $movimento_button = '
                            <a class="btn btn-sm btn-primary" href="'.base_path().'/controller.php?id_module='.Module::where('name', 'Prima nota')->first()->id.'&id_record='.$elemento['idmastrino'].'" target="_blank">
                                <i class="fa fa-book"></i> '.tr('Prima nota').'
                            </a>';
        }

        echo '
                    <tr>
                        <td>'.dateFormat($elemento['date_start']).' - '.dateFormat($elemento['date_end']).'</td>
                        <td>'.($sezionale_stampa ?: tr('Tutti i sezionali')).'</td>
                        <td>'.$elemento['first_page'].' - '.$elemento['last_page'].'</td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-info" href="'.base_path().'/actions.php?id_module='.$id_module.'&op=download-allegato&id='.$file['id'].'&filename='.$file['filename'].'" target="_blank">
                                <i class="fa fa-download"></i> '.tr('Scarica').'
                            </a>
                            '.$movimento_button.'
                        </td>
                    </tr>';
    }

    echo '
                </tbody>';
} else {
    echo '
                <tr>
                    <td class="text-center">'.tr('Nessuna stampa presente').'</td>
                </tr>';
}

echo '
            </table>
        </div>
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

	$("#id_sezionale").on("change", function(){
		eseguiControlli();
	});

	function eseguiControlli() {
		let date_start = $("#date_start").data("DateTimePicker").date().format("YYYY-MM-DD");
		let date_end = $("#date_end").data("DateTimePicker").date().format("YYYY-MM-DD");
		controllaDate(date_start, date_end);

		// Controllo sbilanci per il libro giornale
		if ("'.$nome_stampa.'" === "Libro giornale") {
			controllaSbilanciLibroGiornale(date_start, date_end);
		}
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
		$(document).load(globals.rootdir + "/ajax_complete.php?module=stampe_contabili&op=controlla_stampa&dir='.$dir.'&id_sezionale="+$("#id_sezionale").val()+"&id_print='.$id_print.'&date_start=" + date_start + "&date_end=" + date_end, function(response) {
			let stampa_definitiva = response;

			if (stampa_definitiva==0) {
				$("#is_definitiva").addClass("hidden");

				// Per il libro giornale, controlla anche gli sbilanci prima di abilitare la stampa definitiva
				if ("'.$nome_stampa.'" === "Libro giornale") {
					// Verifica se l\'avviso sbilanci è nascosto (significa che non ci sono sbilanci)
					if ($("#sbilanci_libro_giornale").hasClass("hidden")) {
						input("definitiva").enable();
					} else {
						input("definitiva").disable();
						$("#definitiva").prop("checked", false);
					}
				} else {
					input("definitiva").enable();
				}
			} else {
				$("#is_definitiva").removeClass("hidden");
				input("definitiva").disable();
				$("#definitiva").prop("checked", false);
			}
		});
	}

	$(document).ready(init);

	// Controllo sbilanci nel libro giornale
	function controllaSbilanciLibroGiornale(date_start, date_end) {
		$.ajax({
			url: globals.rootdir + "/ajax_complete.php",
			type: "GET",
			data: {
				module: "stampe_contabili",
				op: "controlla_sbilanci_libro_giornale",
				date_start: date_start,
				date_end: date_end
			},
			success: function(response) {
				try {
					let risultato = JSON.parse(response);

					if (risultato.ha_sbilanci) {
						let dettagli = "Il libro giornale presenta uno sbilancio totale di " + parseFloat(risultato.totale_sbilancio).toFixed(2) + " " + globals.currency;
						$("#dettagli_sbilanci").html(dettagli);
						$("#sbilanci_libro_giornale").removeClass("hidden");

						// Disabilita la stampa definitiva se ci sono sbilanci
						input("definitiva").disable();
						$("#definitiva").prop("checked", false);
					} else {
						$("#sbilanci_libro_giornale").addClass("hidden");

						// Se non ci sono sbilanci, riabilita la stampa definitiva solo se non è già presente una stampa definitiva
						// Richiama il controllo della stampa per verificare se può essere abilitata
						controllaStampa(date_start, date_end);
					}
				} catch (e) {
					console.error("Errore nel parsing della risposta per controllo sbilanci:", e);
				}
			},
			error: function() {
				console.error("Errore nella richiesta di controllo sbilanci");
			}
		});
	}

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
					result = JSON.parse(result);
					window.open("'.$link.'&dir='.$dir.'&id_sezionale="+$("#id_sezionale").val()+"&date_start="+$("#date_start").val()+"&date_end="+$("#date_end").val()+"&first_page="+result.first_page+"");
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
		}
		eseguiControlli();
	});
</script>';
