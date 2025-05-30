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
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;

$documento = Ordine::find($id_record);

$module = Module::where('name', $documento->module)->first();

if (get('documento') == 'fattura') {
    $final_module = $module->name = 'Ordini cliente' ? 'Fatture di vendita' : 'Fatture di acquisto';
    $op = 'add_documento';
    $tipo_documento_finale = Fattura::class;
} elseif (get('documento') == 'ordine_fornitore') {
    $final_module = 'Ordini fornitore';
    $op = 'add_ordine_cliente';
    $tipo_documento_finale = Ordine::class;
} elseif (get('documento') == 'intervento') {
    $final_module = 'Interventi';
    $op = $module->name == 'Ordini cliente' ? 'add_documento' : 'add_intervento';
    $tipo_documento_finale = Intervento::class;
} else {
    $final_module = $module->name == 'Ordini cliente' ? 'Ddt in uscita' : 'Ddt in entrata';
    $op = 'add_ordine';
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
