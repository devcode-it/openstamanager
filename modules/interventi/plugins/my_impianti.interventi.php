<?php

include_once __DIR__.'/../../../core.php';

include_once Modules::filepath('Interventi', 'modutil.php');

// INTERVENTI ESEGUITI SU QUESTO IMPIANTO
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Interventi eseguiti su questo impianto').'</h3>
    </div>
    <div class="box-body">';

$results = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, descrizione, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=my_impianti_interventi.idintervento) AS data FROM my_impianti_interventi INNER JOIN in_interventi ON my_impianti_interventi.idintervento=in_interventi.id WHERE idimpianto='.prepare($id_record).' ORDER BY data DESC');
$totale_interventi = 0;

if (!empty($results)) {
    echo '
        <table class="table table-striped table-hover">
            <tr>
                <th width="350">'.tr('Intervento').'</th>
                <th>'.tr('Descrizione').'</th>
                <th width="150" class="text-right">'.tr('Costo totale').'</th>
            </tr>';

    foreach ($results as $result) {
        $costi_intervento = get_costi_intervento($result['id']);
        $totale_interventi += $costi_intervento['totale'];
        echo '
            <tr>
                <td>
                    '.Modules::link('Interventi', $result['id'], tr('Intervento num. _NUM_ del _DATE_', [
                        '_NUM_' => $result['codice'],
                        '_DATE_' => Translator::dateToLocale($result['data']),
                    ])).'
                </td>
                <td>'.nl2br($result['descrizione']).'</td>
                <td class="text-right">'.Translator::numberToLocale($costi_intervento['totale']).' &euro;</td>
            </tr>';
    }

    echo '  <tr>';
    echo '      <td colspan="2" class="text-right">';
    echo '          <b>Totale:</b>';
    echo '      </td>';
    echo '      <td class="text-right">';
    echo            '<b>'.Translator::numberToLocale($totale_interventi).' &euro;</b>';
    echo '      </td>';
    echo '  </tr>';

    echo '
        </table>';
} else {
    echo '
<p>'.tr('Nessun intervento su questo impianto').'...</p>';
}

echo '
    </div>
</div>';
