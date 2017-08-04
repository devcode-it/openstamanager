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
$qp = "SELECT *, DATE_FORMAT( data_scadenza, '%m-%Y') AS mese, (SELECT idanagrafica FROM co_contratti WHERE id=idcontratto) AS idcliente, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_contratti WHERE id=idcontratto)) AS ragione_sociale, (SELECT matricola FROM my_impianti WHERE id=co_ordiniservizio.idimpianto) AS impianto, (SELECT nome FROM my_impianti WHERE id=co_ordiniservizio.idimpianto) AS impianto, (SELECT idsede FROM my_impianti WHERE id=co_ordiniservizio.idimpianto) AS idsede FROM co_ordiniservizio WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE pianificabile = 1) ) AND idintervento IS NULL ORDER BY DATE_FORMAT( data_scadenza, '%m-%Y') ASC, idcliente ASC";
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
        <i class='fa ".$class."'></i> ".$mesi[intval(date('m', strtotime($r['data_scadenza']))) - 1].' '.date('Y', strtotime($r['data_scadenza'])).'
    </a>
</h4>';

            echo '
<div id="t1_'.$i.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="10%">'._('Entro il').'</th>
                <th width="45%">'._('Ragione sociale').'</th>
                <th width="20%">'._('Sede').'</th>
                <th width="20%">'._('Impianto').'</th>
                <th width="5%"></th>
            </tr>
        </thead>

        <tbody>';
        }

        echo '
            <tr id="int_'.$r['id'].'">
                <td>'.Translator::dateToLocale($r['data_scadenza']).'</td>
                <td>
                    '.Modules::link('Anagrafiche', $r['idcliente'], $r['ragione_sociale']).'
                </td>';

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

        echo
                '<td>
                    '.Modules::link('MyImpianti', $r['idimpianto'], $r['matricola'].' - '.$r['impianto']).'
                </td>';

        // Pulsanti
        echo '
                <td>';
        if (empty($r['idintervento'])) {
            echo "
                    <a class=\"btn btn-primary\" title=\"Pianifica ora!\" onclick=\"launch_modal( 'Pianifica intervento', '".$rootdir.'/add.php?id_module='.Modules::getModule('Interventi')['id'].'&ref=dashboard&idcontratto='.urlencode($r['idcontratto']).'&idordineservizio='.$r['id']."', 1 );\">
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
<p>'._('Non ci sono ordini di servizio da pianificare').'.</p>';
}
