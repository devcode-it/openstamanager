<?php

include_once __DIR__.'/../../core.php';

// Elenco articoli caricati sull'automezzo
$rs2 = $dbo->fetchArray('SELECT 
        `mg_movimenti`.`idsede` AS id, 
        `mg_articoli`.`codice` AS codice, 
        `idarticolo`, 
        SUM(`mg_movimenti`.`qta`) AS qta_automezzo, 
        `mg_articoli`.`qta` AS qta_magazzino, 
        `mg_articoli_lang`.`name`, 
        `mg_articoli`.`prezzo_vendita`, 
        (SELECT `percentuale` FROM `co_iva` LEFT JOIN `co_iva_lang` ON ( `co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = "'.prepare(setting('Lingua')).'") WHERE `co_iva`.`id`=`mg_articoli`.`idiva_vendita`) AS prciva_vendita 
    FROM 
        `mg_movimenti` 
        INNER JOIN `mg_articoli` ON `mg_movimenti`.`idarticolo`=`mg_articoli`.`id` 
        LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(setting('Lingua')).')
    WHERE 
        `mg_movimenti`.`idsede`='.prepare($id_record).' 
    GROUP BY 
        `idarticolo`
    HAVING 
        `qta_automezzo`>0 
    ORDER BY 
        `mg_articoli_lang`.`name`');

if (!empty($rs2)) {
    echo '
<div style="max-height: 300px; overflow: auto;">
    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th>'.tr('Articolo').'</th>
            <th width="25%">'.tr('Q.tà').'</th>
            <th width="25%">'.tr('Prezzo di vendita').'</th>
            <th width="10%"></th>
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
                <small>'.tr('Netto').': '.Translator::numberToLocale($netto).' &euro;</small><br/>
                <small>'.tr('Iva').': '.Translator::numberToLocale($iva).' &euro;</small><br/>
            </td>';

        // Pulsanti
        echo '
            <td class="text-center">
                <a class="btn btn-warning btn-xs" data-href="'.$structure->fileurl('add_articolo.php').'?idautomezzo='.$id_record.'&idarticolo='.$r['idarticolo'].'" data-toggle="modal" data-title="'.tr('Aggiungi articoli').'">
                    <i class="fa fa-edit"></i>
                </a>
                <a class="btn btn-danger btn-xs ask" data-backto="record-edit" data-op="moverow" data-idautomezzotecnico="'.$r['id'].'", data-idarticolo="'.$r['idarticolo'].'" data-msg="'.tr("Rimuovere articolo dell'automezzo?").'">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';

        $tot_articoli += $r['qta_automezzo'];
    }

    echo '
    </table>
</div>';
} else {
    echo '
<p>'.tr('Nessun articolo presente').'...</p>';
}
