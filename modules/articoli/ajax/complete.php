<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
                    echo "<tr><td class='first_cell text-left'><a href='".base_path().'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
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
                echo "<tr><td class='first_cell text-left'><a href='".base_path().'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
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
                echo "<tr><td class='first_cell text-left'><a href='".base_path().'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                array_push($ids, '"'.$documenti[$i]['id'].'"');
            }
            echo "</table>\n";
        } else {
            echo '<br/>'.tr('Nessun acquisto trovato di questo articolo')."...<br/>\n";
        }

        break;

    /*
     * Opzioni utilizzate:
     * - id_articolo
     * - id_anagrafica
     */
    case 'dettagli_articolo':
        $id_articolo = get('id_articolo');
        $id_anagrafica = get('id_anagrafica');
        $direzione = get('dir') == 'uscita' ? 'uscita' : 'entrata';

        if (empty($id_articolo) || empty($id_anagrafica)) {
            return;
        }

        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

        $query = 'SELECT minimo, massimo,
            sconto_percentuale,
            '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario
        FROM mg_prezzi_articoli
        WHERE id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione).' |where|
        ORDER BY minimo ASC, massimo DESC';

        // Lettura dei prezzi relativi all'anagrafica
        $query_anagrafica = replace($query, [
            '|where|' => ' AND id_anagrafica = '.prepare($id_anagrafica),
        ]);
        $results = $database->fetchArray($query_anagrafica);

        // Lettura dei prezzi relativi all'articolo, senza anagrafica collegata
        if (empty($results)) {
            $query_predefinito = replace($query, [
                '|where|' => '',
            ]);
            $results = $database->fetchArray($query_predefinito);
        }

        // Lettura dei prezzi registrati direttamente sull'articolo, per compatibilità con il formato standard
        if (empty($results)) {
            $result = $database->fetchOne('SELECT prezzo_acquisto, '.($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita').' AS prezzo_vendita FROM mg_articoli WHERE id = '.prepare($id_articolo));

            $results = [
                [
                    'minimo' => null,
                    'massino' => null,
                    'sconto_percentuale' => 0,
                    'prezzo_unitario' => $direzione == 'uscita' ? $result['prezzo_acquisto'] : $result['prezzo_vendita'],
                ],
            ];
        }

        echo json_encode($results);

        break;
}
