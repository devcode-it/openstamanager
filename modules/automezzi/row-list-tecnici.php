<?php

include_once __DIR__.'/../../core.php';

/*
    TECNICI ASSEGNATI ALL'AUTOMEZZO
*/
$q_art = 'SELECT zz_users.*, an_anagrafiche.ragione_sociale, zz_groups.nome FROM zz_user_sedi INNER JOIN zz_users ON zz_user_sedi.id_user = zz_users.id INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=zz_users.idanagrafica INNER JOIN zz_groups ON zz_users.idgruppo = zz_groups.id WHERE zz_user_sedi.idsede='.prepare($id_record).' ORDER BY an_anagrafiche.ragione_sociale';
$rs_art = $dbo->fetchArray($q_art);

if (!empty($rs_art)) {
    echo '
<div style="max-height: 300px; overflow: auto;">
    <table class="table table-striped table-hover table-sm">
        <tr>
            <th>'.tr('Anagrafica').'</th>
            <th width="35%">'.tr('Gruppo').'</th>
        </tr>';

    foreach ($rs_art as $r) {
        // Tecnico
        echo '
        <tr>
            <td>
                '.$r['ragione_sociale'].'
            </td>';

        // Data di inizio
        echo '
            <td>
                '.$r['nome'].'
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
