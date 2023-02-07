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

use Modules\Fatture\Fattura;

$module = Modules::get($id_module);
$module_interventi = Modules::get('Interventi');

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $conti = 'conti-vendite';
} else {
    $dir = 'uscita';
    $conti = 'conti-acquisti';
}

$fattura = Fattura::find($id_record);
$numero = ($fattura->numero_esterno != '') ? $fattura->numero_esterno : $fattura->numero;
$idanagrafica = $fattura->idanagrafica;

$idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');

$where = '';
// Lettura interventi non collegati a preventivi, ordini e contratti
if (!setting('Permetti fatturazione delle attività collegate a contratti, ordini e preventivi')) {
    $where = 'AND in_interventi.id_preventivo IS NULL AND in_interventi.id_contratto IS NULL AND in_interventi.id_ordine IS NULL';
}

/*
    Form di inserimento riga documento
*/

echo '
<p>'.tr('Documento numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>

<form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="add_intervento">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

$rs = $dbo->fetchArray('SELECT
        in_interventi.id,
        CONCAT(\'Attività numero \', in_interventi.codice, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), in_interventi.data_richiesta), \'%d/%m/%Y\'), " [", `in_statiintervento`.`descrizione` , "]") AS descrizione,
        CONCAT(\'Attività numero \', in_interventi.codice, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), in_interventi.data_richiesta), \'%d/%m/%Y\')) AS info,
        CONCAT(\'\n\', in_interventi.descrizione) AS descrizione_intervento,
        IF(idclientefinale='.prepare($idanagrafica).', \'Interventi conto terzi\', \'Interventi diretti\') AS `optgroup`
    FROM
        in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento
    WHERE
        (in_interventi.idanagrafica='.prepare($idanagrafica).' OR in_interventi.idclientefinale='.prepare($idanagrafica).')
        AND in_statiintervento.is_completato=1 AND in_statiintervento.is_fatturabile=1
        AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL)
        AND NOT in_interventi.id IN (SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL) '.$where);
foreach ($rs as $key => $value) {
    $intervento = \Modules\Interventi\Intervento::find($value['id']);
    $prezzo = $intervento->totale;

    $rs[$key]['prezzo'] = Translator::numberToLocale($prezzo);
    $rs[$key]['descrizione_intervento'] = str_replace("'", " ", strip_tags($rs[$key]['descrizione_intervento']));
    $rs[$key]['info'] = str_replace("'", " ", strip_tags($module_interventi->replacePlaceholders($value['id'], setting('Descrizione personalizzata in fatturazione')))) ?: $rs[$key]['info'];
}

// Intervento
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Intervento').'", "name": "idintervento", "required": 1, "values": '.json_encode($rs).', "extra": "onchange=\"$data = $(this).selectData(); if($data){  $(\'#descrizione\').val($data.info); $(\'#prezzo\').val($data.prezzo);};\"" ]}
        </div>

		<div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Copia descrizione').'", "name": "copia_descrizione", "placeholder": "'.tr('Copia anche la descrizione dell\'intervento').'." ]}
        </div>

    </div>';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1 ]}
        </div>
    </div>';

$options = [
    'action' => 'add',
    'hide_conto' => true,
    'dir' => $dir,
    'superamento_soglia_qta' => setting('Permetti il superamento della soglia quantità dei documenti di origine'),
];

// Leggo la ritenuta d'acconto predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$ritenuta_acconto = $dbo->fetchOne('SELECT id_ritenuta_acconto_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS id_ritenuta_acconto FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$options['id_ritenuta_acconto_predefined'] = $ritenuta_acconto['id_ritenuta_acconto'];

echo App::internalLoad('conti.php', [], $options);

// Leggo l'iva predefinita dall'articolo e se non c'è leggo quella predefinita generica
$idiva = $fattura->anagrafica->idiva_vendite ?: setting('Iva predefinita');

// Iva
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "ajax-source": "iva", "help": "'.tr("L'aliquota IVA selezionata sovrascrive il valore predditivo presentato in Attività, modificando di conseguenza le sessioni di lavoro dei tecnici").'. '.tr('Righe generiche, articoli e sconti non verranno influenzati').'."]}
        </div>';

echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Conto').'", "name": "idconto", "required": 1, "value": "'.$idconto.'", "ajax-source": "'.$conti.'" ]}
        </div>
    </div>';

// Costo unitario
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "icon-after": "'.currency().'", "disabled": 1 ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';
