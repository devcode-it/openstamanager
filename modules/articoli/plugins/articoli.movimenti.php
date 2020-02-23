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
$qta_totale = $dbo->fetchOne('SELECT SUM(qta) AS qta FROM mg_movimenti WHERE idarticolo='.prepare($id_record))['qta'];
$qta_totale_attuale = $dbo->fetchOne('SELECT SUM(qta) AS qta FROM mg_movimenti WHERE idarticolo='.prepare($id_record).' AND data <= CURDATE()')['qta'];

echo '
<p>'.tr('Quantità calcolata dai movimenti').': <b>'.Translator::numberToLocale($qta_totale, 'qta').' '.$record['um'].'</b> <span class="tip" title="'.tr('Quantità calcolata da tutti i movimenti registrati').'." ><i class="fa fa-question-circle-o"></i></span></p>';

echo '
<p>'.tr('Quantità calcolata attuale').': <b>'.Translator::numberToLocale($qta_totale_attuale, 'qta').' '.$record['um'].'</b> <span class="tip" title="'.tr('Quantità calcolata secondo i movimenti registrati con data oggi o date trascorse').'." ><i class="fa fa-question-circle-o"></i></span></p>';

// Individuazione movimenti
$movimenti = $articolo->movimenti()
    ->selectRaw('*, sum(qta) as qta_movimenti')
    ->groupBy('reference_type', 'reference_id')
    ->orderBy('data', 'id');
if (empty($_GET['movimentazione_completa'])) {
    //$movimenti->limit(20);
}

// Raggruppamento per documento
$movimenti = $movimenti->get();
if (!empty($movimenti)) {
    if (empty($_GET['movimentazione_completa'])) {
        echo '
        <p><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&movimentazione_completa=1#tab_'.$id_plugin.'">[ '.tr('Mostra tutti i movimenti').' ]</a></p>';
    } else {
        echo '
        <p><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&movimentazione_completa=0#tab_'.$id_plugin.'">[ '.tr('Mostra solo gli ultimi 20 movimenti').' ]</a></p>';
    }

    echo '
        <table class="table table-striped table-condensed table-bordered">
            <tr>
                <th class="text-center">'.tr('Q.tà').'</th>
                <th class="text-center">'.tr('Q.tà progressiva').'</th>
                <th class="text-center">'.tr('Operazione').'</th>
                <th>'.tr('Documento').'</th>
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
        $movimento['progressivo_iniziale'] = $movimento['progressivo_finale'] - $movimento['qta'];

        $movimenti[$i]['progressivo_iniziale'] = $movimento['progressivo_iniziale'];
        $movimenti[$i]['progressivo_finale'] = $movimento['progressivo_finale'];

        // Quantità
        echo '
            <tr>
                <td class="text-center">
                    '.numberFormat($movimento['qta_movimenti'], 'qta').' '.$record['um'].'
                </td>

                <td class="text-center">
                    '.numberFormat($movimento['progressivo_iniziale'], 'qta').' '.$record['um'].'
                    <i class="fa fa-arrow-circle-right"></i>
                    '.numberFormat($movimento['progressivo_finale'], 'qta').' '.$record['um'].'
                </td>

                <td class="text-center">
                    '.$movimento->descrizione.'
                </td>

                <td>
                    '.($movimento->hasDocument() ? reference($movimento->getDocument()) : tr('Nessun documento collegato')).'
                </td>';

        // Data
        echo '
                <td class="text-center" >'.Translator::dateToLocale($movimento['data']).' <span  class="tip" title="'.tr('Data del movimento: _DATE_', [
               '_DATE_' => Translator::timestampToLocale($movimento['created_at']),
            ]).'"><i class="fa fa-question-circle-o"></i></span> </td>';

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
