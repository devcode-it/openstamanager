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

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="'.$id_record.'">

	<div class="row">
		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "required": 1, "value": "$title$" ]}
		</div>
		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Classe').'", "name": "class", "required": 1, "value": "$class$" ]}
	</div>
		<div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data prossima esecuzione').'", "name": "next_execution_at", "value": "$next_execution_at$", "readonly": 1 ]}
		</div>
		<div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data precedente esecuzione').'", "name": "last_executed_at", "value": "$last_executed_at$", "readonly": 1 ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-offset-3 col-md-6">
			<div class="alert alert-info">
				<i class="fa fa-info-circle"></i> '.tr('Puoi trovare alcuni esempi qui:').' <a href="https://crontab-generator.com/it/" target="_blank">https://crontab-generator.com/it/</a>
			</div>
			{[ "type": "text", "label": "'.tr('Espressione').'", "name": "expression", "required": 1, "class": "text-center", "value": "$expression$", "extra": "", "readonly": 1 ]}
		</div>';
$expression = $record['expression'];

preg_match('/(.*?) (.*?) (.*?) (.*?) (.*?)/U', $record['expression'], $exp);

$minuto = $exp[1];
$ora = $exp[2];
$giorno = $exp[3];
$mese = $exp[4];
$giorno_sett = $exp[5];

echo '
	</div>
	<div class="row">
		<div class="col-md-offset-3 col-md-3">
			{[ "type": "text", "label": "'.tr('Minuto').'", "name": "minuto", "required": 1, "class": "text-center", "value": "'.$minuto.'", "readonly": 1]}
		</div>
		<div class="col-md-3">
			{[ "type": "select", "label": "'.tr('&nbsp').'", "name": "minuti", "value": "'.$minuto.'", "values":"list=\"*\": \"'.tr('Una volta al minuto (*)').'\",\"*/5\": \"'.tr('Una volta ogni cinque minuti (*/5)').'\",\"0,30\": \"'.tr('Una volta ogni trenta minuti (0,30)').'\",\"5\": \"'.tr('Al minuto 5 dell\'ora (5)').'\",\" \": \"'.tr('Personalizzato').'\""]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-offset-3 col-md-3">
			{[ "type": "text", "label": "'.tr('Ora').'", "name": "ora", "required": 1, "class": "text-center", "value": "'.$ora.'", "extra": "", "readonly": 1 ]}
		</div>
		<div class="col-md-3">
		{[ "type": "select", "label": "'.tr('&nbsp').'", "name": "ore", "value": "'.$ora.'", "values":"list=\"*\": \"'.tr('Ogni ora (*)').'\",\"*/2\": \"'.tr('Ogni due ore (*/2)').'\",\"*/4\": \"'.tr('Ogni 15 minuti (*/4)').'\",\"0,12\": \"'.tr('Ogni 12 ore (0,12)').'\",\"5\": \"'.tr('5:00 a.m. (5)').'\",\"17\": \"'.tr('5:00 p.m. (17)').'\",\" \": \"'.tr('Personalizzato').'\""]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-offset-3 col-md-3">
			{[ "type": "text", "label": "'.tr('Giorno').'", "name": "giorno", "required": 1, "class": "text-center", "value": "'.$giorno.'", "extra": "", "readonly": 1]}
		</div>
		<div class="col-md-3">
			{[ "type": "select", "label": "'.tr('&nbsp').'", "name": "giorni", "value": "'.$giorno.'", "values":"list=\"*\": \"'.tr('Ogni giorno (*)').'\",\"*/2\": \"'.tr('Ogni due giorni (*/2)').'\",\"1,15\": \"'.tr('Il primo e il 15 del mese (1,15)').'\",\"8\": \"'.tr('Il giorno 8 del mese (8)').'\",\" \": \"'.tr('Personalizzato').'\""]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-offset-3 col-md-3">
			{[ "type": "text", "label": "'.tr('Mese').'", "name": "mese", "required": 1, "class": "text-center", "value": "'.$mese.'", "extra": "", "readonly": 1]}
		</div>
		<div class="col-md-3">
			{[ "type": "select", "label": "'.tr('&nbsp').'", "name": "mesi", "value": "'.$mese.'", "values":"list=\"*\": \"'.tr('Ogni mese (*)').'\",\"*/2\": \"'.tr('Ogni due mesi (*/2)').'\",\"1,7\": \"'.tr('Ogni 6 mesi (1,7)').'\",\"8\": \"'.tr('Agosto (8)').'\",\" \": \"'.tr('Personalizzato').'\""]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-offset-3 col-md-3">
			{[ "type": "text", "label": "'.tr('Giorno della settimana').'", "name": "giorno_sett", "required": 1, "class": "text-center", "value": "'.$giorno_sett.'", "extra": "", "readonly": 1]}
		</div>
		<div class="col-md-3">
			{[ "type": "select", "label": "'.tr('&nbsp').'", "name": "giorni_sett", "value": "'.$giorno_sett.'", "values":"list=\"*\": \"'.tr('Ogni giorno (*)').'\",\"*/1-5\": \"'.tr('Ogni giorno della settimana (1-5)').'\",\"0,6\": \"'.tr('Ogni giorno del weekend (0,6)').'\",\"1,3,5\": \"'.tr('Ogni Lunedì, Mercoledì e Venerdì (1,3,5)').'\",\" \": \"'.tr('Personalizzato').'\""]}
		</div>
	</div>
</form>';
?>

<script>

// Alla modifica di un campo select aggiorna il corrispondente campo input, se viene selezionato "Personalizzato" abilita il campo input, altrimenti lo disabilita. Aggiorna infine l'espressione.
function updateField() {
	var fieldData = [
		{ select: 'minuti', input: 'minuto' },
		{ select: 'ore', input: 'ora' },
		{ select: 'giorni', input: 'giorno' },
		{ select: 'mesi', input: 'mese' },
		{ select: 'giorni_sett', input: 'giorno_sett' }
	];

	fieldData.forEach(function(field) {
		var $select = $('select[name="' + field.select + '"]');
		var $input = $('input[name="' + field.input + '"]');
		var value = $select.val();

		if (value == ' ') {
			$input.removeAttr('readonly');
		} else if (value == '') {
			$select.selectSet(' ');
		} else {
			$input.attr('readonly', 'readonly');
			$input.val(value);
		}
	});
}
$('select[name="minuti"], select[name="ore"], select[name="giorni"], select[name="mesi"], select[name="giorni_sett"]').on('change', function() {
    updateField();
    updateExpression();
});

//Aggiorna l'expression alla modifica dei campi input
function updateExpression() {
  var $minuto = $('input[name="minuto"]').val();
  var $ora = $('input[name="ora"]').val();
  var $giorno = $('input[name="giorno"]').val();
  var $mese = $('input[name="mese"]').val();
  var $giorno_sett = $('input[name="giorno_sett"]').val();

  var $expression = $minuto + ' ' + $ora + ' ' + $giorno + ' ' + $mese + ' ' + $giorno_sett;
  $('input[name="expression"]').val($expression);
}
$('input[name="minuto"], input[name="ora"], input[name="giorno"], input[name="mese"], input[name="giorno_sett"]').on('change', updateExpression);

// Se nell'input è inserito un valore personalizzato, seleziona Personalizzato nel select
var fieldData = [
    { select: 'minuti', input: 'minuto' },
    { select: 'ore', input: 'ora' },
    { select: 'giorni', input: 'giorno' },
    { select: 'mesi', input: 'mese' },
    { select: 'giorni_sett', input: 'giorno_sett' }
];

fieldData.forEach(function(field) {
    var $select = $('select[name="' + field.select + '"]');
    var $input = $('input[name="' + field.input + '"]');
    var value = $select.val();

    if (value == '') {
        $select.selectSet(' ');
	}
});

</script>