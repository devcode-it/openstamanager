<?php

include_once __DIR__.'/../../../core.php';

// TODO: aggiornare con la funzione months()
$mesi = [
    tr('Gennaio'),
    tr('Febbraio'),
    tr('Marzo'),
    tr('Aprile'),
    tr('Maggio'),
    tr('Giugno'),
    tr('Luglio'),
    tr('Agosto'),
    tr('Settembre'),
    tr('Ottobre'),
    tr('Novembre'),
    tr('Dicembre'),
];

// Righe inserite
//idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) ) AND
$qp = "SELECT *,
    (SELECT SUM(subtotale) FROM co_righe_contratti WHERE idcontratto=co_ordiniservizio_pianificazionefatture.idcontratto) AS budget_contratto,
    DATE_FORMAT(data_scadenza, '%m-%Y') AS mese,
    (SELECT idanagrafica FROM co_contratti WHERE id=idcontratto) AS idcliente,
	(SELECT nome FROM co_contratti WHERE id=idcontratto) AS nome,
    (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_contratti WHERE id=idcontratto)) AS ragione_sociale,
    (SELECT descrizione FROM an_zone WHERE id=co_ordiniservizio_pianificazionefatture.idzona) AS zona
FROM co_ordiniservizio_pianificazionefatture WHERE  co_ordiniservizio_pianificazionefatture.iddocumento=0 ORDER BY data_scadenza ASC, idcliente ASC";
$rsp = $dbo->fetchArray($qp);

if (!empty($rsp)) {
    // Lettura numero di rate e totale già fatturato
    $rs2 = $dbo->fetchArray('SELECT * FROM co_ordiniservizio_pianificazionefatture');

    for ($j = 0; $j < sizeof($rs2); ++$j) {
        // Leggo quante rate sono pianificate per dividere l'importo delle sedi in modo corretto
        ++$n_rate[$rs2[$j]['idcontratto']][$rs2[$j]['idzona']];

        // Leggo il totale già fatturato per questa zona per toglierlo dalla divisione (totale/n_rate)
        $rs3 = $dbo->fetchArray('SELECT SUM(subtotale-sconto) AS totale FROM co_righe_documenti WHERE iddocumento='.prepare($rs2[$j]['iddocumento']));
        $gia_fatturato[$rs2[$j]['idcontratto']][$rs2[$j]['idzona']] += $rs3[0]['totale'];
    }

    // Elenco fatture da emettere
    foreach ($rsp as $i => $r) {
        ++$n_rata[$r['idzona']][$r['idcontratto']];

        // Se cambia il mese ricreo l'intestazione della tabella
        if (!isset($rsp[$i - 1]) || $r['mese'] != $rsp[$i - 1]['mese']) {
            echo "
<div class='title_settings'>
    <a class='clickable' onclick=\"$('#f_".$i."').slideToggle();\"></a>
</div>";
            if ($i == 0) {
                $attr = '';
                $class = 'fa-minus-circle';
            } else {
                $attr = 'style="display:none;"';
                $class = 'fa-plus-circle';
            }

            echo "
<h4>
    <a class='clickable' onclick=\"if( $('#f_".$i."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#f_".$i."').slideToggle();\">
        <i class='fa ".$class."'></i> ".$mesi[intval(date('m', strtotime($r['data_scadenza']))) - 1].' '.date('Y', strtotime($r['data_scadenza'])).'
    </a>
</h4>';

            echo '
<div id="f_'.$i.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="25%">'.tr('Entro il').'</th>
                <th width="35%">'.tr('Ragione sociale').'</th>
                <th width="10%">'.tr('Zona').'</th>
                <th width="20%">'.tr('Impianto').'</th>
                <th width="10%"></th>
            </tr>
        </thead>

        <tbody>';
        }

        // Lettura numero di sedi in cui si sono pianificati ordini di servizio per la zona corrente
        if (!empty($r['idzona'])) {
            $n_sedi_pianificate = $dbo->fetchNum('SELECT DISTINCT(idsede) FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).') AND idsede IN(SELECT id FROM an_sedi WHERE idzona='.prepare($r['idzona']).')');

            // Verifico se ci sono impianti in questa zona legati alla sede legale
            $n_sedi_pianificate += $dbo->fetchNum('SELECT DISTINCT(idsede) FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).') AND idsede=(SELECT idsede FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_contratti WHERE id='.prepare($id_record).') AND idzona='.prepare($r['idzona']).') AND idsede=0');
        }
        // Fix nel caso non siano previste sedi pianificate (l'eventuale 0 portava a problemi nel calcolo dell'importo)
        $n_sedi_pianificate = ($n_sedi_pianificate < 1) ? 1 : $n_sedi_pianificate;

        /*
            Importo
        */
        // $importo = ($r['budget_contratto'] * $n_sedi_pianificate / $n_rate[ $r['idcontratto'] ][ $r['idzona'] ]) - ($gia_fatturato[ $r['idcontratto'] ][ $r['idzona'] ] * $n_sedi_pianificate / sizeof($gia_fatturato[ $r['idcontratto'] ][ $r['idzona'] ]) );
        $importo = ($r['budget_contratto'] * $n_sedi_pianificate / $n_rate[$r['idcontratto']][$r['idzona']]);

        // Sede
        if ($r['zona'] == '') {
            $zona = tr('Altro');
        } else {
            $zona = $r['zona'];
        }

        if ($n_sedi_pianificate == 1) {
            $n_sedi = tr('1 sede');
        } else {
            $n_sedi = tr('_NUM_ sedi', [
                '_NUM_' => $n_sedi_pianificate,
            ]);
        }

        // Visualizzo solo le rate non pagate
        if ($r['iddocumento'] == 0) {
            echo "
            <tr id='fat_".$r['id']."'>
                <td>".Translator::dateToLocale($r['data_scadenza'])."<br><a href='".$rootdir.'/editor.php?id_module='.Modules::get('Contratti')['id'].'&id_record='.$r['idcontratto']."'><small>rif. ".$r['nome']." </small></a></td>
                <td>
                    <a href='".$rootdir.'/editor.php?id_module='.Modules::get('Anagrafiche')['id'].'&id_record='.$r['idcliente']."'>".nl2br($r['ragione_sociale']).'</a>
                </td>
                <td>'.$zona.' ('.$n_sedi.')</td>
                <td>
                    '.moneyFormat($importo).'<br>
                    <small><small>'.moneyFormat($r['budget_contratto']).' x '.$n_sedi_pianificate.' sedi / '.$n_rate[$r['idcontratto']][$r['idzona']].' rate</small></small>
                </td>';

            // Pulsanti
            echo '
                <td>';
            if (empty($r['idintervento'])) {
                echo '<button type="button" class="btn btn-primary btn-sm" onclick="launch_modal( \'Crea fattura\', \''.$rootdir.'/modules/contratti/plugins/addfattura.php?idcontratto='.$r['idcontratto'].'&idpianificazione='.$r['id'].'&importo='.$importo.'&n_rata='.$n_rata[$r['idzona']][$r['idcontratto']].'\');">
					<i class="fa fa-euro"></i> Crea fattura
				</button>';
            }
            echo '
                </td>
            </tr>';
        }

        if (!isset($rsp[$i + 1]) || $r['mese'] != $rsp[$i + 1]['mese']) {
            echo '
        </tbody>
    </table>
</div>';
        }
    }
} else {
    echo '
<p>'.tr('Non ci sono fatture da emettere').'.</p>';
}
