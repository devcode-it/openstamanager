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

use Modules\Articoli\Articolo;

$idarticolo = get('idarticolo');
$limit = get('limit');

switch ($resource) {
    // Legge gli ultimi prezzi di vendita di un determinato cliente e un determinato articolo e li visualizza per suggerire il prezzo di vendita
    case 'getprezzi':
        $ids = [];
        $idanagrafica = get('idanagrafica');
        $ids = ['""'];

        echo '<small>';
        if (!empty($idarticolo)) {
            // Ultime 5 vendite al cliente
            $documenti = $dbo->fetchArray('SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento FROM co_righe_documenti WHERE idarticolo='.prepare($idarticolo).' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="entrata") AND idanagrafica='.prepare($idanagrafica).')
            UNION
            SELECT idddt AS id, "Ddt" AS tipo, "Ddt di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento, (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento, (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento FROM dt_righe_ddt WHERE idarticolo='.$idarticolo.' AND idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="entrata") AND idanagrafica='.prepare($idanagrafica).') ORDER BY id DESC LIMIT 0,5');

            if (sizeof($documenti) > 0) {
                echo "<br/><table class='table table-striped table-bordered table-extra-condensed' >\n";
                echo "<tr><th width='180'>Documento</th>\n";
                echo "<th width='100' class='text-right' >Totale</th></tr>\n";

                for ($i = 0; $i < sizeof($documenti); ++$i) {
                    ($documenti[$i]['n2_documento'] != '') ? $n_documento = $documenti[$i]['n2_documento'] : $n_documento = $documenti[$i]['n_documento'];

                    $link_id = Modules::get($documenti[$i]['modulo'])['id'];
                    echo "<tr><td class='first_cell text-left'><a href='".base_path().'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                    echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                    $ids[] = '"'.$documenti[$i]['id'].'"';
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
        $ids = [];
        echo '<small>';
        // Ultime 5 vendite totali
        $documenti = $dbo->fetchArray('SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento FROM co_righe_documenti WHERE idarticolo='.prepare($idarticolo).' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="entrata"))
        UNION
        SELECT idddt AS id, "Ddt" AS tipo, "Ddt di vendita" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento, (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento, (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento FROM dt_righe_ddt WHERE idarticolo='.prepare($idarticolo).' AND idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="entrata")) ORDER BY id DESC LIMIT 0,'.$limit.'');

        if (sizeof($documenti) > 0) {
            echo "<table class='table table-striped table-bordered table-extra-condensed' >\n";
            echo "<tr><th width='180'>Documento</th>\n";
            echo "<th width='100' class='text-right' >Totale</th></tr>\n";

            for ($i = 0; $i < sizeof($documenti); ++$i) {
                ($documenti[$i]['n2_documento'] != '') ? $n_documento = $documenti[$i]['n2_documento'] : $n_documento = $documenti[$i]['n_documento'];

                $link_id = Modules::get($documenti[$i]['modulo'])['id'];
                echo "<tr><td class='first_cell text-left'><a href='".base_path().'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                $ids[] = '"'.$documenti[$i]['id'].'"';
            }
            echo "</table>\n";
        } else {
            echo ''.tr('Nessuna vendita trovata di questo articolo')."...<br/>\n";
        }

        break;

    // Legge gli ultimi prezzi di acquisto di un determinato articolo e li visualizza per suggerire il prezzo di acquisto
    case 'getprezziacquisto':
        $ids = [];
        echo '<small>';
        // Ultimi 5 acquisti totali
        $documenti = $dbo->fetchArray('SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di acquisto" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento FROM co_righe_documenti WHERE idarticolo='.prepare($idarticolo).' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita"))
        UNION
        SELECT idddt AS id, "Ddt" AS tipo, "Ddt di acquisto" AS modulo, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento, (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento, (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento FROM dt_righe_ddt WHERE idarticolo='.prepare($idarticolo).' AND idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="uscita")) ORDER BY id DESC LIMIT 0,'.$limit.'');

        if (sizeof($documenti) > 0) {
            echo "<table class='table table-striped table-bordered table-extra-condensed' >\n";
            echo "<tr><th width='180'>Documento</th>\n";
            echo "<th width='100' class='text-right' >Totale</th></tr>\n";

            for ($i = 0; $i < sizeof($documenti); ++$i) {
                ($documenti[$i]['n2_documento'] != '') ? $n_documento = $documenti[$i]['n2_documento'] : $n_documento = $documenti[$i]['n_documento'];

                $link_id = Modules::get($documenti[$i]['modulo'])['id'];
                echo "<tr><td class='first_cell text-left'><a href='".base_path().'/editor.php?id_module='.$link_id.'&id_record='.$documenti[$i]['id']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">".$documenti[$i]['tipo'].'. n. '.$n_documento.' del '.Translator::dateToLocale($documenti[$i]['data_documento'])." </a></td>\n";
                echo "<td class='table_cell text-right'>".moneyFormat($documenti[$i]['costo_unitario'])."</td></tr>\n";
                $ids[] = '"'.$documenti[$i]['id'].'"';
            }
            echo "</table>\n";
        } else {
            echo ''.tr('Nessun acquisto trovato di questo articolo')."...<br/>\n";
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

        // Prezzi netti clienti / listino fornitore
        $query = 'SELECT minimo, massimo,
            sconto_percentuale,
            '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario
        FROM mg_prezzi_articoli
        WHERE id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione).' |where|
        ORDER BY minimo ASC, massimo DESC';
        $query_anagrafica = replace($query, [
            '|where|' => ' AND id_anagrafica = '.prepare($id_anagrafica),
        ]);
        $prezzi = $database->fetchArray($query_anagrafica);

        // Prezzi listini clienti
        $query = 'SELECT sconto_percentuale AS sconto_percentuale_listino, mg_listini.nome,
            '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino
        FROM mg_listini
        LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
        LEFT JOIN an_anagrafiche ON mg_listini.id=an_anagrafiche.id_listino
        WHERE mg_listini.data_attivazione<=NOW() AND mg_listini_articoli.data_scadenza>=NOW() AND mg_listini.attivo=1 AND id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione).' |where|';
        $query_anagrafica = replace($query, [
            '|where|' => ' AND idanagrafica = '.prepare($id_anagrafica),
        ]);
        $listino = $database->fetchArray($query_anagrafica);

        // Prezzi listini clienti sempre visibili
        $query = 'SELECT mg_listini.nome, sconto_percentuale AS sconto_percentuale_listino_visibile,
            '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino_visibile
        FROM mg_listini
        LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
        WHERE mg_listini.data_attivazione<=NOW() AND mg_listini_articoli.data_scadenza>=NOW() AND mg_listini.attivo=1 AND mg_listini.is_sempre_visibile=1 AND id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione);
        $listini_sempre_visibili = $database->fetchArray($query);

        // Prezzi scheda articolo
        if ($direzione == 'uscita') {
            $prezzo_articolo = $database->fetchArray('SELECT prezzo_acquisto AS prezzo_scheda FROM mg_articoli WHERE id = '.prepare($id_articolo));
        } else {
            $prezzo_articolo = $database->fetchArray('SELECT '.($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita').' AS prezzo_scheda FROM mg_articoli WHERE id = '.prepare($id_articolo));
        }

        // Ultimo prezzo al cliente
        $ultimo_prezzo = $dbo->fetchArray('SELECT '.($prezzi_ivati ? '(prezzo_unitario_ivato-sconto_unitario_ivato)' : '(prezzo_unitario-sconto_unitario)').' AS prezzo_ultimo FROM co_righe_documenti LEFT JOIN co_documenti ON co_documenti.id=co_righe_documenti.iddocumento WHERE idarticolo='.prepare($id_articolo).' AND idanagrafica='.prepare($id_anagrafica).' AND idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='.prepare($direzione).') ORDER BY data DESC LIMIT 0,1');

        $results = array_merge($prezzi, $listino, $listini_sempre_visibili, $prezzo_articolo, $ultimo_prezzo);

        echo json_encode($results);

        break;

    case 'getGiacenze':
        $id_articolo = get('id_articolo');
        $id_anagrafica = get('id_anagrafica');
        $direzione = get('dir') == 'uscita' ? 'uscita' : 'entrata';

        if (empty($id_articolo) || empty($id_anagrafica)) {
            return;
        }

        $articolo = Articolo::find($id_articolo);
        $giacenze = $articolo->getGiacenze();
        $sedi = $dbo->fetchArray('(SELECT "0" AS id, IF(indirizzo!=\'\', CONCAT_WS(" - ", "'.tr('Sede legale').'", CONCAT(citta, \' (\', indirizzo, \')\')), CONCAT_WS(" - ", "'.tr('Sede legale').'", citta)) AS nomesede FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')).') UNION (SELECT id, IF(indirizzo!=\'\',CONCAT_WS(" - ", nomesede, CONCAT(citta, \' (\', indirizzo, \')\')), CONCAT_WS(" - ", nomesede, citta )) AS nomesede FROM an_sedi WHERE idanagrafica='.prepare(setting('Azienda predefinita')).')');

        $results = [
            'articolo' => $articolo,
            'giacenze' => $giacenze,
            'sedi' => $sedi,
        ];

        echo json_encode($results);

        break;

    case 'getDatiVendita':
        $id_articolo = get('id_articolo');
        $id_anagrafica = get('id_anagrafica');
        $direzione = get('dir') == 'uscita' ? 'uscita' : 'entrata';

        if (empty($id_articolo) || empty($id_anagrafica)) {
            return;
        }

        //get current date
        $current_month = date('m');
        $current_year = date('Y');

        for ($i = 0; $i < 12; ++$i) {
            $month = $current_month;
            $year = $current_year;

            $datiVendita[] = [
                'mese' => $month,
                'anno' => $year,
                'data' => $dbo->fetchArray(
                    'SELECT SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) AS qta,
                    SUM(
                        IF(reversed=1, -(co_righe_documenti.subtotale - co_righe_documenti.sconto), (co_righe_documenti.subtotale - co_righe_documenti.sconto))
                    ) AS totale,
                    mg_articoli.id, mg_articoli.codice, mg_articoli.descrizione, mg_articoli.um
                    FROM co_documenti
                    INNER JOIN co_statidocumento ON co_statidocumento.id = co_documenti.idstatodocumento
                    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id
                    INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id
                    INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo
                    INNER JOIN zz_segments ON co_documenti.id_segment=zz_segments.id
                    WHERE co_tipidocumento.dir = "entrata"
                    AND
                        (co_statidocumento.descrizione = "Pagato"
                        OR co_statidocumento.descrizione = "Parzialmente pagato"
                        OR co_statidocumento.descrizione = "Emessa"
                    )
                    AND MONTH(co_documenti.data) = ' . $month . '
                    AND YEAR(co_documenti.data) = ' . $year . '
                    AND mg_articoli.id = ' . $id_articolo . '
                    AND zz_segments.autofatture=0
                    GROUP BY co_righe_documenti.idarticolo'
                ),
            ];

            if ($current_month == 1) {
                $current_month = 12;
                $current_year--;
            } else {
                $current_month--;
            }

        }

        $results = [
            'datiVendita' => $datiVendita,
        ];

        echo json_encode($results);

        break;

    case 'articoli_barcode_file':
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $id_anagrafica = get('id_anagrafica'); // ID passato via URL in modo fisso
        $barcodes = json_decode(get('barcodes'), true);
        $barcodeTrovati = [];

        foreach ($barcodes as $barcode => $qta) {
            //select barcode
            $rs = $dbo->fetchOne(
                'SELECT mg_articoli.*,
                IFNULL(mg_fornitore_articolo.codice_fornitore, mg_articoli.codice) AS codice,
                IFNULL(mg_fornitore_articolo.descrizione, mg_articoli.descrizione) AS descrizione,
                IFNULL(mg_fornitore_articolo.prezzo_acquisto, mg_articoli.prezzo_acquisto) AS prezzo_acquisto,
                mg_articoli.'.($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita').' AS prezzo_vendita,
                mg_articoli.prezzo_vendita_ivato AS prezzo_vendita_ivato,
                IFNULL(mg_fornitore_articolo.qta_minima, 0) AS qta_minima,
                mg_fornitore_articolo.id AS id_dettaglio_fornitore
                FROM mg_articoli
                LEFT JOIN mg_fornitore_articolo
                    ON mg_fornitore_articolo.id_articolo = mg_articoli.id
                    AND mg_fornitore_articolo.deleted_at IS NULL
                    AND mg_fornitore_articolo.id_fornitore = '.prepare($id_anagrafica).'
                WHERE mg_articoli.attivo = 1 AND mg_articoli.deleted_at IS NULL AND REPLACE(mg_articoli.barcode, "/", "-") = ' . prepare($barcode)
            );

            if (!empty($rs)) {
                $barcodeTrovati[] = [
                    'dettaglio' => $rs,
                    'qta' => $qta,
                ];
            } else {
                $barcodeTrovati[] = [
                    'dettaglio' => null,
                    'barcode' => $barcode,
                    'qta' => $qta,
                ];
            }
        }

        $results = [
            'barcodeTrovati' => $barcodeTrovati,
        ];

        echo json_encode($results);

        break;
}
