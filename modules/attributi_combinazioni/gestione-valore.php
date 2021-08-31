<?php

use Modules\AttributiCombinazioni\ValoreAttributo;

include_once __DIR__.'/../../core.php';

$id_valore = filter('id_valore');
$testo_valore = '';
if (!empty($id_valore)) {
    $valore = ValoreAttributo::find($id_valore);
    $testo_valore = $valore->nome;
}

echo '
<form action="" method="post" id="form-valore">
    <input type="hidden" name="op" value="gestione-valore">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_valore" value="'.$id_valore.'">

    <div class="row">
        <div class="col-md-12">
			{[ "type": "text", "label": "'.tr('Valore').'", "name": "nome", "value": "'.$testo_valore.'", "required": 1 ]}
		</div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>';
