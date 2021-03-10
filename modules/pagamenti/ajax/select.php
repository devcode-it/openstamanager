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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - codice_modalita_pagamento_fe
     */
    case 'pagamenti':
        $query = "SELECT co_pagamenti.id,
           CONCAT_WS(' - ', codice_modalita_pagamento_fe, descrizione) AS descrizione,
           banca_vendite.id AS id_banca_vendite,
           CONCAT(banca_vendite.nome, ' - ', banca_vendite.iban) AS descrizione_banca_vendite,
           banca_acquisti.id AS id_banca_acquisti,
           CONCAT(banca_acquisti.nome, ' - ', banca_acquisti.iban) AS descrizione_banca_acquisti
        FROM co_pagamenti
            LEFT JOIN co_banche banca_vendite ON co_pagamenti.idconto_vendite = banca_vendite.id_pianodeiconti3
            LEFT JOIN co_banche banca_acquisti ON co_pagamenti.idconto_acquisti = banca_acquisti.id_pianodeiconti3
        |where| GROUP BY co_pagamenti.descrizione ORDER BY co_pagamenti.descrizione ASC";

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
