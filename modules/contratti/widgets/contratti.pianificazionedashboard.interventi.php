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
$qp = "SELECT *, DATE_FORMAT( data_richiesta, '%m-%Y') AS mese, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_promemoria.idtipointervento) AS tipointervento, (SELECT idanagrafica FROM co_contratti WHERE id=idcontratto) AS idcliente, (SELECT ragione_sociale FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_contratti.id=idcontratto) AS ragione_sociale, (SELECT CONCAT('Contratto ', numero, ' del ', DATE_FORMAT(data_bozza, '%d/%m/%Y'), ' - ', nome, ' [', (SELECT `descrizione` FROM `co_staticontratti` WHERE `co_staticontratti`.`id` = `idstato`) , ']') FROM co_contratti WHERE id = co_promemoria.idcontratto) contratto, (SELECT id FROM co_contratti WHERE id = co_promemoria.idcontratto) idcontratto FROM co_promemoria WHERE idcontratto IN ( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) ) AND idintervento IS NULL ORDER BY DATE_FORMAT( data_richiesta, '%Y-%m') ASC, ragione_sociale ASC";
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
    <table class="table table-hover table-striped datatables">
        <thead>
            <tr>
                <th width="120">'.tr('Cliente').'</th>
				 <th width="250">'.tr('Contratto').'</th>
                <th width="90">'.tr('Entro').'</th>
                <th width="150">'.tr('Tipo attività').'</th>
                <th width="300">'.tr('Descrizione').'</th>
                <th width="100">'.tr('Sede').'</th>
                <th width="18"></th>
            </tr>
        </thead>

        <tbody>';
        }

        echo '
            <tr id="int_'.$r['id'].'">
				<td><a target="_blank" >'.Modules::link(Modules::get('Anagrafiche')['id'], $r['idcliente'], $r['ragione_sociale']).'</a></td>
				<td><a target="_blank" >'.Modules::link(Modules::get('Contratti')['id'], $r['idcontratto'], $r['contratto']).'</a></td>
                <td>'.Translator::dateToLocale($r['data_richiesta']).'</td>
                <td>'.$r['tipointervento'].'</td>
                <td>'.nl2br($r['richiesta']).'</td>';

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

        // Pulsanti
        echo '
                <td>';
        if (empty($r['idintervento'])) {
            echo "
                    <a class=\"btn btn-primary\" title=\"Pianifica ora!\" onclick=\"launch_modal( '".tr('Pianifica intervento')."', '".$rootdir.'/add.php?id_module='.Modules::get('Interventi')['id'].'&ref=dashboard&idcontratto='.urlencode($r['idcontratto']).'&idcontratto_riga='.$r['id']."');\">
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
<p>'.tr('Non ci sono interventi da pianificare').'.</p>';
}

?>

<script>
$(document).ready(function() {
	 $('.datatables').DataTable({
		 	"oLanguage": { "sUrl": "<?php echo $rootdir; ?>/assets/dist/js/i18n/datatables/<?php echo $lang; ?>.min.json" },
		 	 "paging": false,
			 "info":     false
	 });
} );
</script>
