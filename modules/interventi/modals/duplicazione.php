<?php

include_once __DIR__.'/../../../core.php';

echo '
<form action="" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "timestamp", "label": "'.tr('Data/ora richiesta').'", "name": "data_richiesta", "required": 0, "value": "-now-", "required":1 ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL", "value": "" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {["type":"checkbox", "label":"'.tr('Duplica righe').'", "name":"righe", "value":"", "help":"'.tr('Selezione per riportare anche le righe nella nuova attività').'" ]}
        </div>

        <div class="col-md-6">
            {["type":"checkbox", "label":"'.tr('Duplica sessioni').'", "name":"sessioni", "value":"", "help":"'.tr('Selezione per riportare anche le sessioni di lavoro nella nuova attività').'" ]}
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
