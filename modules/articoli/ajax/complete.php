<?php

include_once __DIR__.'/../../../core.php';

$idarticolo = get('idarticolo');

switch ($resource) {
    // Legge gli ultimi prezzi di vendita di un determinato cliente e un determinato articolo e li visualizza per suggerire il prezzo di vendita
    case 'getprezzi':
        $idanagrafica = get('idanagrafica');
        $ids = ['""'];

        echo '<small>';
        if (!empty($idarticolo)) {
            // Ultime 5 vendite al cliente
            $fatture = $dbo->fetchArray('SELECT iddocumento, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_fattura, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_fattura, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_fattura FROM co_righe_documenti WHERE idarticolo="'.$idarticolo."\" AND iddocumento IN(SELECT id FROM co_documenti WHERE id_tipo_documento IN(SELECT id FROM co_tipidocumento WHERE dir='entrata') AND idanagrafica=\"".$idanagrafica.'") LIMIT 0,5');

            if (sizeof($fatture) > 0) {
                echo "<br/><table cellspacing='0' class='table-striped' >\n";
                echo "<tr><th width='150'>Documento</th>\n";
                echo "<th width='50'>Data</th>\n";
                echo "<th width='80' class='text-right' >Totale</th></tr>\n";

                for ($i = 0; $i < sizeof($fatture); ++$i) {
                    ($fatture[$i]['n2_fattura'] != '') ? $n_fattura = $fatture[$i]['n2_fattura'] : $n_fattura = $fatture[$i]['n_fattura'];

                    $link_id = Modules::get('Fatture di vendita')['id'];
                    echo "<tr><td class='first_cell text-left'><a href='".ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$fatture[$i]['iddocumento']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">Fatt. n. ".$n_fattura."</a></td>\n";

                    echo "<td class='table_cell text-left'>".Translator::dateToLocale($fatture[$i]['data_fattura'])."</td>\n";
                    echo "<td class='table_cell text-right'>".Translator::numberToLocale($fatture[$i]['costo_unitario'])." &euro;</td></tr>\n";
                    array_push($ids, '"'.$fatture[$i]['iddocumento'].'"');
                }
                echo "</table>\n";
            } else {
                echo '<br/>'.tr('Nessuna vendita di questo articolo al cliente selezionato')."...<br/>\n";
            }
        }
        echo '</small>';
        break;

    // Legge gli ultimi prezzi di vendita di un determinato articolo e li visualizza per suggerire il prezzo di vendita
    case 'getprezzivendita':
        echo '<small>';
        // Ultime 5 vendite totali
        $fatture = $dbo->fetchArray("SELECT DISTINCT iddocumento, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_fattura, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_fattura, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_fattura FROM co_righe_documenti WHERE idarticolo='".$idarticolo."' AND iddocumento IN(SELECT id FROM co_documenti WHERE id_tipo_documento IN(SELECT id FROM co_tipidocumento WHERE dir='entrata') ) ORDER BY data_fattura DESC, n_fattura DESC LIMIT 0,5");

        if (sizeof($fatture) > 0) {
            echo "<br/><table cellspacing='0' class='table-striped' >\n";
            echo "<tr><th width='150'>Documento</th>\n";
            echo "<th width='50'>Data</th>\n";
            echo "<th width='80' class='text-right' >Totale</th></tr>\n";

            for ($i = 0; $i < sizeof($fatture); ++$i) {
                ($fatture[$i]['n2_fattura'] != '') ? $n_fattura = $fatture[$i]['n2_fattura'] : $n_fattura = $fatture[$i]['n_fattura'];

                $link_id = Modules::get('Fatture di vendita')['id'];
                echo "<tr><td class='first_cell text-left'><a href='".ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$fatture[$i]['iddocumento']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">Fatt. n. ".$n_fattura."</a></td>\n";

                echo "<td class='table_cell text-left'>".Translator::dateToLocale($fatture[$i]['data_fattura'])."</td>\n";
                echo "<td class='table_cell text-right'>".Translator::numberToLocale($fatture[$i]['costo_unitario'])." &euro;</td></tr>\n";
            }
            echo "</table>\n";
        } else {
            echo '<br/>'.tr('Questo articolo non è mai stato venduto')."...<br/>\n";
        }
        echo '</small>';
        echo '<br/>';

        break;

    // Legge gli ultimi prezzi di vendita di un determinato articolo e li visualizza per suggerire il prezzo di vendita
    case 'getprezziacquisto':
        echo '<small>';
        // Ultime 5 vendite totali
        $fatture = $dbo->fetchArray("SELECT DISTINCT iddocumento, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_fattura, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_fattura, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_fattura FROM co_righe_documenti WHERE idarticolo='".$idarticolo."' AND iddocumento IN(SELECT id FROM co_documenti WHERE id_tipo_documento IN(SELECT id FROM co_tipidocumento WHERE dir='uscita') ) ORDER BY data_fattura DESC, n_fattura DESC LIMIT 0,5");

        if (sizeof($fatture) > 0) {
            echo "<br/><table cellspacing='0' class='table-striped' >\n";
            echo "<tr><th width='150'>Documento</th>\n";
            echo "<th width='50'>Data</th>\n";
            echo "<th width='80' class='text-right'>Totale</th></tr>\n";

            for ($i = 0; $i < sizeof($fatture); ++$i) {
                ($fatture[$i]['n2_fattura'] != '') ? $n_fattura = $fatture[$i]['n2_fattura'] : $n_fattura = $fatture[$i]['n_fattura'];

                $link_id = Modules::get('Fatture di acquisto')['id'];
                echo "<tr><td class='first_cell text-left'><a href='".ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$fatture[$i]['iddocumento']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">Fatt. n. ".$n_fattura."</a></td>\n";

                echo "<td class='table_cell text-left'>".Translator::dateToLocale($fatture[$i]['data_fattura'])."</td>\n";
                echo "<td class='table_cell text-right'>".Translator::numberToLocale($fatture[$i]['costo_unitario'])." &euro;</td></tr>\n";
            }
            echo "</table>\n";
        } else {
            echo '<br/>'.tr('Questo articolo non è mai stato acquistato')."...<br/>\n";
        }
        echo '</small>';
        echo '<br/>';

        break;
}
