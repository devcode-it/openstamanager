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

use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;

$documento = Intervento::find($id_record);

$module = Modules::get($documento->module);

if (get('documento') == 'fattura') {
    $final_module = 'Fatture di vendita';
    $op = 'add_documento';
    $tipo_documento_finale = Fattura::class;
} elseif (get('documento') == 'ordine_fornitore') {
    $final_module = 'Ordini fornitore';
    $op = 'add_ordine_cliente';
    $tipo_documento_finale = Ordine::class;
} elseif (get('documento') == 'ordine') {
    $final_module = 'Ordini cliente';
    $op = 'add_documento';
    $tipo_documento_finale = Ordine::class;
} else {
    $final_module = 'Ddt di vendita';
    $op = 'add_documento';
    $tipo_documento_finale = DDT::class;
}

$options = [
    'op' => $op,
    'type' => 'ordine',
    'module' => $final_module,
    'button' => tr('Aggiungi'),
    'create_document' => true,
    'serials' => true,
    'documento' => $documento,
    'tipo_documento_finale' => $tipo_documento_finale,
];

echo App::load('importa.php', [], $options, true);
