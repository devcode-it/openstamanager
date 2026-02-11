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

include_once __DIR__.'/../../../core.php';

$sessione = $dbo->fetchOne('SELECT in_interventi_tecnici.*, an_anagrafiche.ragione_sociale, an_anagrafiche.deleted_at, in_interventi_tecnici.tipo_scontokm AS tipo_sconto_km, in_interventi_tecnici.prezzo_ore_unitario, in_interventi_tecnici.prezzo_km_unitario, in_interventi_tecnici.prezzo_dirittochiamata FROM in_interventi_tecnici INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare(get('id_sessione')));

$op = 'split_sessione';
$button = '<i class="fa fa-pause"></i> '.tr('Applica pausa');

// Calcola la probabile pausa pranzo (a metà tra inizio e fine)
$inizio = strtotime($sessione['orario_inizio']);
$fine = strtotime($sessione['orario_fine']);
$meta = ($inizio + $fine) / 2;

// Calcola orario minimo per inizio pausa
$pausa_inizio_min = date('Y-m-d H:i:s', $inizio + setting('Numero di minuti di avanzamento delle sessioni delle attività')*60);

// Calcola orario massimo per fine pausa
$pausa_fine_max = date('Y-m-d H:i:s', $fine - setting('Numero di minuti di avanzamento delle sessioni delle attività')*60);

// Calcola la probabile pausa pranzo (1 ora a metà della sessione)
$pausa_inizio_default = date('Y-m-d H:i:s', $meta - 1800);
$pausa_fine_default = date('Y-m-d H:i:s', $meta + 1800);

// Assicurati che la pausa sia sempre minore rispetto alla sessione
if ($pausa_inizio_default < $pausa_inizio_min) {
    $pausa_inizio_default = $pausa_inizio_min;
}
if ($pausa_fine_default > $pausa_fine_max) {
    $pausa_fine_default = $pausa_fine_max;
}

echo '
<form id="add_form" action="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_sessione" value="'.$sessione['id'].'">';

// Informazioni sessione
echo '
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> '.tr('Sessione corrente: _TECNICO_ dal _INIZIO_ al _FINE_', [
            '_TECNICO_' => '<strong>'.$sessione['ragione_sociale'].'</strong>',
            '_INIZIO_' => '<strong>'.Translator::timestampToLocale($sessione['orario_inizio']).'</strong>',
            '_FINE_' => '<strong>'.Translator::timestampToLocale($sessione['orario_fine']).'</strong>',
        ]).'
    </div>';

// Orari pausa
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "timestamp", "label": "'.tr('Inizio pausa').'", "name": "pausa_inizio", "required": 1, "min-date": "'.$pausa_inizio_min.'", "max-date": "'.$pausa_fine_max.'", "value": "'.$pausa_inizio_default.'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "timestamp", "label": "'.tr('Fine pausa').'", "name": "pausa_fine", "required": 1, "min-date": "'.$pausa_inizio_min.'", "max-date": "'.$pausa_fine_max.'", "value": "'.$pausa_fine_default.'" ]}
        </div>
    </div>';

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">'.$button.'</button>
		</div>
    </div>
</form>';
