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

use Modules\Ordini\Ordine;

include_once __DIR__.'/../../core.php';

// Info contratto
$documento = Ordine::find($id_record);
$dir = $documento->direzione;

// Impostazioni per la gestione
$options = [
    'op' => 'crea-fattura-anticipo',
    'action' => 'add',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'id_documento' => $documento->id,
    'type' => 'ordine',
    'totale_imponibile_documento' => $documento->totale_imponibile,
    'totale_documento' => $documento->totale,
    'numero_esterno' => $documento->numero_esterno,
    'value' => $_GET['value'],
    'id_acconto' => $_GET['id_acconto'],
    'data' => Translator::dateToLocale($documento->data),
    'select-options' => [
        'articoli' => [
            'idanagrafica' => $documento->idanagrafica,
            'dir' => $documento->direzione,
            'permetti_movimento_a_zero' => 1,
            'idagente' => $documento->idagente,
        ],
    ],
];

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'sconto_unitario' => 0,
    'tipo_sconto' => '',
    'idiva' => '',
    'provvigione_default' => 0,
    'tipo_provvigione_default' => 'PRC',
];

$file = 'crea_fattura_anticipo';

echo App::load($file.'.php', $result, $options);
