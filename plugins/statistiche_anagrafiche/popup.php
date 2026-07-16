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
        if ($anagrafica->isTipo('Cliente')) {
            $results = Intervento::whereBetween('data_richiesta', [$start, $end])
                ->where('id_anagrafica', $id_record)
                ->get();
        } else {
            $ids = $dbo->fetchArray('SELECT DISTINCT id_intervento FROM in_interventi_tecnici WHERE id_tecnico = '.prepare($id_record));
            $results = Intervento::whereBetween('data_richiesta', [$start, $end])
                ->whereIn('id', array_column($ids, 'id_intervento'))
                ->get();
        }
        $columns = [
            'codice' => tr('Numero'),
            'data_richiesta' => tr('Data richiesta'),
            'stato' => tr('Stato'),
            'totale_imponibile' => tr('Totale'),
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
            SELECT mg_articoli.id, mg_articoli.codice, mg_articoli.name,
                SUM(righe.qta) AS qta
            FROM (
                SELECT rd.id_articolo, rd.qta
                FROM co_righe_documenti rd
                INNER JOIN co_documenti d ON d.id = rd.id_documento
                INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
                WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = \'entrata\' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).'
                UNION ALL
                SELECT rdd.id_articolo, rdd.qta
                FROM dt_righe_ddt rdd
                INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
                INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
                WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = \'entrata\' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end).'
            ) righe
            INNER JOIN mg_articoli ON mg_articoli.id = righe.id_articolo
            GROUP BY mg_articoli.id, mg_articoli.codice, mg_articoli.name
            ORDER BY mg_articoli.name ASC');
        $columns = [
            'codice' => tr('Codice'),
            'descrizione' => tr('Descrizione'),
            'qta' => tr('Quantità'),
        ];
        break;

    case 'articoli_acquistati':
        $titolo = tr('Articoli acquistati');
        $results = $dbo->fetchArray('
            SELECT mg_articoli.id, mg_articoli.codice, mg_articoli.name,
                SUM(righe.qta) AS qta
            FROM (
                SELECT rd.id_articolo, rd.qta
                FROM co_righe_documenti rd
                INNER JOIN co_documenti d ON d.id = rd.id_documento
                INNER JOIN co_tipi_documento td ON td.id = d.id_tipo_documento
                WHERE d.id_anagrafica = '.prepare($id_record).' AND td.dir = \'uscita\' AND d.data BETWEEN '.prepare($start).' AND '.prepare($end).'
                UNION ALL
                SELECT rdd.id_articolo, rdd.qta
                FROM dt_righe_ddt rdd
                INNER JOIN dt_ddt dd ON dd.id = rdd.id_ddt
                INNER JOIN dt_tipi_ddt tdd ON tdd.id = dd.id_tipo_ddt
                WHERE dd.id_anagrafica = '.prepare($id_record).' AND tdd.dir = \'uscita\' AND dd.data BETWEEN '.prepare($start).' AND '.prepare($end).'
            ) righe
            INNER JOIN mg_articoli ON mg_articoli.id = righe.id_articolo
            GROUP BY mg_articoli.id, mg_articoli.codice, mg_articoli.name
            ORDER BY mg_articoli.name ASC');
        $columns = [
            'codice' => tr('Codice'),
            'descrizione' => tr('Descrizione'),
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

echo '
<table class="table table-striped table-bordered table-hover table-condensed">
    <thead>
        <tr>';
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
    $id = is_object($result) ? $result->id : $result['id'];
    $link = '#';
    $module_name = '';

    if (is_object($result)) {
        $module_name = match ($type) {
            'preventivi' => 'Preventivi',
            'contratti' => 'Contratti',
            'ordini_cliente' => 'Ordini cliente',
            'interventi' => 'Interventi',
            'ddt' => 'Ddt in uscita',
            'fatture' => 'Fatture di vendita',
            'impianti' => 'Impianti',
            default => '',
        };

        if (!empty($module_name)) {
            $link = base_path_osm().'/editor.php?id_module='.$link_module($module_name).'&id_record='.$id;
        }
    }

    echo '
        <tr>';
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
}

echo '
    </tbody>
</table>';
