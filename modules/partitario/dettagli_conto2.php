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

use Modules\Partitario\Movimento;
use Modules\Partitario\PianoDeiConti1;
use Modules\Partitario\PianoDeiConti2;
use Modules\Partitario\PianoDeiConti3;

$period_start = $_SESSION['period_start'];
$period_end = $_SESSION['period_end'];

function partitario_sottoconto_cells($conto_terzo, $conto_secondo, $conto_primo)
{
    $is_economico = $conto_primo->descrizione == 'Economico';
    $numero_movimenti = $conto_terzo->numero_movimenti;

    $totale_conto = $conto_terzo->totale;
    $totale_reddito = $conto_terzo->totale_reddito;
    if ($conto_primo->descrizione != 'Patrimoniale') {
        $totale_conto = -$totale_conto ?: 0;
        $totale_reddito = -$totale_reddito ?: 0;
    }

    $cella = '';

    if (!empty($numero_movimenti)) {
        $cella .= '<button type="button" id="movimenti-'.$conto_terzo->id.'" class="btn btn-default btn-xs plus-btn"><i class="fa fa-plus"></i></button>';
    }

    $cella .= '<span class="hide tools pull-right">';

    $id_anagrafica = $conto_terzo->id_anagrafica;
    $anagrafica_deleted = $conto_terzo->deleted_at;
    if (isset($id_anagrafica)) {
        $cella .= Modules::link('Anagrafiche', $id_anagrafica, ' <i title="'.(isset($anagrafica_deleted) ? tr('Anagrafica eliminata') : tr('Visualizza anagrafica')).'" class="btn btn-'.(isset($anagrafica_deleted) ? 'danger' : 'primary').' btn-xs fa fa-user" ></i>');
    }

    if (!empty($numero_movimenti)) {
        $cella .= Prints::getLink('Mastrino', $conto_terzo->id, 'btn-info btn-xs', '', null, 'lev=3');
    }

    $cella .= '<button type="button" class="btn btn-info btn-xs" onclick="aggiornaReddito('.$conto_terzo->id.')"><i class="fa fa-refresh"></i></button>';

    $cella .= '<button type="button" class="btn btn-warning btn-xs" onclick="modificaConto('.$conto_terzo->id.')"><i class="fa fa-edit"></i></button>';

    if ($numero_movimenti <= 0) {
        $cella .= '<a class="btn btn-danger btn-xs ask" data-widget="tooltip" title="'.tr('Elimina').'" data-backto="record-list" data-op="del" data-id_conto="'.$conto_terzo->id.'"><i class="fa fa-trash"></i></a>';
    }

    $cella .= '</span>';

    $deducibile = $conto_terzo->percentuale_deducibile != '100.00'
        ? tr('(deducibile al _PERC_%', ['_PERC_' => Translator::numberToLocale($conto_terzo->percentuale_deducibile, 0)]).')'
        : '';
    $cella .= '<span class="clickable" id="movimenti-'.$conto_terzo->id.'">&nbsp;'.$conto_secondo->numero.'.'.$conto_terzo->numero.' '.$conto_terzo->descrizione.' <span class="text-muted">'.$deducibile.'</span></span>';
    $cella .= '<div id="conto_'.$conto_terzo->id.'" style="display:none;"></div>';

    $cells = [$cella, moneyFormat($totale_conto, 2)];
    if ($is_economico) {
        $cells[] = moneyFormat($totale_reddito, 2);
    }
    $cells[] = '';

    return $cells;
}

$id_conto = get('id_conto');
$conto_secondo = PianoDeiConti2::find($id_conto);
$conto_primo = $conto_secondo->primoLivello;
$is_economico = $conto_primo->descrizione == 'Economico';

$soglia_datatable = (int) setting('Soglia datatable sottoconti');
if ($soglia_datatable <= 0) {
    $soglia_datatable = 500;
}

$movimenti_subquery = function ($query) use ($period_start, $period_end) {
    $query->selectRaw('COUNT(id_conto) AS numero_movimenti, id_conto,
        SUM(CASE WHEN data BETWEEN ? AND ? THEN totale ELSE 0 END) AS totale,
        SUM(CASE
            WHEN data_inizio_competenza IS NULL OR data_fine_competenza IS NULL THEN totale_reddito
            ELSE totale_reddito * (DATEDIFF(LEAST(data_fine_competenza, ?), GREATEST(data_inizio_competenza, ?)) + 1) / (DATEDIFF(data_fine_competenza, data_inizio_competenza) + 1)
        END) AS totale_reddito', [$period_start, $period_end, $period_end, $period_start])
        ->from('co_movimenti')
        ->where(function ($q) use ($period_start, $period_end) {
            $q->whereBetween('data', [$period_start, $period_end])
                ->orWhere(function ($q2) use ($period_start, $period_end) {
                    $q2->whereNotNull('data_inizio_competenza')
                        ->whereNotNull('data_fine_competenza')
                        ->where('data_fine_competenza', '>=', $period_start)
                        ->where('data_inizio_competenza', '<=', $period_end);
                })
                ->orWhere(function ($q2) use ($period_start, $period_end) {
                    $q2->whereNotNull('data_inizio_competenza')
                        ->whereNotNull('data_fine_competenza')
                        ->where('data_inizio_competenza', '<', $period_start)
                        ->where('data_fine_competenza', '>', $period_end);
                })
                ->orWhere(function ($q2) use ($period_start, $period_end) {
                    $q2->whereNotNull('data_inizio_competenza')
                        ->whereNotNull('data_fine_competenza')
                        ->where('data_inizio_competenza', '<=', $period_end)
                        ->where('data_inizio_competenza', '>=', $period_start);
                })
                ->orWhere(function ($q2) use ($period_start, $period_end) {
                    $q2->whereNotNull('data_inizio_competenza')
                        ->whereNotNull('data_fine_competenza')
                        ->where('data_fine_competenza', '>=', $period_start)
                        ->where('data_fine_competenza', '<=', $period_end);
                });
        })
        ->groupBy('id_conto');
};

$total_sottoconti = PianoDeiConti3::where('id_piano_dei_conti2', $conto_secondo->id)->count();
$usa_datatable = $total_sottoconti > $soglia_datatable;
$datatable_id = 'sottoconti-datatable-'.$conto_secondo->id;
$root_id = 'sottoconti-root-'.$conto_secondo->id;

$search_arr = get('search', true);
$search_value = (is_array($search_arr) && isset($search_arr['value'])) ? trim((string) $search_arr['value']) : '';

if (filter('draw', null, true) !== '') {
    $draw = (int) filter('draw', null, true);
    $start = (int) filter('start', null, true);
    $length = (int) filter('length', null, true);
    if ($length <= 0) {
        $length = 25;
    }

    $query = PianoDeiConti3::where('id_piano_dei_conti2', $conto_secondo->id);

    if ($search_value !== '') {
        $query->where(function ($q) use ($search_value, $conto_secondo) {
            $q->where('descrizione', 'like', '%'.$search_value.'%')
                ->orWhere('numero', 'like', '%'.$search_value.'%');
        });
    }

    $records_filtered = $query->count();

    $terzo_livello = $query->orderBy('numero', 'asc')
        ->offset($start)
        ->limit($length)
        ->get();

    $data = [];
    foreach ($terzo_livello as $conto_terzo) {
        $movimenti = Movimento::selectRaw('COUNT(*) AS numero_movimenti,
            SUM(CASE WHEN data BETWEEN ? AND ? THEN totale ELSE 0 END) AS totale,
            SUM(CASE
                WHEN data_inizio_competenza IS NULL OR data_fine_competenza IS NULL THEN totale_reddito
                ELSE totale_reddito * (DATEDIFF(LEAST(data_fine_competenza, ?), GREATEST(data_inizio_competenza, ?)) + 1) / (DATEDIFF(data_fine_competenza, data_inizio_competenza) + 1)
            END) AS totale_reddito', [$period_start, $period_end, $period_end, $period_start])
            ->where('id_conto', $conto_terzo->id)
            ->where(function ($q) use ($period_start, $period_end) {
                $q->whereBetween('data', [$period_start, $period_end])
                    ->orWhere(function ($q2) use ($period_start, $period_end) {
                        $q2->whereNotNull('data_inizio_competenza')
                            ->whereNotNull('data_fine_competenza')
                            ->where('data_fine_competenza', '>=', $period_start)
                            ->where('data_inizio_competenza', '<=', $period_end);
                    })
                    ->orWhere(function ($q2) use ($period_start, $period_end) {
                        $q2->whereNotNull('data_inizio_competenza')
                            ->whereNotNull('data_fine_competenza')
                            ->where('data_inizio_competenza', '<', $period_start)
                            ->where('data_fine_competenza', '>', $period_end);
                    })
                    ->orWhere(function ($q2) use ($period_start, $period_end) {
                        $q2->whereNotNull('data_inizio_competenza')
                            ->whereNotNull('data_fine_competenza')
                            ->where('data_inizio_competenza', '<=', $period_end)
                            ->where('data_inizio_competenza', '>=', $period_start);
                    })
                    ->orWhere(function ($q2) use ($period_start, $period_end) {
                        $q2->whereNotNull('data_inizio_competenza')
                            ->whereNotNull('data_fine_competenza')
                            ->where('data_fine_competenza', '>=', $period_start)
                            ->where('data_fine_competenza', '<=', $period_end);
                    });
            })
            ->first();

        $conto_terzo->numero_movimenti = $movimenti->numero_movimenti ?? 0;
        $conto_terzo->totale = $movimenti->totale ?? 0;
        $conto_terzo->totale_reddito = $movimenti->totale_reddito ?? 0;
        $conto_terzo->id_anagrafica = \Modules\Anagrafiche\Anagrafica::where('id_conto_cliente', $conto_terzo->id)
            ->orWhere('id_conto_fornitore', $conto_terzo->id)
            ->value('id');
        $conto_terzo->deleted_at = \Modules\Anagrafiche\Anagrafica::where('id_conto_cliente', $conto_terzo->id)
            ->orWhere('id_conto_fornitore', $conto_terzo->id)
            ->value('deleted_at');

        $cells = partitario_sottoconto_cells($conto_terzo, $conto_secondo, $conto_primo);
        $row = [
            'DT_RowId' => 'conto3-'.$conto_terzo->id,
            'DT_RowClass' => 'conto3',
        ];
        if (empty($conto_terzo->numero_movimenti)) {
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
    echo '<div id="'.$root_id.'">';

    if ($total_sottoconti == 0) {
        echo '<br><span>'.tr('Nessun conto presente').'</span>';
    } elseif ($usa_datatable) {
        $terzo_livello = PianoDeiConti3::where('id_piano_dei_conti2', $conto_secondo->id)->get();

        $totale_conto2 = 0;
        $totale_reddito2 = 0;

        foreach ($terzo_livello as $conto_terzo) {
            $movimenti = Movimento::selectRaw('COUNT(*) AS numero_movimenti,
                SUM(CASE WHEN data BETWEEN ? AND ? THEN totale ELSE 0 END) AS totale,
                SUM(CASE
                    WHEN data_inizio_competenza IS NULL OR data_fine_competenza IS NULL THEN totale_reddito
                    ELSE totale_reddito * (DATEDIFF(LEAST(data_fine_competenza, ?), GREATEST(data_inizio_competenza, ?)) + 1) / (DATEDIFF(data_fine_competenza, data_inizio_competenza) + 1)
                END) AS totale_reddito', [$period_start, $period_end, $period_end, $period_start])
                ->where('id_conto', $conto_terzo->id)
                ->where(function ($q) use ($period_start, $period_end) {
                    $q->whereBetween('data', [$period_start, $period_end])
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_fine_competenza', '>=', $period_start)
                                ->where('data_inizio_competenza', '<=', $period_end);
                        })
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_inizio_competenza', '<', $period_start)
                                ->where('data_fine_competenza', '>', $period_end);
                        })
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_inizio_competenza', '<=', $period_end)
                                ->where('data_inizio_competenza', '>=', $period_start);
                        })
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_fine_competenza', '>=', $period_start)
                                ->where('data_fine_competenza', '<=', $period_end);
                        });
                })
                ->first();

            $totale_conto2 += $movimenti->totale ?? 0;
            $totale_reddito2 += $movimenti->totale_reddito ?? 0;
        }

        if ($conto_primo->descrizione != 'Patrimoniale') {
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

        $columns_js = '{ data: "0" }, { data: "1", className: "text-right" }';
        if ($is_economico) {
            $columns_js .= ', { data: "2", className: "text-right" }';
        }
        $columns_js .= ', { data: "'.($is_economico ? '3' : '2').'" }';

        echo '
<script>
    $(function() {
        var $dt = $("#'.$datatable_id.'");
        if ($dt.length && !$.fn.DataTable.isDataTable($dt)) {
            $dt.DataTable({
                serverSide: true,
                deferRender: true,
                searchDelay: 400,
                ajax: {
                    url: globals.rootdir + "/modules/partitario/dettagli_conto2.php?id_module=" + globals.id_module + "&id_conto='.$conto_secondo->id.'",
                    type: "get",
                    dataSrc: "data",
                    beforeSend: function () { $("#main_loading").stop(true, true).show(); },
                    complete: function () { $("#main_loading").stop(true, true).fadeOut(); },
                },
                columns: [ '.$columns_js.' ],
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
                search: { search: ($("#input-cerca").val() || "") },
                dom: "<\'row\'<\'col-sm-12 col-md-6\'l><\'col-sm-12 col-md-6\'f>>rt<\'row\'<\'col-sm-12 col-md-5\'i><\'col-sm-12 col-md-7\'p>>",
                initComplete: function () {
                    var api = this.api();
                    var $container = $(api.table().container()).addClass("sottoconti-dt");

                    $container.find(".dataTables_length select").select2({
                        theme: "bootstrap4",
                        language: "it",
                        width: "auto",
                        minimumResultsForSearch: -1,
                        allowClear: false,
                    });

                    $container.find(".dataTables_filter input").addClass("form-control text-center");

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
        $terzo_livello = PianoDeiConti3::with(['secondoLivello.primoLivello'])->where('id_piano_dei_conti2', $conto_secondo->id)->orderBy('numero', 'asc')->get();
        $totale_conto2 = 0;
        $totale_reddito2 = 0;

        echo '
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
            <tbody>';
        foreach ($terzo_livello as $conto_terzo) {
            $movimenti = Movimento::selectRaw('COUNT(*) AS numero_movimenti,
                SUM(CASE WHEN data BETWEEN ? AND ? THEN totale ELSE 0 END) AS totale,
                SUM(CASE
                    WHEN data_inizio_competenza IS NULL OR data_fine_competenza IS NULL THEN totale_reddito
                    ELSE totale_reddito * (DATEDIFF(LEAST(data_fine_competenza, ?), GREATEST(data_inizio_competenza, ?)) + 1) / (DATEDIFF(data_fine_competenza, data_inizio_competenza) + 1)
                END) AS totale_reddito', [$period_start, $period_end, $period_end, $period_start])
                ->where('id_conto', $conto_terzo->id)
                ->where(function ($q) use ($period_start, $period_end) {
                    $q->whereBetween('data', [$period_start, $period_end])
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_fine_competenza', '>=', $period_start)
                                ->where('data_inizio_competenza', '<=', $period_end);
                        })
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_inizio_competenza', '<', $period_start)
                                ->where('data_fine_competenza', '>', $period_end);
                        })
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_inizio_competenza', '<=', $period_end)
                                ->where('data_inizio_competenza', '>=', $period_start);
                        })
                        ->orWhere(function ($q2) use ($period_start, $period_end) {
                            $q2->whereNotNull('data_inizio_competenza')
                                ->whereNotNull('data_fine_competenza')
                                ->where('data_fine_competenza', '>=', $period_start)
                                ->where('data_fine_competenza', '<=', $period_end);
                        });
                })
                ->first();

            $conto_terzo->numero_movimenti = $movimenti->numero_movimenti ?? 0;
            $conto_terzo->totale = $movimenti->totale ?? 0;
            $conto_terzo->totale_reddito = $movimenti->totale_reddito ?? 0;
            $conto_terzo->id_anagrafica = \Modules\Anagrafiche\Anagrafica::where('id_conto_cliente', $conto_terzo->id)
                ->orWhere('id_conto_fornitore', $conto_terzo->id)
                ->value('id');
            $conto_terzo->deleted_at = \Modules\Anagrafiche\Anagrafica::where('id_conto_cliente', $conto_terzo->id)
                ->orWhere('id_conto_fornitore', $conto_terzo->id)
                ->value('deleted_at');

            $totale_conto = $conto_terzo->totale;
            $totale_reddito = $conto_terzo->totale_reddito;
            if ($conto_primo->descrizione != 'Patrimoniale') {
                $totale_conto = -$totale_conto ?: 0;
                $totale_reddito = -$totale_reddito ?: 0;
            }
            $totale_conto2 += $totale_conto;
            $totale_reddito2 += $totale_reddito;

            $cells = partitario_sottoconto_cells($conto_terzo, $conto_secondo, $conto_primo);
            echo '
                <tr class="conto3" id="conto3-'.$conto_terzo->id.'" style="'.(!empty($conto_terzo->numero_movimenti) ? '' : 'opacity: 0.5;').'">
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