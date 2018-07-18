<?php

if (in_array($id_cliente, $tipi_anagrafica)) {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        '.tr('Nuovo').' <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>

    <ul class="dropdown-menu dropdown-menu-right">
        <li><a data-toggle="modal" data-title="'.tr('Aggiungi intervento').'" data-target="#bs-popup" data-href="add.php?id_module='.Modules::get('Interventi')['id'].'&idanagrafica='.$record['idanagrafica'].'">
            '.tr('Nuovo intervento').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi preventivo').'" data-target="#bs-popup" data-href="add.php?id_module='.Modules::get('Preventivi')['id'].'&idanagrafica='.$record['idanagrafica'].'">
            '.tr('Nuovo preventivo').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi contratto').'" data-target="#bs-popup" data-href="add.php?id_module='.Modules::get('Contratti')['id'].'&idanagrafica='.$record['idanagrafica'].'">
            '.tr('Nuovo contratto').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi ordine').'" data-target="#bs-popup" data-href="add.php?id_module='.Modules::get('Ordini cliente')['id'].'&idanagrafica='.$record['idanagrafica'].'">
            '.tr('Nuovo ordine').'
        </a></li>

		  <li><a data-toggle="modal" data-title="'.tr('Aggiungi ddt').'" data-target="#bs-popup" data-href="add.php?id_module='.Modules::get('Ddt di vendita')['id'].'&idanagrafica='.$record['idanagrafica'].'">
            '.tr('Nuovo ddt').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi fattura').'" data-target="#bs-popup" data-href="add.php?id_module='.Modules::get('Fatture di vendita')['id'].'&idanagrafica='.$record['idanagrafica'].'">
            '.tr('Nuova fattura').'
        </a></li>

    </ul>
</div>';
}
