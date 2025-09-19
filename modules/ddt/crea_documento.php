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

$documento = DDT::find($id_record);
$tipo_documento_finale = Fattura::class;

$module = Module::find($id_module);

if ($module->name == 'Ddt in uscita') {
    $final_module = 'Fatture di vendita';
    $dir = 'entrata';
} else {
    $final_module = 'Fatture di acquisto';
    $dir = 'uscita';
}

$options = [
    'op' => 'add_documento',
    'type' => 'ddt',
    'module' => $final_module,
    'serials' => true,
    'button' => tr('Aggiungi'),
    'dir' => $dir,
    'create_document' => true,
    'documento' => $documento,
    'tipo_documento_finale' => $tipo_documento_finale,
    'reversed' => $documento->reversed,
];

echo App::load('importa.php', [], $options, true);
