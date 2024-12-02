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

namespace Plugins\ReceiptFE;

use Hooks\Manager;
use Models\Cache;
use Models\Module;

/**
 * Hook per l'importazione e il conteggio delle ricevute rilevate come da importare.
 *
 * @see MissingReceiptTask,ReceiptTask Per procedura più strutturata di importazione
 */
class ReceiptHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        if (!Interaction::isEnabled()) {
            return false;
        }

        // Lettura cache
        $todo_cache = Cache::where('name', 'Ricevute Elettroniche')->first();

        return !$todo_cache->isValid() || !empty($todo_cache->content);
    }

    public function execute()
    {
        // Lettura cache
        $todo_cache = Cache::where('name', 'Ricevute Elettroniche')->first();
        $completed_cache = Cache::where('name', 'Ricevute Elettroniche importate')->first();

        // Refresh cache
        if (!$todo_cache->isValid()) {
            $list = Interaction::getRemoteList();

            $todo_cache->set($list);
            $completed_cache->set([]);

            return;
        }

        // Caricamento elenco di importazione
        $todo = $todo_cache->content;
        if (empty($todo)) {
            return;
        }

        // Caricamento elenco di ricevute imporate
        $completed = $completed_cache->content;
        $count = (is_array($todo) ? count($todo) : 0);

        // Esecuzione di 10 imporazioni
        for ($i = 0; $i < 10 && $i < $count; ++$i) {
            $element = $todo[$i];

            // Importazione ricevuta
            $name = $element['name'];
            $fattura = Ricevuta::process($name);

            if (!empty($fattura)) {
                $completed[] = $element;
                unset($todo[$i]);
            }
        }

        // Aggiornamento cache
        $todo_cache->set($todo);
        $completed_cache->set($completed);
    }

    public function response()
    {
        // Lettura cache
        $todo_cache = Cache::where('name', 'Ricevute Elettroniche')->first();
        $completed_cache = Cache::where('name', 'Ricevute Elettroniche importate')->first();

        $completed_number = (is_array($completed_cache->content) ? count($completed_cache->content) : 0);
        $total_number = $completed_number + (is_array($todo_cache->content) ? count($todo_cache->content) : 0);

        // Messaggio di importazione
        $message = tr('_NUM_ ricevut_A_ su _TOT_ totali', [
            '_NUM_' => (($completed_number > 1) ? tr('Sono state importate') : tr('E\' stata importata')).' '.$completed_number,
            '_A_' => (($completed_number > 1) ? tr('e') : tr('a')),
            '_TOT_' => $total_number,
        ]);

        // Notifica sullo stato dell'importazione
        $notify = $total_number != 0;
        $color = $total_number == $completed_number ? 'success' : 'yellow';

        $id_module = Module::where('name', 'Fatture di vendita')->first()->id;

        return [
            'icon' => 'fa fa-ticket text-'.$color,
            'message' => $message,
            'show' => $notify,
            'link' => base_path().'/controller.php?id_module='.$id_module,
        ];
    }
}
