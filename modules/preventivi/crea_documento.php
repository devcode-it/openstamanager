<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;

$documento = Preventivo::find($id_record);

if (get('documento') == 'fattura') {
    $final_module = 'Fatture di vendita';
    $op = 'add_documento';
    $tipo_documento_finale = Fattura::class;
} elseif (get('documento') == 'ordine') {
    $final_module = 'Ordini cliente';
    $op = 'add_preventivo';
    $tipo_documento_finale = Ordine::class;
} elseif (get('documento') == 'ddt') {
    $final_module = 'Ddt di vendita';
    $op = 'add_documento';
    $tipo_documento_finale = DDT::class;
} else {
    $final_module = 'Contratti';
    $op = 'add_preventivo';
    $tipo_documento_finale = Contratto::class;
}

$options = [
    'op' => $op,
    'type' => 'preventivo',
    'module' => $final_module,
    'button' => tr('Aggiungi'),
    'dir' => 'entrata',
    'create_document' => true,
    'documento' => $documento,
    'tipo_documento_finale' => $tipo_documento_finale,
];

echo App::load('importa.php', [], $options, true);
