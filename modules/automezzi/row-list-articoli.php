<?php

include_once __DIR__.'/../../core.php';

// Elenco articoli caricati sull'automezzo
$query = 'SELECT mg_articoli_automezzi.id, mg_articoli.codice AS codice, idarticolo, mg_articoli_automezzi.qta AS qta_automezzo, mg_articoli.qta AS qta_magazzino, mg_articoli.descrizione, mg_articoli.prezzo_vendita, (SELECT percentuale FROM co_iva WHERE id=mg_articoli.idiva_vendita) AS prciva_vendita FROM mg_articoli_automezzi INNER JOIN mg_articoli ON mg_articoli_automezzi.idarticolo=mg_articoli.id WHERE mg_articoli_automezzi.idautomezzo='.prepare($id_record).' AND mg_articoli_automezzi.qta > 0';
$rs2 = $dbo->fetchArray($query);

if (!empty($rs2)) {
    echo '
<table class="table table-striped table-hover table-condensed">
    <tr>
        <th>'.tr('Articolo').'</th>
        <th width="25%">'.tr('Q.tà').'</th>
        <th width="25%">'.tr('Prezzo di vendita').'</th>
        <th width="5%"></th>
    </tr>';

    foreach ($rs2 as $r) {
        echo '
    <tr>';
        // Articolo
        echo '
        <td class="text-left">
            '.Modules::link('Articoli', $r['idarticolo'], $r['codice'].' - '.$r['descrizione']).'
        </td>';

        // Quantità
        echo '
        <td class="first_cell center">
            <span><big>'.Translator::numberToLocale($r['qta_automezzo']).'</big></span><br/>
            <small>'.tr('Q.tà magazzino').': '.Translator::numberToLocale($r['qta_magazzino']).'</small><br/>
        </td>';

        // Prezzo di vendita
        $netto = $r['prezzo_vendita'];
        $iva = $r['prezzo_vendita'] / 100 * $r['prciva_vendita'];
        echo '
        <td class="table_cell center">
            <span>'.Translator::numberToLocale($netto + $iva).' &euro;</span><br/>
            <small>netto: '.Translator::numberToLocale($netto).' &euro;</small><br/>
            <small>iva: '.Translator::numberToLocale($iva).' &euro;</small><br/>
        </td>';

        // Pulsanti
        echo '
        <td class="text-center">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="moverow" data-idautomezzotecnico="'.$r['id'].'" data-msg="'.tr("Rimuovere articolo dell'automezzo?").'">
                <i class="fa fa-trash"></i>
            </a>
        </td>
    </tr>';

        $tot_articoli += $r['qta_automezzo'];
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Nessun articolo presente').'...</p>';
}
