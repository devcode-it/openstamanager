<?php

include_once __DIR__.'/../../core.php';

/*
    TECNICI ASSEGNATI ALL'AUTOMEZZO
*/
$q_art = "SELECT *, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=dt_automezzi_tecnici.idtecnico) AS nometecnico FROM dt_automezzi_tecnici WHERE idautomezzo=".prepare($id_record);
$rs_art = $dbo->fetchArray($q_art);

if (!empty($rs_art)) {
    echo '
<table class="table table-striped table-hover table-condensed">
    <tr>
        <th>'.tr('Tecnico').'</th>
        <th width="25%">'.tr('dal').'</th>
        <th width="25%">'.tr('al').'</th>
        <th width="5%"></th>
    </tr>';

    foreach ($rs_art as $r) {
        // Tecnico
        echo '
    <tr>
        <td>
            <input type="hidden" name="idautomezzotecnico[]" value="'.$r['id'].'">
            '.$r['nometecnico'].'
        </td>';

        // Data di inizio
        echo '
        <td>
            {[ "type": "date", "name": "data_inizio['.$r['id'].']", "required": 1, "maxlength": 10, "value": "'.$r['data_inizio'].'" ]}
        </td>';

        // Data di fine
        echo '
        <td>
            {[ "type": "date", "name": "data_fine['.$r['id'].']", "maxlength": 10, "value": "'.$r['data_fine'].'", "min-date": "'.$r['data_inizio'].'" ]}
        </td>';

        // Pulsanti per aggiornamento date tecnici
        echo '
        <td>
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="deltech" data-id="'.$r['id'].'" data-msg="'.tr("Rimuovere il tecnico responsabile dell'automezzo?").'">
                <i class="fa fa-trash"></i>
            </a>
        </td>
    </tr>';

        echo '
    <script type="text/javascript">
        $(function () {
            $("#data_inizio'.$r['id'].'").on("dp.change", function (e) {
                $("#data_fine'.$r['id'].'").data("DateTimePicker").minDate(e.date);

                if($("#data_fine'.$r['id'].'").data("DateTimePicker").date() < e.date){
                    $("#data_fine'.$r['id'].'").data("DateTimePicker").date(e.date);
                }
            })
        });
    </script>';
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Nessun tecnico inserito').'...</p>';
}
