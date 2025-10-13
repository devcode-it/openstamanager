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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-2">
			{[ "type": "span", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$title$" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "checkbox", "label": "<?php echo tr('Non conteggiare'); ?>", "name": "non_conteggiare", "help": "<?php echo tr('Specifica se scalare dal consuntivo collegato'); ?>.", "value": "$non_conteggiare$"  ]}
		</div>

		<div class="col-md-2">
			{[ "type": "checkbox", "label": "<?php echo tr('Calcola km'); ?>", "name": "calcola_km", "help": "<?php echo tr('Specifica se inserire automaticamente i km percorsi tra sede azienda e sede cliente'); ?>.", "value": "$calcola_km$"  ]}
		</div>

		<div class="col-md-2">
			{[ "type": "number", "label": "<?php echo tr('Tempo standard'); ?>", "name": "tempo_standard", "help": "<?php echo tr('Valore compreso tra 0,25 - 24 ore. <br><small>Esempi: <em><ul><li>60 minuti = 1 ora</li><li>30 minuti = 0,5 ore</li><li>15 minuti = 0,25 ore</li></ul></em></small> Suggerisce il tempo solitamente impiegato per questa tipologia di attivita'); ?>.", "min-value": "0", "max-value": "24", "class": "text-center", "value": "$tempo_standard$", "icon-after": "ore"  ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			{[ "type": "textarea", "label": "<?php echo tr('Note da riportare nella stampa attività'); ?>", "name": "note", "value": "$note$" ]}
		</div>
	</div>

	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Addebiti unitari al cliente'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito orario'); ?>", "name": "costo_orario", "required": 1, "value": "$costo_orario$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Addebito al cliente'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito km'); ?>", "name": "costo_km", "required": 1, "value": "$costo_km$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo al Cliente per KM'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito diritto ch.'); ?>", "name": "costo_diritto_chiamata", "required": 1, "value": "$costo_diritto_chiamata$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Addebito al Cliente per il diritto di chiamata'); ?>" ]}
				</div>
			</div>
		</div>
	</div>


	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Costi unitari del tecnico'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo orario'); ?>", "name": "costo_orario_tecnico", "required": 1, "value": "$costo_orario_tecnico$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo interno'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo km'); ?>", "name": "costo_km_tecnico", "required_tecnico": 1, "value": "$costo_km_tecnico$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo interno per  KM'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo diritto ch.'); ?>", "name": "costo_diritto_chiamata_tecnico", "required": 1, "value": "$costo_diritto_chiamata_tecnico$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo interno per il diritto di chiamata'); ?>" ]}
				</div>
			</div>
		</div>
	</div>
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Righe aggiuntive predefinite'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-12" id="righe">
					<script>$('#righe').load('<?php echo $module->fileurl('ajax_righe.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');</script>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12 text-right">
					<button type="button" class="btn btn-primary" onclick="launch_modal('<?php echo tr('Aggiungi riga'); ?>', '<?php echo $module->fileurl('add_righe.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>', 1);"><i class="fa fa-plus"></i><?php echo tr(' Aggiungi'); ?>..</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Addebiti e costi per fasce orarie'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-12" id="addebiti_costi">
					<script>$('#addebiti_costi').load('<?php echo $module->fileurl('ajax_addebiti_costi.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>');</script>
				</div>
			</div>
		</div>
	</div>


</form>

<div class="card card-warning collapsable collapsed-card" id="documenti-collegati-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> <span id="documenti-collegati-title"><?php echo tr('Documenti collegati'); ?></span></h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" id="documenti-collegati-toggle"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body" id="documenti-collegati-body">
        <div class="text-center" id="documenti-collegati-loading">
            <i class="fa fa-spinner fa-spin"></i> <?php echo tr('Caricamento documenti collegati in corso'); ?>...
        </div>
        <div id="documenti-collegati-content" style="display: none;"></div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var documentiCaricati = false;

    // Mostra la card inizialmente e carica il conteggio
    $('#documenti-collegati-card').show();
    caricaConteggio();

    // Carica i documenti quando il card viene espanso
    // Utilizziamo l'evento specifico di AdminLTE per il collapse/expand
    $('#documenti-collegati-card').on('expanded.lte.cardwidget', function() {
        if (!documentiCaricati) {
            caricaDocumentiCollegati();
        }
    });

    // Fallback: se l'evento AdminLTE non funziona, usa il click diretto
    $('#documenti-collegati-toggle').on('click', function() {

        if (!documentiCaricati && $('#documenti-collegati-card').hasClass('collapsed-card')) {
            setTimeout(function() {
                if (!$('#documenti-collegati-card').hasClass('collapsed-card')) {
                    caricaDocumentiCollegati();
                } else {
                    // Se la card non si espande correttamente, carica comunque i documenti
                    caricaDocumentiCollegati();
                }
            }, 500);
        }
    });

    function caricaConteggio() {
        $.get('<?php echo $module->fileurl('ajax_documenti_collegati.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&count_only=1')
        .done(function(data) {
            var response;

            // Se la risposta è già un oggetto (jQuery ha fatto il parsing automatico)
            if (typeof data === 'object') {
                response = data;
            } else {
                // Prova a fare il parsing manuale
                try {
                    var cleanData = data.trim();
                    response = JSON.parse(cleanData);
                } catch (e) {

                    // In caso di errore, mantieni la card visibile
                    $('#documenti-collegati-title').text('<?php echo tr('Documenti collegati'); ?>');
                    $('#documenti-collegati-card').show();
                    return;
                }
            }

            if (response.count > 0) {
                $('#documenti-collegati-title').text('<?php echo tr('Documenti collegati'); ?>: ' + response.count);
                $('#documenti-collegati-card').show();
            } else {
                $('#documenti-collegati-card').hide();
            }
        })
        .fail(function(xhr, status, error) {
            // In caso di errore di rete, mantieni la card visibile
            $('#documenti-collegati-title').text('<?php echo tr('Documenti collegati'); ?>');
            $('#documenti-collegati-card').show();
        });
    }

    function caricaDocumentiCollegati() {
        if (documentiCaricati) return;

        $('#documenti-collegati-loading').show();
        $('#documenti-collegati-content').hide();

        var url = '<?php echo $module->fileurl('ajax_documenti_collegati.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>';
        $.get(url)
        .done(function(data) {
            $('#documenti-collegati-loading').hide();
            $('#documenti-collegati-content').html(data).show();
            documentiCaricati = true;
        })
        .fail(function(xhr, status, error) {
            $('#documenti-collegati-loading').hide();
            var errorMsg = '<?php echo tr('Errore nel caricamento dei documenti collegati'); ?>';
            if (xhr.responseText) {
                errorMsg += ': ' + xhr.responseText;
            }
            $('#documenti-collegati-content').html('<div class="alert alert-danger">' + errorMsg + '</div>').show();
        });
    }
});
</script>

<?php
echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
?>
