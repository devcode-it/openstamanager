<?php

include_once __DIR__.'/../../core.php';

/*
    TECNICI ASSEGNATI ALL'AUTOMEZZO
*/
$q_art = 'SELECT an_sedi_tecnici.*, an_anagrafiche.ragione_sociale FROM an_sedi_tecnici INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=an_sedi_tecnici.idtecnico WHERE an_sedi_tecnici.idsede='.prepare($id_record);
$rs_art = $dbo->fetchArray($q_art);

if (!empty($rs_art)) {
    echo '
<div style="max-height: 300px; overflow: auto;">
    <table class="table table-striped table-hover table-sm">
        <tr>
            <th>'.tr('Tecnico').'</th>
            <th width="25%">'.tr('Dal').'</th>
            <th width="25%">'.tr('Al').'</th>
            <th width="5%"></th>
        </tr>';

    foreach ($rs_art as $r) {
        // Tecnico
        echo '
        <tr>
            <td>
                <input type="hidden" name="idtecnico['.$r['id'].']" value="'.$r['idtecnico'].'">
                '.$r['ragione_sociale'].'
            </td>';

        // Data di inizio
        echo '
            <td>
                {[ "type": "date", "name": "data_inizio['.$r['id'].']", "required": 1, "value": "'.$r['data_inizio'].'" ]}
            </td>';

        // Data di fine
        echo '
            <td>
                {[ "type": "date", "name": "data_fine['.$r['id'].']", "value": "'.$r['data_fine'].'", "min-date": "'.$r['data_inizio'].'" ]}
            </td>';

        // Pulsanti per aggiornamento date tecnici
        echo '
            <td>
                <a class="btn btn-danger ask" data-backto="record-edit" data-op="deltech" data-id="'.$r['id'].'" data-msg="'.tr("Rimuovere il tecnico responsabile dell'automezzo?").'">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
    }

    echo '
    </table>
</div>';
} else {
    echo '
<p>'.tr('Nessun tecnico inserito').'...</p>';
}
