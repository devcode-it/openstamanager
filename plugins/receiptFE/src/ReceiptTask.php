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
use Models\Cache;
use Tasks\Manager;

/**
 * Task dedicata all'importazione di tutte le ricevute individuate dal sistema automatico di gestione Fatture Elettroniche.
 *
 * @see MissingReceiptTask Gestione ricevute non individuate per malfunzionamenti.
 */
class ReceiptTask extends Manager
{
    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Ricevute importate correttamente!'),
        ];

        if (!Interaction::isEnabled()) {
            $result = [
                'response' => 2,
                'message' => tr('Importazione automatica disattivata'),
            ];

            return $result; 
        }

        $todo_cache = Cache::where('name', 'Ricevute Elettroniche')->first();
        $completed_cache = Cache::where('name', 'Ricevute Elettroniche importate')->first();

        // Refresh cache
        $list = Interaction::getRemoteList();

        $todo_cache->set($list);
        $completed_cache->set([]);

        // Caricamento elenco di importazione
        $todo = $todo_cache->content;
        if (empty($todo)) {
            $result = [
                'response' => 1,
                'message' => tr('Nessuna ricevuta da importare!'),
            ];
            return $result;
        }

        // Caricamento elenco di ricevute imporate
        $completed = $completed_cache->content;
        $count = (is_array($todo) ? count($todo) : 0);

        // Esecuzione di 10 imporazioni
        for ($i = 0; $i < 25 && $i < $count; ++$i) {
            $element = $todo[$i];

            if ($element !== null) {
                // Importazione ricevuta
                $name = $element['name'];
                $fattura = Ricevuta::process($name);

                if ($fattura !== null) {
                    $completed[] = $element;
                    unset($todo[$i]);
                }
            }
        }

        // Aggiornamento cache
        $todo_cache->set($todo);
        $completed_cache->set($completed);

        if( empty($list) ){
            $result = [
                'response' => 1,
                'message' => tr('Nessuna ricevuta da importare'),
            ];
        }

        // Esecuzione dell'importazione
        foreach ($list as $element) {
            $name = $element['name'];

            Ricevuta::process($name);
        }

        return $result;
    }
}
