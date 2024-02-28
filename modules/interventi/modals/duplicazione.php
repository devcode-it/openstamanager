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

echo '
<form action="" method="post" id="copia-intervento">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">

    <div class="row">
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "value": "-now-", "required":1 ]}
        </div>

        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "value": "-now-", "required":1 ]}
        </div>

        <div class="col-md-3">
            {[ "type": "time", "label": "'.tr('Ora richiesta').'", "name": "ora_richiesta", "value": "-now-", "required":1 ]}
        </div>

        <div class="col-md-3">
            {[ "type": "time", "label": "'.tr('Ora scadenza').'", "name": "ora_scadenza" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "multiple":"1", "label": "'.tr('Giorni').'", "name": "giorni[]", "required": 0, "value": "'.strtolower(setting('Giorni lavorativi')).'", "values": "list=\"lunedì\":\"'.tr('Lunedì').'\", \"martedì\":\"'.tr('Martedì').'\", \"mercoledì\":\"'.tr('Mercoledì').'\", \"giovedì\":\"'.tr('Giovedì').'\", \"venerdì\":\"'.tr('Venerdì').'\", \"sabato\":\"'.tr('Sabato').'\", \"domenica\":\"'.tr('Domenica').'\"", "required":1 ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `in_statiintervento`.`id`, `name` as descrizione, `colore` AS _bgcolor_ FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `deleted_at` IS NULL ORDER BY `name`", "value": "" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            {["type": "checkbox", "label": "'.tr('Duplica righe').'", "name": "copia_righe", "help": "'.tr('Selezione per riportare anche le righe nella nuova attività').'" ]}
        </div>

        <div class="col-md-3">
            {["type": "checkbox", "label": "'.tr('Duplica sessioni').'", "name": "copia_sessioni", "help": "'.tr('Selezione per riportare anche le sessioni di lavoro nella nuova attività').'" ]}
        </div>

        <div class="col-md-3">
            {["type": "checkbox", "label": "'.tr('Duplica impianti').'", "name": "copia_impianti", "help": "'.tr('Selezione per riportare anche gli impianti nella nuova attività').'" ]}
        </div>

        <div class="col-md-3">
            {["type": "checkbox", "label": "'.tr('Duplica allegati').'", "name": "copia_allegati", "help": "'.tr('Selezione per riportare anche gli allegati nella nuova attività').'", "value": 1 ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
                <i class="fa fa-copy"></i> '.tr('Duplica').'
            </button>
		</div>
	</div>
</form>';

echo '
<script>$(document).ready(init)</script>';
