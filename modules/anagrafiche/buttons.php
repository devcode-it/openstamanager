<?php
if (in_array($id_cliente, $tipi_anagrafica) or in_array($id_fornitore, $tipi_anagrafica)) {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-magic"></i>
        '.tr('Crea').'... <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">';

    //Aggiunta utente per i tecnici
    if (in_array($id_tecnico, $tipi_anagrafica)) {
        echo '
        <li><a data-toggle="modal" data-title="'.tr('Aggiungi utente').'" data-href="modules/utenti/user.php?id_module='.Modules::get('Utenti e permessi')['id'].'&id_record='.$dbo->fetchOne('SELECT id FROM zz_groups WHERE nome=\'Tecnici\'')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-user"></i>'.tr('Nuovo utente').'
        </a></li>';
    }


    if (in_array($id_cliente, $tipi_anagrafica)) {
        echo '
        <li><a data-toggle="modal" data-title="'.tr('Aggiungi intervento').'" data-href="add.php?id_module='.Modules::get('Interventi')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-wrench"></i>'.tr('Nuovo intervento').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi preventivo').'" data-href="add.php?id_module='.Modules::get('Preventivi')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-text"></i>'.tr('Nuovo preventivo').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi contratto').'" data-href="add.php?id_module='.Modules::get('Contratti')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-text-o"></i>'.tr('Nuovo contratto').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi ordine cliente').'" data-href="add.php?id_module='.Modules::get('Ordini cliente')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-o"></i>'.tr('Nuovo ordine cliente').'
        </a></li>

		  <li><a data-toggle="modal" data-title="'.tr('Aggiungi ddt uscita').'" data-href="add.php?id_module='.Modules::get('Ddt di vendita')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-truck"></i>'.tr('Nuovo ddt in uscita').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi fattura di vendita').'" data-href="add.php?id_module='.Modules::get('Fatture di vendita')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file"></i>'.tr('Nuova fattura di vendita').'
        </a></li>';
    }

    if (in_array($id_fornitore, $tipi_anagrafica)) {
        echo  '<li><a data-toggle="modal" data-title="'.tr('Aggiungi ordine fornitore').'" data-href="add.php?id_module='.Modules::get('Ordini fornitore')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-o fa-flip-horizontal"></i>'.tr('Nuovo ordine fornitore').'
    </a></li>

      <li><a data-toggle="modal" data-title="'.tr('Aggiungi ddt entrata').'" data-href="add.php?id_module='.Modules::get('Ddt di acquisto')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-truck fa-flip-horizontal"></i>'.tr('Nuovo ddt in entrata').'
    </a></li>

    <li><a data-toggle="modal" data-title="'.tr('Aggiungi fattura di acquisto').'" data-href="add.php?id_module='.Modules::get('Fatture di acquisto')['id'].'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file fa-flip-horizontal"></i>'.tr('Nuova fattura di acquisto').'
    </a></li>';
    }

    echo '
    </ul>
</div>';
}
