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

use Modules\Iva\Aliquota;

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
$iva_predefinita = setting('Iva predefinita');
$aliquota_predefinita = floatval(Aliquota::find($iva_predefinita)->percentuale);
$idconto_acquisto = $dbo->fetchOne('SELECT valore FROM zz_settings WHERE nome = "Conto predefinito di acquisto"')['valore'];
$idconto_vendita = $dbo->fetchOne('SELECT valore FROM zz_settings WHERE nome = "Conto predefinito di vendita"')['valore'];

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "required": 0, "value": "<?php echo htmlentities(filter('codice')) ?: ''; ?>", "help": "<?php echo tr('Se non specificato, il codice verrà calcolato automaticamente'); ?>", "validation": "codice" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Barcode'); ?>", "name": "barcode", "required": 0, "value": "<?php echo htmlentities(filter('barcode')) ?: ''; ?>", "validation": "barcode" ]}
		</div>
    </div>

    <div class="row">
		<div class="col-md-12">
			{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo htmlentities(filter('descrizione')) ?: ''; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 0, "ajax-source": "categorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "subcategoria", "id": "subcategoria_add", "ajax-source": "sottocategorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>||hide" ]}
		</div>
	</div>

    <div class="box box-info collapsed-box">
        <div class="box-header with-border">
            <h3 class="box-title"><?php echo tr('Informazioni aggiuntive'); ?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "number", "label": "<?php echo tr('Prezzo di acquisto'); ?>", "name": "prezzo_acquisto", "icon-after": "<?php echo currency(); ?>", "value": "<?php echo htmlentities(filter('prezzo_acquisto')) ?: 0; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "number", "label": "<?php echo tr('Coefficiente di vendita'); ?>", "name": "coefficiente", "help": "<?php echo tr('Imposta un coefficiente per calcolare automaticamente il prezzo di vendita quando cambia il prezzo di acquisto'); ?>." ]}
                </div>

                <div class="col-md-4">
                    <?php
                    if (!setting('Utilizza prezzi di vendita comprensivi di IVA')) {
                        echo '
                    <button type="button" class="btn btn-info btn-xs pull-right tip pull-right" title="'.tr('Scorpora l\'IVA dal prezzo di vendita.').'" id="scorpora_iva_add"><i class="fa fa-calculator" aria-hidden="true"></i></button>';
                    }
                    ?>

                    {[ "type": "number", "label": "<?php echo tr('Prezzo di vendita'); ?>", "name": "prezzo_vendita", "icon-after": "<?php echo currency(); ?>", "help": "<?php echo setting('Utilizza prezzi di vendita comprensivi di IVA') ? tr('Importo IVA inclusa') : ''; ?>" ]}
                </div>

            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "number", "label": "<?php echo tr('Quantità iniziale'); ?>", "name": "qta", "decimals": "qta" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "number", "label": "<?php echo tr('Soglia minima quantità'); ?>", "name": "threshold_qta", "decimals": "qta", "min-value": "undefined" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Iva di vendita'); ?>", "name": "idiva_vendita", "ajax-source": "iva", "valore_predefinito": "Iva predefinita", "help": "<?php echo tr('Se non specificata, verrà utilizzata l\'iva di default delle impostazioni'); ?>" ]}
                    <input type="hidden" name="prezzi_ivati" value="<?php echo $prezzi_ivati; ?>">
                    <input type="hidden" name="aliquota_predefinita" value="<?php echo $aliquota_predefinita; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Unità di misura'); ?>", "name": "um", "value": "", "ajax-source": "misure", "icon-after": "add|<?php echo Modules::get('Unità di misura')['id']; ?>" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('U.m. secondaria'); ?>", "name": "um_secondaria", "value": "", "ajax-source": "misure", "help": "<?php echo tr("Unità di misura da utilizzare nelle stampe di Ordini fornitori in relazione all'articolo"); ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "number", "label": "<?php echo tr('Fattore moltiplicativo'); ?>", "name": "fattore_um_secondaria", "value": "", "decimals": "qta", "help": "<?php echo tr("Fattore moltiplicativo per l'unità di misura da utilizzare nelle stampe di Ordini fornitori"); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Conto predefinito di acquisto'); ?>", "name": "idconto_acquisto", "ajax-source": "conti-acquisti", "value": "<?php echo $idconto_acquisto; ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Conto predefinito di vendita'); ?>", "name": "idconto_vendita", "ajax-source": "conti-vendite", "value": "<?php echo $idconto_vendita; ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
					{[ "type": "checkbox", "label": "<?php echo tr('Abilita serial number'); ?>", "name": "abilita_serial", "help": "<?php echo tr('Abilita serial number in fase di aggiunta articolo in fattura o ddt'); ?>", "placeholder": "<?php echo tr('Serial number'); ?>" ]}
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

<script>
iva_vendita = $("#add-form").find("#idiva_vendita");
percentuale = 0;

$(document).ready(function () {
    var sub = $('#add-form').find('#subcategoria_add');
    var original = sub.parent().find(".input-group-addon button").attr("onclick");

    $('#add-form').find('#categoria').change(function() {
        updateSelectOption("id_categoria", $(this).val());
        session_set('superselect,id_categoria', $(this).val(), 0);

        sub.selectReset();

        if($(this).val()){
            sub.parent().find(".input-group-addon button").removeClass("hide");
            sub.parent().find(".input-group-addon button").attr("onclick", original.replace('&ajax=yes', "&ajax=yes&id_original=" + $(this).val()));
        }
        else {
            sub.parent().find(".input-group-addon button").addClass("hide");
        }
    });

    input("coefficiente").on('keyup', function(){
        if (iva_vendita.val()) {
            percentuale = parseFloat(iva_vendita.selectData().percentuale);
        }
        if (!percentuale) {
            percentuale =  parseFloat(input("aliquota_predefinita").get());
        }
        if (input("coefficiente").get()) {
            let prezzo_vendita = input('prezzo_acquisto').get() * input("coefficiente").get();
            if (parseFloat(input("prezzi_ivati").get())) {
                prezzo_vendita = prezzo_vendita + (prezzo_vendita * percentuale / 100);
            }
            input("prezzo_vendita").set(prezzo_vendita);
            input("prezzo_vendita").disable();
            $("#scorpora_iva_add").addClass("disabled");
        } else {
            input("prezzo_vendita").enable();
            $("#scorpora_iva_add").removeClass("disabled");
        }
    });

    input("prezzo_acquisto").on('keyup change', function(){
        if (iva_vendita.val()) {
            percentuale = parseFloat(iva_vendita.selectData().percentuale);
        }
        if (!percentuale) {
            percentuale =  parseFloat(input("aliquota_predefinita").get());
        }
        if (input("coefficiente").get()) {
            let prezzo_vendita = input('prezzo_acquisto').get() * input("coefficiente").get();
            if (parseFloat(input("prezzi_ivati").get())) {
                prezzo_vendita = prezzo_vendita + (prezzo_vendita * percentuale / 100);
            }
            input("prezzo_vendita").set(prezzo_vendita);
            input("prezzo_vendita").disable();
            $("#scorpora_iva_add").addClass("disabled");
        } else {
            input("prezzo_vendita").enable();
            $("#scorpora_iva_add").removeClass("disabled");
        }
    });

    $("#scorpora_iva_add").click( function() {
        scorpora_iva_add();
    });
});

function scorpora_iva_add() {
    if (iva_vendita.val()) {
        percentuale = parseFloat(iva_vendita.selectData().percentuale);
    }
    if (!percentuale) {
        percentuale =  parseFloat(input("aliquota_predefinita").get());
    }
    if(!percentuale) return;

    let input = $("#prezzo_vendita");
    let prezzo = input.val().toEnglish();
    let scorporato = prezzo * 100 / (100 + percentuale);
    input.val(scorporato);
}
</script>
