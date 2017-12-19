<?php

include_once __DIR__.'/../../../core.php';

// INTERVENTI ESEGUITI SU QUESTO IMPIANTO
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Interventi eseguiti su questo impianto').'</h3>
    </div>
    <div class="box-body">';

$results = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, descrizione, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=my_impianti_interventi.idintervento) AS data FROM my_impianti_interventi INNER JOIN in_interventi ON my_impianti_interventi.idintervento=in_interventi.id WHERE idimpianto='.prepare($id_record).' ORDER BY data DESC');

if (!empty($results)) {
    echo '
        <table class="table table-striped table-hover">
            <tr>
                <th width="25%">'.tr('Intervento').'</th>
                <th>'.tr('Descrizione').'</th>
            </tr>';

    foreach ($results as $result) {
        echo '
            <tr>
                <td>
                    '.Modules::link('Interventi', $result['id'], tr('Intervento num. _NUM_ del _DATE_', [
                        '_NUM_' => $result['codice'],
                        '_DATE_' => Translator::dateToLocale($result['data']),
                    ])).'
                </td>
                <td>'.nl2br($result['descrizione']).'</td>
            </tr>';
    }

    echo '
        </table>';
} else {
    echo '
<p>'.tr('Nessun intervento su questo impianto').'...</p>';
}

echo '
    </div>
</div>';
