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

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Impianti\Impianto;
use Modules\Interventi\Intervento;
use Modules\Interventi\Components\Sessione;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;

$start = filter('start');
$end = filter('end');
$type = filter('type');

$anagrafica = Anagrafica::withTrashed()->find($id_record);
if (empty($anagrafica)) {
    return;
}

$link_module = function ($name) {
    return Module::where('name', $name)->first()->id;
};

$titolo = '';
$results = [];
$columns = [];

switch ($type) {
    case 'preventivi':
        $titolo = tr('Preventivi');
        $results = Preventivo::whereBetween('data_bozza', [$start, $end])
            ->where('id_anagrafica', $id_record)
            ->where('default_revision', 1)
            ->get();
        $columns = [
            'numero' => tr('Numero'),
            'data_bozza' => tr('Data'),
            'nome' => tr('Nome'),
            'stato' => tr('Stato'),
            'totale_imponibile' => tr('Totale'),
        ];
        break;

    case 'contratti':
        $titolo = tr('Contratti');
        $results = Contratto::whereBetween('data_bozza', [$start, $end])
            ->where('id_anagrafica', $id_record)
            ->get();
        $columns = [
            'numero' => tr('Numero'),
            'data_bozza' => tr('Data'),
            'nome' => tr('Nome'),
            'stato' => tr('Stato'),
            'totale_imponibile' => tr('Totale'),
        ];
        break;

    case 'ordini_cliente':
        $titolo = tr('Ordini cliente');
        $results = Ordine::whereBetween('data', [$start, $end])
            ->where('id_anagrafica', $id_record)
            ->get();
        $columns = [
            'numero' => tr('Numero'),
            'data' => tr('Data'),
            'stato' => tr('Stato'),
            'totale_imponibile' => tr('Totale'),
        ];
        break;

    case 'interventi':
        $titolo = tr('Attività');
        $id_param = $anagrafica->isTipo('Cliente') ? 'i.id_anagrafica = '.prepare($id_record) : 'EXISTS (SELECT 1 FROM in_interventi_tecnici WHERE id_intervento = i.id AND id_tecnico = '.prepare($id_record).')';
        $results = $dbo->fetchArray('
            SELECT i.id, i.codice, i.data_richiesta,
                MAX(it.orario_fine) AS data_fine,
                ti.name AS tipo,
                SUM(it.ore) AS ore,
                si.name AS stato,
                GROUP_CONCAT(DISTINCT a.ragione_sociale ORDER BY a.ragione_sociale SEPARATOR ", ") AS tecnici,
                i.descrizione
            FROM in_interventi i
            INNER JOIN in_stati_intervento si ON si.id = i.id_stato
            INNER JOIN in_tipi_intervento ti ON ti.id = i.id_tipo_intervento
            LEFT JOIN in_interventi_tecnici it ON it.id_intervento = i.id
            LEFT JOIN an_anagrafiche a ON a.id = it.id_tecnico
            WHERE i.data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end).' AND '.$id_param.'
            GROUP BY i.id, i.codice, i.data_richiesta, ti.name, si.name, i.descrizione
            ORDER BY i.data_richiesta ASC');
        $columns = [
            'codice' => tr('Numero'),
            'data_richiesta' => tr('Data inizio'),
            'data_fine' => tr('Data fine'),
            'tipo' => tr('Tipo'),
            'ore' => tr('Ore'),
            'stato' => tr('Stato'),
            'tecnici' => tr('Tecnici'),
            'descrizione' => tr('Descrizione'),
        ];
        break;

    case 'sessioni':
        $titolo = tr('Ore lavorate');
        if ($anagrafica->isTipo('Cliente')) {
            $ids = $dbo->fetchArray('SELECT DISTINCT in_interventi_tecnici.id FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi.id = in_interventi_tecnici.id_intervento WHERE in_interventi.id_anagrafica = '.prepare($id_record).' AND in_interventi_tecnici.orario_inizio BETWEEN '.prepare($start).' AND '.prepare($end));
        } else {
            $ids = $dbo->fetchArray('SELECT id FROM in_interventi_tecnici WHERE id_tecnico = '.prepare($id_record).' AND orario_inizio BETWEEN '.prepare($start).' AND '.prepare($end));
        }
        $results = Sessione::whereBetween('orario_inizio', [$start, $end])
            ->whereIn('id', array_column($ids, 'id'))
            ->get();
        $columns = [
            'orario_inizio' => tr('Data'),
            'id_tecnico' => tr('Tecnico'),
            'ore' => tr('Ore'),
        ];
        break;

    case 'ddt':
        $titolo = tr('Ddt in uscita');
        $results = DDT::whereBetween('data', [$start, $end])
            ->where('id_anagrafica', $id_record)
            ->whereHas('tipo', function ($query) {
                $query->where('dt_tipi_ddt.dir', '=', 'entrata');
            })
            ->get();
        $columns = [
            'numero' => tr('Numero'),
            'data' => tr('Data'),
            'stato' => tr('Stato'),
            'totale_imponibile' => tr('Totale'),
        ];
        break;

    case 'fatture':
        $titolo = tr('Fatture');
        $segmenti = $dbo->select('zz_segments', 'id', [], ['autofatture' => 0]);
        $results = Fattura::whereBetween('data', [$start, $end])
            ->where('id_anagrafica', $id_record)
            ->whereHas('tipo', fn ($query) => $query->where('co_tipi_documento.dir', '=', 'entrata'))
            ->whereIn('id_segment', array_column($segmenti, 'id'))
            ->get();
        $columns = [
            'numero' => tr('Numero'),
            'data' => tr('Data'),
            'stato' => tr('Stato'),
            'totale_imponibile' => tr('Totale'),
        ];
        break;

    case 'impianti':
        $titolo = tr('Impianti');
        $results = Impianto::whereBetween('data', [$start, $end])
            ->where('id_anagrafica', $id_record)
            ->get();
        $columns = [
            'matricola' => tr('Matricola'),
            'nome' => tr('Nome'),
            'data' => tr('Data'),
        ];
        break;

    case 'articoli_venduti':
        $titolo = tr('Articoli venduti');
        $results = $dbo->fetchArray('
            SELECT id_articolo, codice, name, SUM(qta) AS qta
            FROM (
                SELECT rd.id_articolo, a.codice, a.name, rd.qta
                FROM co_righe_documenti rd
                INNER JOIN co_documenti d ON d.id = rd.id_documento
                INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
                INNER JOIN mg_articoli a ON a.id = rd.id_articolo
                WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = \'uscita\' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).'
                UNION ALL
                SELECT rdd.id_articolo, a.codice, a.name, rdd.qta
                FROM dt_righe_ddt rdd
                INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
                INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
                INNER JOIN mg_articoli a ON a.id = rdd.id_articolo
                WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = \'uscita\' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end).'
            ) AS righe
            GROUP BY id_articolo, codice, name
            ORDER BY name ASC');
        $columns = [
            'codice' => tr('Codice'),
            'name' => tr('Descrizione'),
            'qta' => tr('Quantità'),
        ];
        break;

    case 'articoli_acquistati':
        $titolo = tr('Articoli acquistati');
        $results = $dbo->fetchArray('
            SELECT id_articolo, codice, name, SUM(qta) AS qta
            FROM (
                SELECT rd.id_articolo, a.codice, a.name, rd.qta
                FROM co_righe_documenti rd
                INNER JOIN co_documenti d ON d.id = rd.id_documento
                INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
                INNER JOIN mg_articoli a ON a.id = rd.id_articolo
                WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = \'entrata\' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).'
                UNION ALL
                SELECT rdd.id_articolo, a.codice, a.name, rdd.qta
                FROM dt_righe_ddt rdd
                INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
                INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
                INNER JOIN mg_articoli a ON a.id = rdd.id_articolo
                WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = \'entrata\' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end).'
            ) AS righe
            GROUP BY id_articolo, codice, name
            ORDER BY name ASC');
        $columns = [
            'codice' => tr('Codice'),
            'name' => tr('Descrizione'),
            'qta' => tr('Quantità'),
        ];
        break;
}

if (empty($results) || (is_countable($results) ? count($results) : $results->count()) == 0) {
    echo '
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> '.tr('Nessun documento trovato nel periodo selezionato').'
    </div>';

    return;
}

$tipi_documento = [
    'preventivi',
    'contratti',
    'ordini_cliente',
    'interventi',
    'ddt',
    'fatture',
    'articoli_venduti',
    'articoli_acquistati',
];

$espandibile = in_array($type, $tipi_documento);

echo '
<table class="table table-striped table-bordered table-hover table-condensed">
    <thead>
        <tr>';
if ($espandibile) {
    echo '
            <th class="text-center" width="30"></th>';
}
foreach ($columns as $key => $label) {
    $class = in_array($key, ['totale_imponibile', 'qta']) ? 'text-right' : 'text-left';
    echo '
            <th class="'.$class.'">'.$label.'</th>';
}
echo '
        </tr>
    </thead>
    <tbody>';

foreach ($results as $result) {
    $id = is_object($result) ? $result->id : ($result['id_articolo'] ?? $result['id']);
    $link = '#';
    $module_name = '';
    $source = '';

    if (is_object($result)) {
        $source = $result->source ?? '';
        $module_name = match ($type) {
            'preventivi' => 'Preventivi',
            'contratti' => 'Contratti',
            'ordini_cliente' => 'Ordini cliente',
            'interventi' => 'Interventi',
            'ddt' => 'Ddt in uscita',
            'fatture' => 'Fatture di vendita',
            'impianti' => 'Impianti',
            'articoli_venduti', 'articoli_acquistati' => $source == 'ddt' ? 'Ddt in uscita' : 'Fatture di vendita',
            default => '',
        };

        if (!empty($module_name)) {
            $link = base_path_osm().'/editor.php?id_module='.$link_module($module_name).'&id_record='.$id;
        }
    }

    $row_attr = $espandibile ? ' data-toggle="details" data-type="'.$type.'" data-id="'.$id.'" data-start="'.$start.'" data-end="'.$end.'"'.($source ? ' data-source="'.$source.'"' : '').' style="cursor: pointer;"' : '';

    echo '
        <tr'.$row_attr.'>';
    if ($espandibile) {
        echo '
            <td class="text-center"><i class="fa fa-chevron-right"></i></td>';
    }
    foreach ($columns as $key => $label) {
        $class = in_array($key, ['totale_imponibile', 'qta']) ? 'text-right' : 'text-left';
        $value = is_object($result) ? $result->{$key} : $result[$key];

        if ($value instanceof Illuminate\Database\Eloquent\Model) {
            $value = $value->name ?? $value->title ?? $value->descrizione ?? '';
        }

        if ($key == 'totale_imponibile') {
            $value = moneyFormat($value);
        } elseif (in_array($key, ['data', 'data_bozza', 'data_richiesta'])) {
            $value = dateFormat($value);
        } elseif ($key == 'data_fine') {
            $value = !empty($value) ? dateFormat($value) : '';
        } elseif ($key == 'orario_inizio') {
            $value = timestampFormat($value);
        } elseif ($key == 'id_tecnico') {
            $value = is_object($result) && $result->anagrafica ? $result->anagrafica->nome : '';
        } elseif ($key == 'ore') {
            $value = $value !== null ? numberFormat($value, 0) : '0';
        } elseif ($key == 'qta') {
            $value = numberFormat($value, 2);
        } elseif ($key == 'numero') {
            $value = is_object($result) ? $result->numero : (isset($result['numero']) ? $result['numero'] : $result['codice']);
        }

        if (!empty($link) && $link != '#' && $key == array_key_first($columns)) {
            echo '
            <td class="'.$class.'"><a href="'.$link.'" target="_blank">'.$value.'</a></td>';
        } else {
            echo '
            <td class="'.$class.'">'.$value.'</td>';
        }
    }
    echo '
        </tr>';

    if ($espandibile) {
        echo '
        <tr class="righe-documento" style="display: none;">
            <td colspan="'.(count($columns) + 1).'">
                <div class="righe-content" data-loaded="0"></div>
            </td>
        </tr>';
    }
}

echo '
    </tbody>
</table>';

if ($espandibile) {
    echo '
<script>
$(document).ready(function () {
    $("tr[data-toggle=\"details\"]").on("click", function (e) {
        if ($(e.target).is("a")) {
            return;
        }

        var row = $(this);
        var details = row.next("tr.righe-documento");
        var icon = row.find("i.fa");
        var content = details.find(".righe-content");
        var start = row.data("start");
        var end = row.data("end");

        if (details.is(":visible")) {
            details.hide();
            icon.removeClass("fa-chevron-down").addClass("fa-chevron-right");
        } else {
            details.show();
            icon.removeClass("fa-chevron-right").addClass("fa-chevron-down");

            if (content.data("loaded") == 0) {
                content.html("<p class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Caricamento righe...</p>");
                $.ajax({
                    url: globals.rootdir + "/plugins/statistiche_anagrafiche/ajax/righe.php",
                    type: "POST",
                    data: {
                        type: row.data("type"),
                        id_record: globals.id_record,
                        id_documento: row.data("id"),
                        start: start,
                        end: end
                    },
                    success: function (data) {
                        content.html(data);
                        content.data("loaded", 1);
                    },
                    error: function () {
                        content.html("<div class=\"alert alert-danger\">Errore durante il caricamento delle righe</div>");
                    }
                });
            }
        }
    });
});
</script>';
}
