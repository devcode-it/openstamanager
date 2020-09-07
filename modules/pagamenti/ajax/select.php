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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - codice_modalita_pagamento_fe
     */
    case 'pagamenti':
        $query = "SELECT id,
           CONCAT_WS(' - ', codice_modalita_pagamento_fe, descrizione) AS descrizione,
           (SELECT id FROM co_banche WHERE id_pianodeiconti3 = co_pagamenti.idconto_vendite LIMIT 1) AS id_banca_vendite,
           (SELECT id FROM co_banche WHERE id_pianodeiconti3 = co_pagamenti.idconto_acquisti LIMIT 1) AS id_banca_acquisti
        FROM co_pagamenti |where| GROUP BY descrizione ORDER BY descrizione ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_pagamenti.id = '.prepare($element);
        }

        if (!empty($superselect['codice_modalita_pagamento_fe'])) {
            $where[] = 'codice_modalita_pagamento_fe = '.prepare($superselect['codice_modalita_pagamento_fe']);
        }

        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
