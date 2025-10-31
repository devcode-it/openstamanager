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

// Recupero informazioni sulla riga del documento
$documento = $dbo->fetchOne('SELECT * FROM `co_documenti` WHERE id='.prepare($record['iddocumento']));

// Recupero righe di ammortamento esistenti
$righe_ammortamento = $dbo->fetchArray('SELECT * FROM `co_righe_ammortamenti` WHERE id_riga='.prepare($id_record).' ORDER BY anno ASC');
$numero_righe = count($righe_ammortamento);

// Calcola anno di inizio (anno del documento)
$anno_inizio = date('Y', strtotime((string) $documento['data_competenza']));

$readonly = $anno_inizio == date('Y') ? 0 : 1;

// Recupero conti patrimoniali
$conti_patrimoniali = $dbo->fetchArray('SELECT id, descrizione FROM co_pianodeiconti2 WHERE idpianodeiconti1=(SELECT id FROM co_pianodeiconti1 WHERE descrizione="Patrimoniale") ORDER BY descrizione ASC');

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_ammortamento">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Informazioni'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Cespite'); ?>", "name": "descrizione_riga", "value": "$descrizione$", "disabled": 1 ]}
				</div>
				
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Importo'); ?>", "name": "importo_riga", "value": "<?php echo numberFormat($record['subtotale']); ?>", "disabled": 1, "class": "text-right", "icon-after": "<?php echo currency(); ?>" ]}
				</div>
				
				<div class="col-md-4">
					<?php echo Modules::link('Fatture di acquisto', $documento['id'], null, null, 'class="pull-right"'); ?>
					{[ "type": "text", "label": "<?php echo tr('Fattura di origine'); ?>", "name": "fattura_collegata", "value": "<?php echo $documento['numero']; ?> del <?php echo dateFormat($documento['data_competenza']); ?>", "disabled": 1 ]}
				</div>
			</div>
		</div>
	</div>

	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Ammortamenti'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Conto per ammortamento'); ?>", "name": "id_conto", "required": 1, "values": "query=SELECT co_pianodeiconti3.id, CONCAT(co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero, ' - ', co_pianodeiconti3.descrizione) AS descrizione FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE idpianodeiconti2=<?php echo prepare(setting('Conto predefinito per gli ammortamenti')); ?>", "value": "<?php echo $righe_ammortamento[0]['id_conto']; ?>", "disabled": "<?php echo $readonly > 0 ? 1 : 0; ?>" ]}
				</div>

				<div class="col-md-6" style="margin-top: 25px">
					<button type="button" class="btn btn-info pull-right <?php echo $readonly > 0 ? 'disabled' : ''; ?>" id="add-riga">
						<i class="fa fa-plus"></i> <?php echo tr('Aggiungi riga'); ?>
					</button>
				</div>
			</div>

			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th class="text-center"><?php echo tr('Anno'); ?></th>
							<th width="30%"><?php echo tr('Percentuale'); ?></th>
							<th width="30%"><?php echo tr('Importo'); ?></th>
							<th width="15%" class="text-center"><?php echo tr('Movimento'); ?></th>
						</tr>
					</thead>
					<tbody id="righe-ammortamento">
						<?php
                        // Righe esistenti
                        foreach ($righe_ammortamento as $index => $riga) {
                            echo '
						<tr>
							<td>
								{[ "type": "text", "name": "anno['.$riga['id'].']", "value": "'.$riga['anno'].'", "disabled": "1", "class": "text-center" ]}
							</td>
							<td>
								{[ "type": "number", "name": "percentuale['.$riga['id'].']", "value": "'.$riga['percentuale'].'", "icon-after": "%", "disabled": "'.$readonly.'", "class": "percentuale" ]}
							</td>
							<td>
								{[ "type": "number", "name": "importo['.$riga['id'].']", "value": "'.$riga['importo'].'", "icon-after": "'.currency().'", "disabled": "1", "class": "importo" ]}
							</td>
							<td class="text-center">
								'.($riga['id_mastrino'] ? Modules::link('Prima nota', $riga['id_mastrino'], 'Visualizza prima nota', null, 'class="btn btn-sm btn-primary"') : '').'
							</td>
						</tr>';
                        }
?>
					</tbody>
				</table>
			</div>

			<div class="alert alert-warning text-center hide" id="alert-percentuale">
				<i class="fa fa-warning"></i> <?php echo tr('Attenzione! La somma delle percentuali deve essere esattamente 100%'); ?>
				<br>
				<?php echo tr('Totale attuale'); ?>: <span id="totale-percentuale">0</span>%
			</div>

			<div class="alert alert-info text-center">
				<i class="fa fa-info-circle"></i> <?php echo tr('Ammortamento modificabile fino al 31/12/_ANNO_', [
				    '_ANNO_' => $anno_inizio,
				]); ?>
			</div>

			<div class="row">
				<div class="col-md-12 text-center">
					<button type="button" class="btn btn-lg btn-primary <?php echo $readonly ? 'disabled' : ''; ?>" id="btn-salva" onclick="applicaAmmortamento()">
						<?php echo tr('Applica ammortamento'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</form>

<!-- Template per nuova riga -->
<table class="hide">
	<tbody id="template-riga">
		<tr>
			<td>
				{[ "type": "text", "name": "anno[-id-]", "value": "-anno-", "disabled": 1, "class": "text-center" ]}
			</td>
			<td>
				{[ "type": "number", "name": "percentuale[-id-]", "value": "0", "icon-after": "%", "class": "percentuale" ]}
			</td>
			<td>
				{[ "type": "number", "name": "importo[-id-]", "value": "0", "icon-after": "<?php echo currency(); ?>", "disabled": "1", "class": "importo" ]}
			</td>
			<td class="text-center"></td>
		</tr>
	</tbody>
</table>

<script>
$(document).ready(function() {
	$("#save-buttons").hide();

	// Variabili globali
	var importo_totale = <?php echo $record['subtotale']; ?>;
	var indice_riga = 0;
	var anno_documento = <?php echo $anno_inizio; ?>;
	var numero_righe_create = <?php echo $numero_righe; ?>;
	
	// Aggiunta nuova riga
	$("#add-riga").click(function() {
		var template = $("#template-riga").html();
		var anno_da_usare;
		
		// Se è la prima riga, usa l'anno del documento
		if ($("#righe-ammortamento tr").length === 0) {
			anno_da_usare = anno_documento;
		} else {
			// Altrimenti prendi l'anno dell'ultima riga e aggiungi 1
			var ultima_riga = $("#righe-ammortamento tr:last-child");
			var ultimo_anno = parseInt(ultima_riga.find("input[name^='anno']").val());
			anno_da_usare = ultimo_anno + 1;
		}
		
		var html = template.replace(/-id-/g, indice_riga)
						  .replace(/-anno-/g, anno_da_usare);
		
		$("#righe-ammortamento").append(html);
		indice_riga++;
		
		// Aggiorna gli importi
		aggiornaImporti();
	});
	
	// Aggiornamento importi in base alla percentuale
	$(document).on("input", ".percentuale", function() {
		aggiornaImporti();
	});
	
	// Funzione per aggiornare gli importi
	function aggiornaImporti() {
		var totale_percentuale = 0;

		$(".percentuale").each(function() {
			var percentuale = parseFloat($(this).val()) || 0;
			var importo = (percentuale / 100) * importo_totale;
			
			// Trova il campo importo corrispondente
			var importo_field = $(this).closest("tr").find(".importo");
			importo_field.val(importo);

			totale_percentuale += percentuale;
		});
		
		// Aggiorna il totale visualizzato
		$("#totale-percentuale").text(totale_percentuale.toFixed(0));
		
		// Mostra o nascondi l'avviso in base al totale
		if (totale_percentuale != 100 && totale_percentuale != 0) {
			$("#alert-percentuale").removeClass("hide");
		} else {
			$("#alert-percentuale").addClass("hide");
		}
		
		return (totale_percentuale == 100 || totale_percentuale == 0);
	}
	
	// Inizializza gli importi
	aggiornaImporti();
	
	// Controlla se il pulsante di salvataggio deve essere abilitato
	function controllaPulsanteCrea() {
		var totale_valido = aggiornaImporti();
		var righe_presenti = $("#righe-ammortamento tr").length > 0;
		
		// Abilita il pulsante solo se ci sono righe e il totale è 100%
		if (totale_valido && righe_presenti) {
			$("#btn-salva").removeClass("disabled");
		} else {
			$("#btn-salva").addClass("disabled");
		}
	}
	
	// Controlla lo stato del pulsante all'avvio
	controllaPulsanteCrea();
	
	// Controlla lo stato del pulsante quando vengono modificate le percentuali
	$(document).on("input", ".percentuale", function() {
		controllaPulsanteCrea();
	});
	
	// Controlla lo stato del pulsante quando viene aggiunta o rimossa una riga
	$("#add-riga").click(function() {
		controllaPulsanteCrea();
	});
});

// Funzione per confermare l'applicazione dell'ammortamento
function applicaAmmortamento() {
	swal({
		title: "<?php echo tr('Applicare l\'ammortamento?'); ?>",
		html: "<?php echo tr('Sei sicuro di voler applicare questo ammortamento?'); ?>",
		type: "success",
		showCancelButton: true,
		confirmButtonText: "<?php echo tr('Sì, procedi'); ?>"
	}).then(function () {
		$('#save').click();
	}).catch(swal.noop);
}
</script>
