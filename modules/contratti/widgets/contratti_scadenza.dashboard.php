<?php

include_once __DIR__.'/../../../core.php';

$rs = $dbo->fetchArray('SELECT *, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND NOT EXISTS (SELECT id FROM co_righe_documenti WHERE co_righe_documenti.idcontratto = co_contratti.id) AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970 ORDER BY giorni_rimanenti ASC');

if (!empty($rs)) {
    echo '
<table class="table table-hover">
    <tr>
        <th width="50%">'.tr('Contratto').'</th>
        <th width="15%">'.tr('Data inizio').'</th>
        <th width="15%">'.tr('Data conclusione').'</th>
        <th width="20%">'.tr('Rinnovo').'</th>
    </tr>';

    foreach ($rs as $r) {
        $data_accettazione = !empty($r['data_accettazione']) ? Translator::dateToLocale($r['data_accettazione']) : '';

        $data_conclusione = !empty($r['data_conclusione']) ? Translator::dateToLocale($r['data_conclusione']) : '';

        // Se scaduto, segna la riga in rosso
        $class = (strtotime($r['data_conclusione']) < strtotime(date('Y-m-d')) && !empty($data_conclusione)) ? 'danger' : '';

        $scadenza = ($r['giorni_rimanenti'] > 0) ? tr('scade fra _DAYS_ giorni') : tr('scaduto da _DAYS_ giorni');
        $scadenza = str_replace('_DAYS_', $r['giorni_rimanenti'], $scadenza);

        echo '
    <tr class="'.$class.'">
        <td>
            '.Modules::link('Contratti', $r['id'], $r['nome']).'<br>
            <small class="help-block">'.$r['ragione_sociale'].'</small>
        </td>
        <td class="text-center">'.$data_accettazione.'</td>
        <td class="text-center">'.$data_conclusione.'</td>
        <td class="text-center">'.$scadenza.'</td>
    </tr>';
    }
    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono contratti in scadenza').'.</p>';
}
