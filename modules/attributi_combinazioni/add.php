<?php

include_once __DIR__.'/init.php';

echo '
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "required": 1, "help": "'.tr("Nome univoco dell'attributo").'" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Titolo').'", "name": "titolo", "required": 1, "help": "'.tr("Nome visibile dell'attributo").'" ]}
		</div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</form>';
