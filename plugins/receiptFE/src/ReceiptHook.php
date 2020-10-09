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

namespace Plugins\ReceiptFE;

use Hooks\Manager;
use Models\Cache;

class ReceiptHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        // Lettura cache
        $todo_cache = Cache::pool('Ricevute Elettroniche');

        return !$todo_cache->isValid() || !empty($todo_cache->content);
    }

    public function execute()
    {
        // Lettura cache
        $todo_cache = Cache::pool('Ricevute Elettroniche');
        $completed_cache = Cache::pool('Ricevute Elettroniche importate');

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
        $count = count($todo);

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
        $todo_cache = Cache::pool('Ricevute Elettroniche');
        $completed_cache = Cache::pool('Ricevute Elettroniche importate');

        $completed_number = count($completed_cache->content);
        $total_number = $completed_number + count($todo_cache->content);

        // Messaggio di importazione
        $message = tr('Sono state importate _NUM_ ricevute su _TOT_', [
            '_NUM_' => $completed_number,
            '_TOT_' => $total_number,
        ]);

        // Notifica sullo stato dell'importazione
        $notify = $total_number != 0;
        $color = $total_number == $completed_number ? 'success' : 'yellow';

        return [
            'icon' => 'fa fa-ticket text-'.$color,
            'message' => $message,
            'show' => $notify,
        ];
    }
}
