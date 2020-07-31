<?php

include_once __DIR__.'/../../../core.php';

echo '
<form action="" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">

    <div class="row">
        <div class="col-md-9">
            {[ "type": "text", "label": "'.tr('Codice').'", "name": "codice", "required": 1, "value": "", "validation": "codice" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Copia allegati').'", "name": "copia_allegati", "value": 1 ]}
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
