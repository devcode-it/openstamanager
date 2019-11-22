<?php

include_once __DIR__.'/../../../core.php';

$rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.idstatointervento FROm in_statiintervento WHERE in_statiintervento.codice=\'WIP\') ORDER BY data_richiesta ASC');

if (!empty($rs)) {
    echo '
<table class="table table-hover">
    <tr>
        <th width="50%">'.tr('Attività').'</th>
        <th width="15%" class="text-center">'.tr('Data richiesta').'</th>
    </tr>';

    foreach ($rs as $r) {
        $data_richiesta = !empty($r['data_richiesta']) ? Translator::dateToLocale($r['data_richiesta']) : '';

        echo '
    <tr >
        <td>
            '.Modules::link('Interventi', $r['id'], 'Intervento n. '.$r['codice'].' del '.$data_richiesta).'<br>
            <small class="help-block">'.$r['ragione_sociale'].'</small>
        </td>
        <td class="text-center">'.$data_richiesta.'</td>
    </tr>';
    }
    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono attività programmate').'.</p>';
}
