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

/**
 * Utility functions for displaying Prima Nota movements
 */

/**
 * Renderizza una tabella con i movimenti di prima nota collegati a un documento o scadenza
 *
 * @param int|null $id_documento ID del documento (fattura)
 * @param int|null $id_scadenza ID della scadenza
 * @param string|null $titolo Titolo personalizzato per la tabella
 * @return string HTML della tabella
*/

function renderTabellaMovimentiPrimaNota($id_documento = null, $id_scadenza = null) {
    global $dbo;

    $where_conditions = ["co_movimenti.primanota = 1"];
    $params = [];

    if (!empty($id_documento)) {
        $where_conditions[] = "co_movimenti.iddocumento = ?";
        $params[] = $id_documento;
    } elseif (!empty($id_scadenza)) {
        $where_conditions[] = "co_movimenti.id_scadenza = ?";
        $params[] = $id_scadenza;
    } else {
        return "";
    }

    $where_clause = implode(" AND ", $where_conditions);

    $query = "SELECT
        co_movimenti.idmastrino,
        co_movimenti.data,
        co_movimenti.descrizione as causale,
        SUM(CASE WHEN co_movimenti.totale > 0 THEN co_movimenti.totale ELSE 0 END) as importo_totale
    FROM co_movimenti
    WHERE $where_clause
    GROUP BY co_movimenti.idmastrino, co_movimenti.data, co_movimenti.descrizione
    ORDER BY co_movimenti.data DESC, co_movimenti.idmastrino DESC";

    $movimenti = $dbo->fetchArray($query, $params);

    if (empty($movimenti)) {
        return '';
    }

    $html = '
    <div class="card" style="margin-top: 15px;">
        <div class="card-header">
            <h5 class="card-title">'.tr('Movimenti in prima nota').'</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <tbody>';

    foreach ($movimenti as $movimento) {
        $data = dateFormat($movimento['data']);
        $causale = $movimento['causale'];
        $importo = moneyFormat($movimento['importo_totale']);

        $html .= '
                        <tr>
                            <td style="width: 80px;">'.$data.'</td>
                            <td>'.$causale.'</td>
                            <td style="text-align: right; width: 100px;">'.$importo.'</td>
                            <td style="width: 100px;">
                                <a href="'.base_path().'/editor.php?id_module='.$dbo->fetchOne("SELECT id FROM zz_modules WHERE name = 'Prima nota'")['id'].'&id_record='.$movimento['idmastrino'].'"
                                   class="btn btn-primary btn-xs" target="_blank">
                                    Visualizza »
                                </a>
                            </td>
                        </tr>';
    }

    $html .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

    return $html;
}