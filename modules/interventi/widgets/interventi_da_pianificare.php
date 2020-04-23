<?php

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../../core.php';

// Interventi da pianificare
$interventi_da_pianificare = Intervento::doesntHave('sessioni')
    ->orderByRaw('IF(data_scadenza IS NULL, data_richiesta, data_scadenza)')
    ->get();
$raggruppamenti = $interventi_da_pianificare->groupBy(function ($item, $key) {
    $data = $item->data_scadenza ?: $item->data_richiesta;

    return ucfirst($data->formatLocalized('%B %Y'));
});

$counter = 0;
foreach ($raggruppamenti as $mese => $raggruppamento) {
    ++$counter;

    // Se cambia il mese ricreo l'intestazione della tabella
    if ($counter == 1) {
        $attr = '';
        $class = 'fa-minus-circle';
    } else {
        $attr = 'style="display:none;"';
        $class = 'fa-plus-circle';
    }

    echo "
<h4>
    <a class='clickable' onclick=\"if( $('#interventi_pianificare_".$counter."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#interventi_pianificare_".$counter."').slideToggle();\">
        <i class='fa ".$class."'></i> ".$mese.'
    </a>
</h4>';

    echo '
<div id="interventi_pianificare_'.$counter.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
				<th width="70">'.tr('Codice').'</th>
                <th width="120">'.tr('Cliente').'</th>
                <th width="70"><small>'.tr('Data richiesta').'</small></th>
                <th width="70"><small>'.tr('Data scadenza').'</small></th>
                <th width="200">'.tr('Tipo intervento').'</th>
                <th>'.tr('Descrizione').'</th>
                <th width="100">'.tr('Sede').'</th>
            </tr>
        </thead>

        <tbody>';

    // Elenco interventi da pianificare
    foreach ($raggruppamento as $r) {
        echo '
            <tr id="int_'.$r['id'].'">
				<td><a target="_blank" >'.Modules::link(Modules::get('Interventi')['id'], $r['id'], $r['codice']).'</a></td>
                <td>'.$r['ragione_sociale'].'</td>
                <td>'.Translator::dateToLocale($r['data_richiesta']).'</td>
                <td>'.((empty($r['data_scadenza'])) ? ' - ' : Translator::dateToLocale($r['data_scadenza'])).'</td>
                <td>'.$r['tipointervento'].'</td>
                <td>'.nl2br($r['richiesta']).'</td>
				';

        echo '
                <td>';
        // Sede
        if ($r['idsede'] == '-1') {
            echo '- '.('Nessuna').' -';
        } elseif (empty($r['idsede'])) {
            echo tr('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($r['idsede']));

            echo $rsp2[0]['descrizione'];
        }
        echo '
                </td>';

        echo '
            </tr>';
    }

    echo '
        </tbody>
    </table>
</div>';
}

if ($raggruppamenti->isEmpty()) {
    echo '
<p>'.tr('Non ci sono interventi da pianificare').'.</p>';
}
