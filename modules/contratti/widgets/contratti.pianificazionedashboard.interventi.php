<?php

include_once __DIR__.'/../../../core.php';

$mesi = [
    _('Gennaio'),
    _('Febbraio'),
    _('Marzo'),
    _('Aprile'),
    _('Maggio'),
    _('Giugno'),
    _('Luglio'),
    _('Agosto'),
    _('Settembre'),
    _('Ottobre'),
    _('Novembre'),
    _('Dicembre'),
];

// Righe inserite
$qp = "SELECT *, DATE_FORMAT( data_richiesta, '%m-%Y') AS mese, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_righe_contratti.idtipointervento) AS tipointervento, (SELECT idanagrafica FROM co_contratti WHERE id=idcontratto) AS idcliente FROM co_righe_contratti WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE pianificabile = 1) ) AND idintervento IS NULL ORDER BY DATE_FORMAT( data_richiesta, '%m-%Y') ASC";
$rsp = $dbo->fetchArray($qp);

if (!empty($rsp)) {
    // Elenco interventi da pianificare
    foreach ($rsp as $i => $r) {
        // Se cambia il mese ricreo l'intestazione della tabella
        if (!isset($rsp[$i - 1]) || $r['mese'] != $rsp[$i - 1]['mese']) {
            if ($i == 0) {
                $attr = '';
                $class = 'fa-minus-circle';
            } else {
                $attr = 'style="display:none;"';
                $class = 'fa-plus-circle';
            }

            echo "
<h4>
    <a class='clickable' onclick=\"if( $('#t1_".$i."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#t1_".$i."').slideToggle();\">
        <i class='fa ".$class."'></i> ".$mesi[intval(date('m', strtotime($r['data_richiesta']))) - 1].' '.date('Y', strtotime($r['data_richiesta'])).'
    </a>
</h4>';

            echo '
<div id="t1_'.$i.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="70">'._('Entro il').'</th>
                <th width="200">'._('Tipo intervento').'</th>
                <th width="300">'._('Descrizione').'</th>
                <th width="200">'._('Intervento collegato').'</th>
                <th width="100">'._('Sede').'</th>
                <th width="18"></th>
            </tr>
        </thead>

        <tbody>';
        }

        echo '
            <tr id="int_'.$r['id'].'">
                <td>'.Translator::dateToLocale($r['data_richiesta']).'</td>
                <td>'.$r['tipointervento'].'</td>
                <td>'.nl2br($r['richiesta']).'</td>
                <td>';

        // Intervento svolto
        if (!empty($r['idintervento'])) {
            $rsp2 = $dbo->fetchArray('SELECT id, codice, data FROM in_interventi WHERE id='.prepare($r['idintervento']));

            echo Modules::link('Interventi', $rsp2[0]['id'], str_replace(['_NUM_', '_DATE_'], [$rsp2[0]['codice'],Translator::dateToLocale($rsp2[0]['data'])], _('Intervento _NUM_ del _DATE_')));
        } else {
            echo '- '.('Nessuno').' -';
        }
        echo '</td>';

        echo '
                <td>';
        // Sede
        if ($r['idsede'] == '-1') {
            echo '- '.('Nessuna').' -';
        } elseif (empty($r['idsede'])) {
            echo _('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($r['idsede']));

            echo $rsp2[0]['descrizione'];
        }
        echo '
                </td>';

        // Pulsanti
        echo '
                <td>';
        if (empty($r['idintervento'])) {
            echo "
                    <a class=\"btn btn-primary\" title=\"Pianifica ora!\" onclick=\"launch_modal( '', '".$rootdir.'/add.php?id_module='.Modules::getModule('Interventi')['id'].'&ref=dashboard&idcontratto='.urlencode($r['idcontratto']).'&idcontratto_riga='.$r['id']."', 1 );\">
                        <i class='fa fa-calendar'></i>
                    </a>";
        }
        echo '
                </td>
            </tr>';

        if (!isset($rsp[$i + 1]) || $r['mese'] != $rsp[$i + 1]['mese']) {
            echo '
        </tbody>
    </table>
</div>';
        }
    }
} else {
    echo '
<p>'._('Non ci sono interventi da pianificare').'.</p>';
}
