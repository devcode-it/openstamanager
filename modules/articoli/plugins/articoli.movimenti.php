<?php

include_once __DIR__.'/../../../core.php';

// Movimentazione degli articoli
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Movimenti').'</h3>
    </div>
    <div class="box-body">';

// Calcolo la quantità dai movimenti in magazzino
$rst = $dbo->fetchArray('SELECT COUNT(mg_movimenti.id) AS `row`, SUM(qta) AS qta_totale, (SELECT SUM(qta) FROM mg_movimenti  WHERE idarticolo='.prepare($id_record).' AND (idintervento IS NULL) AND data <= CURDATE()) AS qta_totale_attuale FROM mg_movimenti WHERE idarticolo='.prepare($id_record).' AND (idintervento IS NULL)');
$qta_totale = $rst[0]['qta_totale'];
$qta_totale_attuale = $rst[0]['qta_totale_attuale'];

if ($rst[0]['row'] > 0) {
    echo '
	<p>'.tr('Quantità calcolata dai movimenti').': <b>'.Translator::numberToLocale($qta_totale, 'qta').' '.$record['um'].'</b> <span  class=\'tip\' title=\''.tr('Quantità calcolata da tutti i movimenti registrati').'.\' ><i class="fa fa-question-circle-o"></i></span></p>';

    echo '
	<p>'.tr('Quantità calcolata attuale').': <b>'.Translator::numberToLocale($qta_totale_attuale, 'qta').' '.$record['um'].'</b> <span  class=\'tip\' title=\''.tr('Quantità calcolata secondo i movimenti registrati con data oggi o date trascorse').'.\' ><i class="fa fa-question-circle-o"></i></span></p>';
}

// Elenco movimenti magazzino
$query = 'SELECT * FROM mg_movimenti WHERE idarticolo='.prepare($id_record).' ORDER BY created_at DESC, id DESC';
if (empty($_GET['show_all1'])) {
    $query .= ' LIMIT 0, 20';
}

$movimenti = $dbo->fetchArray($query);
if (!empty($movimenti)) {
    if (empty($_GET['show_all1'])) {
        echo '
        <p><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&show_all1=1#tab_'.$id_plugin.'">[ '.tr('Mostra tutti i movimenti').' ]</a></p>';
    } else {
        echo '
        <p><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&show_all1=0#tab_'.$id_plugin.'">[ '.tr('Mostra solo gli ultimi 20 movimenti').' ]</a></p>';
    }

    echo '
        <table class="table table-striped table-condensed table-bordered">
            <tr>
                <th class="text-center">'.tr('Q.tà').'</th>
                <th class="text-center">'.tr('Q.tà progressiva').'</th>
                <th>'.tr('Causale').'</th>
                <th class="text-center">'.tr('Data').'</th>
                <th class="text-center" width="7%">#</th>
            </tr>';

    foreach ($movimenti as $i => $movimento) {
        // Quantità progressiva
        if ($i == 0) {
            $movimento['progressivo_finale'] = $articolo->qta;
        } else {
            $movimento['progressivo_finale'] = $movimenti[$i - 1]['progressivo_iniziale'];
        }
        $movimento['progressivo_iniziale'] = $movimento['progressivo_finale'] - intval($movimento['qta']);

        $movimenti[$i]['progressivo_iniziale'] = $movimento['progressivo_iniziale'];
        $movimenti[$i]['progressivo_finale'] = $movimento['progressivo_finale'];

        // Quantità
        echo '
            <tr>
                <td class="text-center">
                    '.numberFormat($movimento['qta'], 'qta').' '.$record['um'].'
                </td>
                
                <td class="text-center">
                    '.numberFormat($movimento['progressivo_iniziale'], 'qta').' '.$record['um'].'
                    <i class="fa fa-arrow-circle-right"></i>
                    '.numberFormat($movimento['progressivo_finale'], 'qta').' '.$record['um'].'
                </td>';

        // Causale
        $dir = ($movimento['qta'] < 0) ? 'vendita' : 'acquisto';

        if (!empty($movimento['iddocumento'])) {
            $dir = $dbo->fetchArray('SELECT dir FROM co_tipidocumento WHERE id = (SELECT idtipodocumento FROM co_documenti WHERE id = '.prepare($movimento['iddocumento']).')')[0]['dir'] == 'entrata' ? 'vendita' : 'acquisto';
        }

        echo '
                <td>'.$movimento['movimento'].'
				'.((!empty($movimento['idintervento'])) ? Modules::link('Interventi', $movimento['idintervento']) : '').'
				'.((!empty($movimento['idddt'])) ? (Modules::link('DDt di '.$dir, $movimento['idddt'], null, null, (intval($database->fetchOne('SELECT * FROM `dt_ddt` WHERE `id` ='.prepare($movimento['idddt'])))) ? '' : 'class="disabled"')) : '').'
				'.((!empty($movimento['iddocumento'])) ? (Modules::link('Fatture di '.$dir, $movimento['iddocumento'], null, null, (intval($database->fetchOne('SELECT * FROM `co_documenti` WHERE `id` ='.prepare($movimento['iddocumento'])))) ? '' : 'class="disabled"')) : '').'
				</td>';

        // Data
        echo '
                <td class="text-center" >'.Translator::dateToLocale($movimento['data']).' <span  class=\'tip\' title=\''.tr('Data del movimento: ').Translator::timestampToLocale($movimento['created_at']).'\' ><i class="fa fa-question-circle-o"></i></span> </td>';

        // Operazioni
        echo '
                <td class="text-center">';

        if (Auth::admin() && $movimento['manuale'] == '1') {
            echo '
                    <a class="btn btn-danger btn-sm ask" data-backto="record-edit" data-op="delmovimento" data-idmovimento="'.$movimento['id'].'">
                        <i class="fa fa-trash"></i>
                    </a>';
        }

        echo '
                </td>
            </tr>';
    }

    echo '
        </table>';
} else {
    echo '
	<div class="alert alert-info">
		<i class="fa fa-info-circle"></i>
		'.tr('Questo articolo non è ancora stato movimentato').'.
	</div>';
}

echo '
    </div>
</div>';
