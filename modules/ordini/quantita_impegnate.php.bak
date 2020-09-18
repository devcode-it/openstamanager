<?php

use Modules\Ordini\Ordine;

include_once __DIR__.'/../../core.php';

$ordine = Ordine::find($id_record);
$articoli = $ordine->articoli->groupBy('idarticolo');

if ($articoli->isEmpty()) {
    echo '
<p>'.tr('Il documento non contiene articoli').'.</p>';

    return;
}

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
			<th>'.tr('Articolo').'</th>
            <th class="text-center tip" width="150" title="'.tr('Quantità presente nel documento').'">'.tr('Q.tà').'</th>
            <th class="text-center tip" width="150" title="'.tr('Quantità presente nel magazzino del gestionale').'">'.tr('Q.tà magazzino').'</th>
            <th class="text-center tip" width="150" title="'.tr('Quantità impegnata in altri Ordini del gestionale').'">'.tr('Q.tà impegnata').'</th>
		</tr>
	</thead>

    <tbody>';

foreach ($articoli as $elenco) {
    $qta = $elenco->sum('qta');
    $articolo = $elenco->first()->articolo;

    $codice = $articolo ? $articolo->codice : tr('Articolo eliminato');
    $descrizione = $articolo ? $articolo->descrizione : $elenco->first()->descrizione;

    $qta_impegnata = $database->fetchOne("SELECT SUM(qta) as qta
        FROM or_righe_ordini
            JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine
        WHERE or_ordini.id != '.prepare($ordine->id).'
              AND idstatoordine = (SELECT id FROM or_statiordine WHERE descrizione = 'Bozza')
              AND idtipoordine IN (SELECT id FROM or_tipiordine WHERE dir = 'entrata')
        GROUP BY idarticolo")['qta'];
    $qta_impegnata = floatval($qta_impegnata);

    $class = $qta_impegnata + $qta > $articolo->qta ? 'danger' : 'success';
    $descrizione_riga = $codice.' - '.$descrizione;
    $text = $articolo ? Modules::link('Articoli', $articolo->id, $descrizione_riga) : $descrizione_riga;

    echo '
        <tr class="'.$class.'">
            <td>'.$text.'</td>
            <td class="text-center">'.numberFormat($qta, 'qta').'</td>
            <td class="text-center">'.numberFormat($articolo->qta, 'qta').'</td>
            <td class="text-center">'.numberFormat($qta_impegnata, 'qta').'</td>
        </tr>';
}
echo '
    </tbody>
</table>';
