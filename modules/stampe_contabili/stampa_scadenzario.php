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

// Trovo id_print della stampa
$link = Prints::getHref('Scadenzario', null);

echo '
	<div class="row">
        <div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "value": "" ]}
		</div>
        <div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "value": "" ]}
		</div>
		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Includi scadenze pagate').'", "name": "is_pagata" ]}
		</div>
    </div>
    <div class="row">
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Includi solo Ri.Ba').'", "name": "is_riba" ]}
        </div>
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Includi solo scadenze Clienti').'", "name": "is_cliente" ]}
        </div>
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Includi solo scadenze Fornitori').'", "name": "is_fornitore" ]}
        </div>
	</div>

	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" onclick="avvia_stampa();" class="btn btn-primary">
				<i class="fa fa-print"></i> '.tr('Stampa scadenzario').'
			</button>
		</div>
	</div>

<script>$(document).ready(init)</script>';

echo '
<script>
	function avvia_stampa (){
        var data_inizio = $("#data_inizio").val();
        var data_fine = $("#data_fine").val();
        var is_pagata = $("#is_pagata").is(":checked");
        var is_riba = $("#is_riba").is(":checked");
        var is_cliente = $("#is_cliente").is(":checked");
        var is_fornitore = $("#is_fornitore").is(":checked");

		window.open("'.$link.'&data_inizio="+data_inizio+"&data_fine="+data_fine+"&is_pagata="+is_pagata+"&is_riba="+is_riba+"&is_cliente="+is_cliente+"&is_fornitore="+is_fornitore, "_blank");
	}

    $("#is_cliente").change(function() {
        if($(this).is(":checked")) {
            $("#is_fornitore").prop("checked", false);
        }
    });
    
    $("#is_fornitore").change(function() {
        if($(this).is(":checked")) {
            $("#is_cliente").prop("checked", false);
        }
    });
</script>';