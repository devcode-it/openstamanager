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
            $documenti = $dbo->fetchArray('SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento FROM co_righe_documenti WHERE idarticolo='.prepare($idarticolo).' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="entrata") AND idanagrafica='.prepare($idanagrafica).')
            UNION
            SELECT idddt AS id, "Ddt" AS tipo, "Ddt di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento, (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento, (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento FROM dt_righe_ddt WHERE idarticolo='.$idarticolo.' AND idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="entrata") AND idanagrafica='.prepare($idanagrafica).') LIMIT 0,5');

            if (sizeof($documenti) > 0) {
                echo "<br/><table cellspacing='0' class='table-striped table-bordered' >\n";
                echo "<tr><th width='180'>Documento</th>\n";
                echo "<th width='100' class='text-right' >Totale</th></tr>\n";

                for ($i = 0; $i < sizeof($documenti); ++$i) {
                    ($documenti[$i]['n2_documento'] != '') ? $n_documento = $documenti[$i]['n2_documento'] : $n_documento = $documenti[$i]['n_documento'];

                    $link_id = Modules::get($documenti[$i]['modulo'])['id'];
                    echo "<tr><td class='first_cell text-left'><a href='".ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                    echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                    array_push($ids, '"'.$documenti[$i]['id'].'"');
                }
                echo "</table>\n";
            } else {
                echo '<br/>'.tr('Nessuna vendita trovata di questo articolo al cliente')."...<br/>\n";
            }
        }
        echo '</small>';
        break;

    // Legge gli ultimi prezzi di vendita di un determinato articolo e li visualizza per suggerire il prezzo di vendita
    case 'getprezzivendita':
        echo '<small>';
        // Ultime 5 vendite totali
        $documenti = $dbo->fetchArray('SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento FROM co_righe_documenti WHERE idarticolo='.prepare($idarticolo).' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="entrata"))
        UNION
        SELECT idddt AS id, "Ddt" AS tipo, "Ddt di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento, (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento, (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento FROM dt_righe_ddt WHERE idarticolo='.prepare($idarticolo).' AND idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="entrata")) LIMIT 0,5');

        if (sizeof($documenti) > 0) {
            echo "<br/><table cellspacing='0' class='table-striped table-bordered' >\n";
            echo "<tr><th width='180'>Documento</th>\n";
            echo "<th width='100' class='text-right' >Totale</th></tr>\n";

            for ($i = 0; $i < sizeof($documenti); ++$i) {
                ($documenti[$i]['n2_documento'] != '') ? $n_documento = $documenti[$i]['n2_documento'] : $n_documento = $documenti[$i]['n_documento'];

                $link_id = Modules::get($documenti[$i]['modulo'])['id'];
                echo "<tr><td class='first_cell text-left'><a href='".ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                array_push($ids, '"'.$documenti[$i]['id'].'"');
            }
            echo "</table>\n";
        } else {
            echo '<br/>'.tr('Nessuna vendita trovata di questo articolo')."...<br/>\n";
        }

        break;

    // Legge gli ultimi prezzi di acquisto di un determinato articolo e li visualizza per suggerire il prezzo di acquisto
    case 'getprezziacquisto':
        echo '<small>';
        // Ultimi 5 acquisti totali
        $documenti = $dbo->fetchArray('SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di acquisto" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento FROM co_righe_documenti WHERE idarticolo='.prepare($idarticolo).' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita"))
        UNION
        SELECT idddt AS id, "Ddt" AS tipo, "Ddt di acquisto" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento, (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento, (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento FROM dt_righe_ddt WHERE idarticolo='.prepare($idarticolo).' AND idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="uscita")) LIMIT 0,5');

        if (sizeof($documenti) > 0) {
            echo "<br/><table cellspacing='0' class='table-striped table-bordered' >\n";
            echo "<tr><th width='180'>Documento</th>\n";
            echo "<th width='100' class='text-right' >Totale</th></tr>\n";

            for ($i = 0; $i < sizeof($documenti); ++$i) {
                ($documenti[$i]['n2_documento'] != '') ? $n_documento = $documenti[$i]['n2_documento'] : $n_documento = $documenti[$i]['n_documento'];

                $link_id = Modules::get($documenti[$i]['modulo'])['id'];
                echo "<tr><td class='first_cell text-left'><a href='".ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                array_push($ids, '"'.$documenti[$i]['id'].'"');
            }
            echo "</table>\n";
        } else {
            echo '<br/>'.tr('Nessun acquisto trovato di questo articolo')."...<br/>\n";
        }

        break;
}
