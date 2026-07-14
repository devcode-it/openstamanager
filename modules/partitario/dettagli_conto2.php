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

include_once __DIR__.'/../../core.php';

/**
 * Costruisce le celle HTML di una riga sottoconto. Riusata sia dal render completo
 * (mastri sotto soglia, client-side) sia dalla risposta JSON server-side (oltre soglia),
 * così la query e il markup della riga vivono una volta sola.
 *
 * @return array Celle: [sottoconto, importo, (importo reddito se Economico), vuota]
 */
function partitario_sottoconto_cells($conto_terzo, $conto_secondo, $conto_primo)
{
    $is_economico = $conto_primo['descrizione'] == 'Economico';
    $numero_movimenti = $conto_terzo['numero_movimenti'];

    $totale_conto = $conto_terzo['totale'];
    $totale_reddito = $conto_terzo['totale_reddito'];
    if ($conto_primo['descrizione'] != 'Patrimoniale') {
        $totale_conto = -$totale_conto ?: 0;
        $totale_reddito = -$totale_reddito ?: 0;
    }

    $cella = '';

    // Possibilità di esplodere i movimenti del conto
    if (!empty($numero_movimenti)) {
        $cella .= '<button type="button" id="movimenti-'.$conto_terzo['id'].'" class="btn btn-default btn-xs plus-btn"><i class="fa fa-plus"></i></button>';
    }

    // Span con i pulsanti
    $cella .= '<span class="hide tools pull-right">';

    // Possibilità di visionare l'anagrafica
    $id_anagrafica = $conto_terzo['id_anagrafica'];
    $anagrafica_deleted = $conto_terzo['deleted_at'];
    if (isset($id_anagrafica)) {
        $cella .= Modules::link('Anagrafiche', $id_anagrafica, ' <i title="'.(isset($anagrafica_deleted) ? tr('Anagrafica eliminata') : tr('Visualizza anagrafica')).'" class="btn btn-'.(isset($anagrafica_deleted) ? 'danger' : 'primary').' btn-xs fa fa-user" ></i>');
    }

    // Stampa mastrino
    if (!empty($numero_movimenti)) {
        $cella .= Prints::getLink('Mastrino', $conto_terzo['id'], 'btn-info btn-xs', '', null, 'lev=3');
    }

    // Pulsante per aggiornare il totale reddito del conto di livello 3
    $cella .= '<button type="button" class="btn btn-info btn-xs" onclick="aggiornaReddito('.$conto_terzo['id'].')"><i class="fa fa-refresh"></i></button>';

    // Pulsante per modificare il nome del conto di livello 3
    $cella .= '<button type="button" class="btn btn-warning btn-xs" onclick="modificaConto('.$conto_terzo['id'].')"><i class="fa fa-edit"></i></button>';

    // Possibilità di eliminare il conto se non ci sono movimenti collegati
    if ($numero_movimenti <= 0) {
        $cella .= '<a class="btn btn-danger btn-xs ask" data-widget="tooltip" title="'.tr('Elimina').'" data-backto="record-list" data-op="del" data-id_conto="'.$conto_terzo['id'].'"><i class="fa fa-trash"></i></a>';
    }

    $cella .= '</span>';

    // Span con info del conto + contenitore per l'espansione dei movimenti
    $deducibile = $conto_terzo['percentuale_deducibile'] != '100.00'
        ? tr('(deducibile al _PERC_%', ['_PERC_' => Translator::numberToLocale($conto_terzo['percentuale_deducibile'], 0)]).')'
        : '';
    $cella .= '<span class="clickable" id="movimenti-'.$conto_terzo['id'].'">&nbsp;'.$conto_secondo['numero'].'.'.$conto_terzo['numero'].' '.$conto_terzo['descrizione'].' <span class="text-muted">'.$deducibile.'</span></span>';
    $cella .= '<div id="conto_'.$conto_terzo['id'].'" style="display:none;"></div>';

    $cells = [$cella, moneyFormat($totale_conto, 2)];
    if ($is_economico) {
        $cells[] = moneyFormat($totale_reddito, 2);
    }
    $cells[] = '';

    return $cells;
}

$id_conto = get('id_conto');
$conto_secondo = $dbo->selectOne('co_piano_dei_conti2', '*', ['id' => $id_conto]);
$conto_primo = $dbo->selectOne('co_piano_dei_conti1', '*', ['id' => $conto_secondo['id_piano_dei_conti1']]);
$is_economico = $conto_primo['descrizione'] == 'Economico';

// Oltre la soglia configurabile i sottoconti del mastro vengono mostrati come DataTable
// (ricerca + impaginazione server-side). Il conteggio si basa sul totale dei sottoconti
// del mastro, indipendentemente da eventuali filtri.
$soglia_datatable = (int) setting('Soglia datatable sottoconti');
if ($soglia_datatable <= 0) {
    $soglia_datatable = 500;
}

// Subquery dei movimenti per conto (periodo + competenza). Definita una volta e riusata
// sia dalla query di pagina sia dal totale del footer.
$movimenti_subquery = '(
    SELECT COUNT(id_conto) AS numero_movimenti,
        id_conto,
        SUM(
            CASE
                WHEN co_movimenti.data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']).' THEN totale
                ELSE 0
            END
        ) AS totale,
        SUM(
            CASE
                WHEN data_inizio_competenza IS NULL OR data_fine_competenza IS NULL THEN totale_reddito
                ELSE totale_reddito * (
                    DATEDIFF(
                        LEAST(data_fine_competenza, '.prepare($_SESSION['period_end']).'),
                        GREATEST(data_inizio_competenza, '.prepare($_SESSION['period_start']).')
                    ) + 1
                ) / (DATEDIFF(data_fine_competenza, data_inizio_competenza) + 1)
            END
        ) AS totale_reddito
    FROM co_movimenti
    WHERE (
        (data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']).')
        OR (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND data_fine_competenza >= '.prepare($_SESSION['period_start']).' AND data_inizio_competenza <= '.prepare($_SESSION['period_end']).')
        OR (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND data_inizio_competenza < '.prepare($_SESSION['period_start']).' AND data_fine_competenza > '.prepare($_SESSION['period_end']).')
        OR (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND data_inizio_competenza <= '.prepare($_SESSION['period_end']).' AND data_inizio_competenza >= '.prepare($_SESSION['period_start']).')
        OR (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND data_fine_competenza >= '.prepare($_SESSION['period_start']).' AND data_fine_competenza <= '.prepare($_SESSION['period_end']).')
    ) GROUP BY id_conto
)';

// Anagrafica collegata al conto: due LEFT JOIN diretti e indicizzati su an_anagrafiche
// (come cliente o fornitore) invece di un join IN su derived table, che non usava indici.
$anagrafica_select = 'COALESCE(ac.id, af.id) AS id_anagrafica, COALESCE(ac.deleted_at, af.deleted_at) AS deleted_at';
$anagrafica_join = function ($src) {
    return '
        LEFT JOIN an_anagrafiche ac ON ac.id_conto_cliente = '.$src.'.id
        LEFT JOIN an_anagrafiche af ON af.id_conto_fornitore = '.$src.'.id';
};

// Elenco COMPLETO dei sottoconti (mastri sotto soglia, client-side): join su tutta la tabella.
$query3_full = 'SELECT `co_piano_dei_conti3`.*, movimenti.numero_movimenti, movimenti.totale, movimenti.totale_reddito, '.$anagrafica_select.'
    FROM `co_piano_dei_conti3`'.$anagrafica_join('co_piano_dei_conti3').'
        LEFT OUTER JOIN '.$movimenti_subquery.' movimenti ON co_piano_dei_conti3.id=movimenti.id_conto
    WHERE `id_piano_dei_conti2` = '.prepare($conto_secondo['id']).' ORDER BY numero ASC';

// Conteggio totale sottoconti del mastro (decide la soglia ed è recordsTotal della DataTable).
$total_sottoconti = (int) $dbo->fetchOne('SELECT COUNT(*) AS tot FROM `co_piano_dei_conti3` WHERE `id_piano_dei_conti2` = '.prepare($conto_secondo['id']))['tot'];
$usa_datatable = $total_sottoconti > $soglia_datatable;
$datatable_id = 'sottoconti-datatable-'.$conto_secondo['id'];
$root_id = 'sottoconti-root-'.$conto_secondo['id'];

// Filtro di ricerca server-side: descrizione oppure numero "mastro.sottoconto".
// Valore letto raw per non farlo alterare dal formatter degli input.
$search_arr = get('search', true);
$search_value = (is_array($search_arr) && isset($search_arr['value'])) ? trim((string) $search_arr['value']) : '';
$search_where = '';
if ($search_value !== '') {
    $like = prepare('%'.$search_value.'%');
    $search_where = ' AND (`co_piano_dei_conti3`.`descrizione` LIKE '.$like.' OR CONCAT('.prepare($conto_secondo['numero']).', \'.\', `co_piano_dei_conti3`.`numero`) LIKE '.$like.')';
}

if (filter('draw', null, true) !== '') {
    // === Ramo JSON: una pagina di righe per la DataTable server-side ===
    // Parametri numerici letti raw: il formatter degli input potrebbe inserire
    // separatori di migliaia su valori grandi (es. start oltre 999) e rompere l'OFFSET.
    $draw = (int) filter('draw', null, true);
    $start = (int) filter('start', null, true);
    $length = (int) filter('length', null, true);
    if ($length <= 0) {
        $length = 25;
    }

    $records_filtered = (int) $dbo->fetchOne('SELECT COUNT(*) AS tot FROM `co_piano_dei_conti3` WHERE `id_piano_dei_conti2` = '.prepare($conto_secondo['id']).$search_where)['tot'];

    // Paginazione efficiente: prima si selezionano i sottoconti della sola pagina, poi si
    // fanno i join sulle ~25 righe risultanti, evitando i join sull'intero mastro.
    $page_query = 'SELECT base.*, movimenti.numero_movimenti, movimenti.totale, movimenti.totale_reddito, '.$anagrafica_select.'
        FROM (
            SELECT `co_piano_dei_conti3`.* FROM `co_piano_dei_conti3`
            WHERE `id_piano_dei_conti2` = '.prepare($conto_secondo['id']).$search_where.'
            ORDER BY numero ASC LIMIT '.$start.', '.$length.'
        ) base'.$anagrafica_join('base').'
            LEFT OUTER JOIN '.$movimenti_subquery.' movimenti ON base.id=movimenti.id_conto
        ORDER BY base.numero ASC';
    $rows = $dbo->fetchArray($page_query);

    $data = [];
    foreach ($rows as $conto_terzo) {
        $cells = partitario_sottoconto_cells($conto_terzo, $conto_secondo, $conto_primo);
        $row = [
            'DT_RowId' => 'conto3-'.$conto_terzo['id'],
            'DT_RowClass' => 'conto3',
        ];
        if (empty($conto_terzo['numero_movimenti'])) {
            $row['DT_RowAttr'] = ['style' => 'opacity: 0.5;'];
        }
        foreach ($cells as $i => $cella) {
            $row[$i] = $cella;
        }
        $data[] = $row;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_sottoconti,
        'recordsFiltered' => $records_filtered,
        'data' => $data,
    ]);
} else {
    // === Ramo HTML: guscio (oltre soglia, server-side) o elenco completo (sotto soglia) ===
    echo '<div id="'.$root_id.'">';

    if ($total_sottoconti == 0) {
        echo '<br><span>'.tr('Nessun conto presente').'</span>';
    } elseif ($usa_datatable) {
        // Totale del mastro per il footer: una sola query (indipendente dai filtri).
        $tot = $dbo->fetchOne('SELECT SUM(movimenti.totale) AS totale, SUM(movimenti.totale_reddito) AS totale_reddito
            FROM `co_piano_dei_conti3`
                LEFT OUTER JOIN '.$movimenti_subquery.' movimenti ON co_piano_dei_conti3.id=movimenti.id_conto
            WHERE `id_piano_dei_conti2` = '.prepare($conto_secondo['id']));
        $totale_conto2 = $tot['totale'];
        $totale_reddito2 = $tot['totale_reddito'];
        if ($conto_primo['descrizione'] != 'Patrimoniale') {
            $totale_conto2 = -$totale_conto2;
            $totale_reddito2 = -$totale_reddito2;
        }

        echo '
    <div class="table-responsive">
        <table id="'.$datatable_id.'" class="table table-striped table-hover table-sm js-sottoconti-datatable">
            <thead>
                <tr>
                    <th>'.tr('Sottoconto').'</th>
                    <th class="text-right">'.tr('Importo').'</th>';
        if ($is_economico) {
            echo '
                    <th class="text-right">'.tr('Importo reddito').'</th>';
        }
        echo '
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr class="totali">
                    <th class="text-right">'.tr('Totale').'</th>
                    <th class="text-right">'.moneyFormat($totale_conto2).'</th>';
        if ($is_economico) {
            echo '
                    <th class="text-right">'.moneyFormat($totale_reddito2).'</th>';
        }
        echo '
                </tr>
            </tfoot>
        </table>
        <br><br>
    </div>';

        // Colonne (3 Patrimoniale, 4 Economico) — importi allineati a destra via className.
        $columns_js = '{ data: "0" }, { data: "1", className: "text-right" }';
        if ($is_economico) {
            $columns_js .= ', { data: "2", className: "text-right" }';
        }
        $columns_js .= ', { data: "'.($is_economico ? '3' : '2').'" }';

        echo '
<style>
    /* Stile scoped alla DataTable dei sottoconti (override delle regole BS3 di datatables.css) */

    /* Length menu: select2 inline, tutto su una riga */
    .sottoconti-dt .dataTables_length label {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        margin: 0;
        font-weight: normal;
    }
    .sottoconti-dt .dataTables_length .select2-container {
        display: inline-block !important;
        width: auto !important;
        min-width: 4.5rem;
        vertical-align: middle;
    }

    /* Campo ricerca: come #input-cerca in cima alla pagina, solo piu\' piccola (no -lg) */
    .sottoconti-dt .dataTables_filter label {
        display: flex;
        justify-content: flex-end;
        width: 100%;
        margin: 0;
    }
    .sottoconti-dt .dataTables_filter input {
        display: block;
        width: 16rem;
        max-width: 100%;
        height: calc(2.25rem + 2px);
        margin: 0;
        padding: .375rem .75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: .25rem !important;
        box-shadow: none;
    }

    /* Riga paginazione: campo "Vai a pagina" + pager, allineati a destra */
    .sottoconti-dt .dt-pagination-col {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: .5rem .75rem;
        padding-top: .5rem;
    }
    .sottoconti-dt .dt-pagination-col .dataTables_paginate {
        padding-top: 0;
        text-align: right;
    }
    .sottoconti-dt .dt-goto {
        width: auto;
        flex: 0 0 auto;
        margin: 0;
    }
    .sottoconti-dt .dt-goto .input-group-text {
        /* !important per battere la regola globale .input-group-text di style.css */
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .sottoconti-dt .dt-goto input {
        width: 4.5rem;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }

    /* Bottoni paginazione in stile BS4 */
    .sottoconti-dt .dataTables_paginate .paginate_button {
        display: inline-block;
        box-sizing: border-box;
        min-width: 2.25rem;
        padding: .375rem .75rem !important; /* !important per battere il padding 24px BS3 su prev/next */
        border: 1px solid #dee2e6;
        background: #fff;
        color: #007bff;
        line-height: 1.25;
        text-align: center;
        cursor: pointer;
    }
    .sottoconti-dt .dataTables_paginate .paginate_button:hover {
        background: #e9ecef;
        color: #0056b3;
        text-decoration: none;
    }
    .sottoconti-dt .dataTables_paginate .paginate_button.current,
    .sottoconti-dt .dataTables_paginate .paginate_button.current:hover {
        border-color: #007bff;
        background: #007bff;
        color: #fff;
    }
    .sottoconti-dt .dataTables_paginate .paginate_button.disabled,
    .sottoconti-dt .dataTables_paginate .paginate_button.disabled:hover {
        color: #6c757d;
        background: #fff;
        cursor: default;
    }
    /* Gruppo numeri: bottoni uniti, estremi arrotondati */
    .sottoconti-dt .dataTables_paginate span a.paginate_button {
        margin-left: -1px;
        border-radius: 0;
    }
    .sottoconti-dt .dataTables_paginate span a.paginate_button:first-child {
        margin-left: 0;
        border-top-left-radius: .25rem;
        border-bottom-left-radius: .25rem;
    }
    .sottoconti-dt .dataTables_paginate span a.paginate_button:last-child {
        border-top-right-radius: .25rem;
        border-bottom-right-radius: .25rem;
    }
    /* Prec/Succ: bottoni separati dal gruppo numeri */
    .sottoconti-dt .dataTables_paginate .paginate_button.previous {
        border-radius: .25rem;
        margin-right: .5rem;
    }
    .sottoconti-dt .dataTables_paginate .paginate_button.next {
        border-radius: .25rem;
        margin-left: .5rem;
    }
    /* niente frecce FontAwesome doppie: solo le icone passate via language.paginate */
    .sottoconti-dt .dataTables_paginate .paginate_button.previous:before,
    .sottoconti-dt .dataTables_paginate .paginate_button.next:after {
        content: none !important;
    }
</style>
<script>
    $(function() {
        var $dt = $("#'.$datatable_id.'");
        if ($dt.length && !$.fn.DataTable.isDataTable($dt)) {
            $dt.DataTable({
                // Paginazione server-side: ogni pagina è una fetch con LIMIT/OFFSET (niente caricamento totale)
                serverSide: true,
                deferRender: true,
                searchDelay: 400,
                ajax: {
                    // globals.rootdir (calcolato nella pagina principale) evita il path raddoppiato
                    // di fileurl() quando questo file è richiesto direttamente su install servito alla root
                    url: globals.rootdir + "/modules/partitario/dettagli_conto2.php?id_module=" + globals.id_module + "&id_conto='.$conto_secondo['id'].'",
                    type: "get",
                    dataSrc: "data",
                    // Loader standard dell\'app (#main_loading), come caricaConti3/caricaMovimenti.
                    // stop(true, true) annulla l\'eventuale fadeOut ancora in corso di caricaConti3
                    // (primo caricamento), altrimenti il loader resterebbe invisibile.
                    beforeSend: function () { $("#main_loading").stop(true, true).show(); },
                    complete: function () { $("#main_loading").stop(true, true).fadeOut(); },
                },
                columns: [ '.$columns_js.' ],
                // Placeholder al posto dell\'etichetta "Cerca:" e icone per prec/succ (override locale)
                language: $.extend(true, {}, globals.translations.datatables, {
                    search: "_INPUT_",
                    searchPlaceholder: "'.tr('Cerca').'...",
                    paginate: {
                        previous: \'<i class="fa fa-angle-left"></i>\',
                        next: \'<i class="fa fa-angle-right"></i>\',
                    },
                }),
                ordering: false,
                searching: true,
                paging: true,
                lengthChange: true,
                pageLength: 25,
                order: [],
                // Ricerca iniziale: se la barra globale in cima è attiva, parte già filtrata (senza doppia fetch)
                search: { search: ($("#input-cerca").val() || "") },
                // Layout Bootstrap 4: length + filtro su una riga, info + paginazione su un\'altra
                dom: "<\'row\'<\'col-sm-12 col-md-6\'l><\'col-sm-12 col-md-6\'f>>rt<\'row\'<\'col-sm-12 col-md-5\'i><\'col-sm-12 col-md-7\'p>>",
                initComplete: function () {
                    var api = this.api();
                    // Classe sul wrapper per agganciare le regole CSS scoped (sopra)
                    var $container = $(api.table().container()).addClass("sottoconti-dt");

                    // Length menu: select2 tema bootstrap4 (coerente con gli altri select dell\'app)
                    $container.find(".dataTables_length select").select2({
                        theme: "bootstrap4",
                        language: "it",
                        width: "auto",
                        minimumResultsForSearch: -1,
                        allowClear: false,
                    });

                    // Campo ricerca: come #input-cerca in cima alla pagina, solo piu\' piccolo
                    $container.find(".dataTables_filter input").addClass("form-control text-center");

                    // Campo "Vai a pagina" accanto al pager (fratello del pager: il redraw non lo cancella)
                    var $pagCol = $container.find(".dataTables_paginate").parent().addClass("dt-pagination-col");
                    var $goto = $(\'<div class="dt-goto input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">'.tr('Vai a pagina').'</span></div><input type="number" min="1" class="form-control text-center"></div>\');
                    $goto.prependTo($pagCol);
                    $goto.find("input").on("keydown change", function (e) {
                        if (e.type === "keydown" && e.key !== "Enter") {
                            return;
                        }
                        var p = parseInt($(this).val(), 10);
                        if (isNaN(p)) {
                            return;
                        }
                        p = Math.min(Math.max(p, 1), api.page.info().pages);
                        $(this).val(p);
                        api.page(p - 1).draw("page");
                    });
                },
            });
        }
    });
</script>';
    } else {
        // Sotto soglia: elenco completo client-side (comportamento invariato).
        $terzo_livello = $dbo->fetchArray($query3_full);
        $totale_conto2 = 0;
        $totale_reddito2 = 0;

        echo '
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
            <tbody>';
        foreach ($terzo_livello as $conto_terzo) {
            $totale_conto = $conto_terzo['totale'];
            $totale_reddito = $conto_terzo['totale_reddito'];
            if ($conto_primo['descrizione'] != 'Patrimoniale') {
                $totale_conto = -$totale_conto ?: 0;
                $totale_reddito = -$totale_reddito ?: 0;
            }
            $totale_conto2 += $totale_conto;
            $totale_reddito2 += $totale_reddito;

            $cells = partitario_sottoconto_cells($conto_terzo, $conto_secondo, $conto_primo);
            echo '
                <tr class="conto3" id="conto3-'.$conto_terzo['id'].'" style="'.(!empty($conto_terzo['numero_movimenti']) ? '' : 'opacity: 0.5;').'">
                    <td>'.$cells[0].'</td>
                    <td width="10%" class="text-right">'.$cells[1].'</td>';
            if ($is_economico) {
                echo '
                    <td width="10%" class="text-right">'.$cells[2].'</td>';
            }
            echo '
                    <td width="5%"></td>
                </tr>';
        }
        echo '
            </tbody>
            <tfoot>
                <tr class="totali">
                    <th class="text-right">'.tr('Totale').'</th>
                    <th class="text-right">'.moneyFormat($totale_conto2).'</th>';
        if ($is_economico) {
            echo '
                    <th class="text-right">'.moneyFormat($totale_reddito2).'</th>';
        }
        echo '
                </tr>
            </tfoot>
        </table>
        <br><br>
    </div>';
    }

    // Script condiviso: hover sui pulsanti + espansione movimenti. Event delegation sul
    // contenitore (le righe arrivano dinamicamente in server-side ad ogni draw).
    echo '
<script>
    $(function() {
        var $root = $("#'.$root_id.'");

        $root.on("mouseover", "tr", function() {
            $(this).find(".tools").removeClass("hide");
        });
        $root.on("mouseleave", "tr", function() {
            $(this).find(".tools").addClass("hide");
        });

        $root.on("click", "span[id^=movimenti-], button[id^=movimenti-]", function() {
            var movimenti = $(this).parent().find("div[id^=conto_]");

            if (!movimenti.html()) {
                var id_conto = $(this).attr("id").split("-").pop();
                caricaMovimenti(movimenti.attr("id"), id_conto);
            } else {
                movimenti.slideToggle();
            }

            $(this).parent().find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
        });
    });

    function caricaMovimenti(selector, id_conto) {
        $("#main_loading").show();

        $.ajax({
            url: globals.rootdir + "/modules/partitario/dettagli_conto3.php",
            type: "get",
            data: {
                id_module: globals.id_module,
                id_conto: id_conto,
            },
            success: function(data){
               $("#" + selector).html(data)
                    .slideToggle();

               $("#main_loading").fadeOut();
            }
        });
    }
</script>';

    echo '</div>';
}
