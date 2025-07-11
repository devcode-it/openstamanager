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

use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

if (!empty($id_record)) {
    $files = Interaction::getFileList();
    $record = $files[$id_record - 1] ?? null;

    $has_next = isset($files[$id_record]);

    try {
        $fattura_pa = FatturaElettronica::manage($record['name'] ?? '');
        $anagrafica = $fattura_pa->findAnagrafica();
    } catch (UnexpectedValueException) {
        $imported = true;
    } catch (Exception) {
        $error = true;
    }

    // Rimozione .p7m dal nome del file (causa eventuale estrazione da ZIP)
    $record['name'] = preg_replace('/(.+)\.p7m$/i', '$1', $record['name'] ?? '');

    if (empty($record)) {
        flash()->warning(tr('Nessuna fattura da importare!'));

        redirect(base_path().'/controller.php?id_module='.$id_module);
    }

    // Se la fattura è già stata importata e siamo in modalità sequenza, passa alla successiva
    if (!empty($imported) && get('sequence') == 1) {
        $next_record = $id_record + 1;
        $next_file = $files[$next_record - 1] ?? null;

        if (!empty($next_file)) {
            flash()->info(tr('La fattura _NAME_ è già stata importata, passaggio alla successiva...', [
                '_NAME_' => $record['name']
            ]));
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$next_record.'&sequence=1');
        } else {
            flash()->info(tr('Tutte le fatture salvate sono state importate!'));
            redirect(base_path().'/controller.php?id_module='.$id_module);
        }
    }
}
